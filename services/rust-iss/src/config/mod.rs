use std::time::Duration;
use sqlx::PgPool;
use tracing::error;
use tracing_subscriber::{EnvFilter, FmtSubscriber};
use redis::aio::ConnectionManager;

#[derive(Clone)]
pub struct AppState {
    pub pool: PgPool,
    pub redis: ConnectionManager,
    pub nasa_url: String,
    pub nasa_key: String,
    pub fallback_url: String,
    pub every_osdr: u64,
    pub every_iss: u64,
    pub every_apod: u64,
    pub every_neo: u64,
    pub every_donki: u64,
    pub every_spacex: u64,
}

impl AppState {
    pub async fn new() -> anyhow::Result<Self> {
        let subscriber = FmtSubscriber::builder()
            .with_env_filter(EnvFilter::from_default_env())
            .finish();
        let _ = tracing::subscriber::set_global_default(subscriber);

        dotenvy::dotenv().ok();

        let db_url = std::env::var("DATABASE_URL").expect("DATABASE_URL is required");

        let nasa_url = std::env::var("NASA_API_URL")
            .unwrap_or_else(|_| "https://visualization.osdr.nasa.gov/biodata/api/v2/datasets/?format=json".to_string());
        let nasa_key = std::env::var("NASA_API_KEY").unwrap_or_default();

        let fallback_url = std::env::var("WHERE_ISS_URL")
            .unwrap_or_else(|_| "https://api.wheretheiss.at/v1/satellites/25544".to_string());

        let every_osdr   = env_u64("FETCH_EVERY_SECONDS", 600);
        let every_iss    = env_u64("ISS_EVERY_SECONDS",   120);
        let every_apod   = env_u64("APOD_EVERY_SECONDS",  43200);
        let every_neo    = env_u64("NEO_EVERY_SECONDS",   7200);
        let every_donki  = env_u64("DONKI_EVERY_SECONDS", 3600);
        let every_spacex = env_u64("SPACEX_EVERY_SECONDS",3600);

        let pool = sqlx::postgres::PgPoolOptions::new().max_connections(5).connect(&db_url).await?;
        init_db(&pool).await?;

        let redis_url = std::env::var("REDIS_URL").expect("REDIS_URL is required");
        let redis_client = redis::Client::open(redis_url)?;
        let redis = ConnectionManager::new(redis_client).await?;

        Ok(AppState {
            pool,
            redis,
            nasa_url,
            nasa_key,
            fallback_url,
            every_osdr, every_iss, every_apod, every_neo, every_donki, every_spacex,
        })
    }

    pub fn start_background_tasks(&self) {
        // OSDR
        {
            let st = self.clone();
            tokio::spawn(async move {
                loop {
                    if let Err(e) = crate::services::fetch_and_store_osdr(&st).await { error!("osdr err {e:?}") }
                    tokio::time::sleep(Duration::from_secs(st.every_osdr)).await;
                }
            });
        }
        // ISS
        {
            let st = self.clone();
            tokio::spawn(async move {
                loop {
                    if let Err(e) = crate::services::fetch_and_store_iss(&st.pool, &st.fallback_url, &mut st.redis.clone()).await { error!("iss err {e:?}") }
                    tokio::time::sleep(Duration::from_secs(st.every_iss)).await;
                }
            });
        }
        // APOD
        {
            let st = self.clone();
            tokio::spawn(async move {
                loop {
                    if let Err(e) = crate::services::fetch_apod(&st).await { error!("apod err {e:?}") }
                    tokio::time::sleep(Duration::from_secs(st.every_apod)).await;
                }
            });
        }
        // NeoWs
        {
            let st = self.clone();
            tokio::spawn(async move {
                loop {
                    if let Err(e) = crate::services::fetch_neo_feed(&st).await { error!("neo err {e:?}") }
                    tokio::time::sleep(Duration::from_secs(st.every_neo)).await;
                }
            });
        }
        // DONKI
        {
            let st = self.clone();
            tokio::spawn(async move {
                loop {
                    if let Err(e) = crate::services::fetch_donki(&st).await { error!("donki err {e:?}") }
                    tokio::time::sleep(Duration::from_secs(st.every_donki)).await;
                }
            });
        }
        // SpaceX
        {
            let st = self.clone();
            tokio::spawn(async move {
                loop {
                    if let Err(e) = crate::services::fetch_spacex_next(&st).await { error!("spacex err {e:?}") }
                    tokio::time::sleep(Duration::from_secs(st.every_spacex)).await;
                }
            });
        }
    }
}

fn env_u64(k: &str, d: u64) -> u64 {
    std::env::var(k).ok().and_then(|s| s.parse().ok()).unwrap_or(d)
}

/* ---------- DB boot ---------- */
async fn init_db(pool: &PgPool) -> anyhow::Result<()> {
    // ISS
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS iss_fetch_log(
            id BIGSERIAL PRIMARY KEY,
            fetched_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            source_url TEXT NOT NULL,
            payload JSONB NOT NULL
        )"
    ).execute(pool).await?;

    // OSDR
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS osdr_items(
            id BIGSERIAL PRIMARY KEY,
            dataset_id TEXT,
            title TEXT,
            status TEXT,
            updated_at TIMESTAMPTZ,
            inserted_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            raw JSONB NOT NULL
        )"
    ).execute(pool).await?;
    sqlx::query(
        "CREATE UNIQUE INDEX IF NOT EXISTS ux_osdr_dataset_id
         ON osdr_items(dataset_id) WHERE dataset_id IS NOT NULL"
    ).execute(pool).await?;

    // универсальный кэш космоданных
    sqlx::query(
        "CREATE TABLE IF NOT EXISTS space_cache(
            id BIGSERIAL PRIMARY KEY,
            source TEXT NOT NULL,
            fetched_at TIMESTAMPTZ NOT NULL DEFAULT now(),
            payload JSONB NOT NULL
        )"
    ).execute(pool).await?;
    sqlx::query("CREATE INDEX IF NOT EXISTS ix_space_cache_source ON space_cache(source,fetched_at DESC)").execute(pool).await?;

    Ok(())
}

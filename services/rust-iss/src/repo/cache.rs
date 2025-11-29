use serde_json::Value;
use sqlx::{PgPool, Row};
use crate::domain::SpaceCacheItem;

pub struct CacheRepo;

impl CacheRepo {
    pub async fn get_latest(pool: &PgPool, source: &str) -> anyhow::Result<Option<SpaceCacheItem>> {
        let row = sqlx::query(
            "SELECT fetched_at, payload FROM space_cache
             WHERE source = $1 ORDER BY id DESC LIMIT 1"
        ).bind(source).fetch_optional(pool).await?;

        if let Some(r) = row {
            Ok(Some(SpaceCacheItem {
                source: source.to_string(),
                fetched_at: r.get("fetched_at"),
                payload: r.get("payload"),
            }))
        } else {
            Ok(None)
        }
    }

    pub async fn insert(pool: &PgPool, source: &str, payload: Value) -> anyhow::Result<()> {
        sqlx::query("INSERT INTO space_cache(source, payload) VALUES ($1,$2)")
            .bind(source).bind(payload).execute(pool).await?;
        Ok(())
    }

    pub async fn get_latest_as_value(pool: &PgPool, source: &str) -> Value {
        Self::get_latest(pool, source).await.ok().flatten()
            .map(|item| serde_json::json!({"at": item.fetched_at, "payload": item.payload}))
            .unwrap_or(Value::Null)
    }

    pub async fn get_osdr_count(pool: &PgPool) -> anyhow::Result<i64> {
        let count: i64 = sqlx::query("SELECT count(*) AS c FROM osdr_items")
            .fetch_one(pool).await?
            .get("c");
        Ok(count)
    }
}

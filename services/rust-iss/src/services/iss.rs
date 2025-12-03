use chrono::Utc;
use redis::aio::ConnectionManager;
use serde_json;
use sqlx::PgPool;
use crate::clients::IssClient;
use crate::repo::IssRepo;
use crate::models::IssData;

pub async fn fetch_and_store_iss(pool: &PgPool, url: &str, redis: &mut ConnectionManager) -> anyhow::Result<()> {
    let client = IssClient::new();
    let json = client.get(url).await?;
    IssRepo::insert(pool, url, json.clone()).await?;

    // Update cache with new data
    let cache_key = "iss:latest";
    let data = IssData {
        id: 0, // Will be set by DB, but for cache we can use placeholder
        fetched_at: Utc::now(),
        source_url: url.to_string(),
        payload: json,
    };
    let _: () = redis::cmd("SETEX")
        .arg(cache_key)
        .arg(120)
        .arg(serde_json::to_string(&data)?)
        .query_async(redis)
        .await
        .unwrap_or(());

    Ok(())
}

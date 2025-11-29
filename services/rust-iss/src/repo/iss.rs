use chrono::{DateTime, Utc};
use serde_json::Value;
use sqlx::{PgPool, Row};
use crate::domain::IssData;

pub struct IssRepo;

impl IssRepo {
    pub async fn get_last(pool: &PgPool) -> anyhow::Result<Option<IssData>> {
        let row_opt = sqlx::query(
            "SELECT id, fetched_at, source_url, payload
             FROM iss_fetch_log
             ORDER BY id DESC LIMIT 1"
        ).fetch_optional(pool).await?;

        if let Some(row) = row_opt {
            Ok(Some(IssData {
                id: row.get("id"),
                fetched_at: row.get("fetched_at"),
                source_url: row.get("source_url"),
                payload: row.try_get("payload").unwrap_or(Value::Null),
            }))
        } else {
            Ok(None)
        }
    }

    pub async fn get_last_two_for_trend(pool: &PgPool) -> anyhow::Result<Vec<(DateTime<Utc>, Value)>> {
        let rows = sqlx::query("SELECT fetched_at, payload FROM iss_fetch_log ORDER BY id DESC LIMIT 2")
            .fetch_all(pool).await?;

        Ok(rows.into_iter().map(|r| (r.get("fetched_at"), r.get("payload"))).collect())
    }

    pub async fn insert(pool: &PgPool, source_url: &str, payload: Value) -> anyhow::Result<()> {
        sqlx::query("INSERT INTO iss_fetch_log (source_url, payload) VALUES ($1, $2)")
            .bind(source_url)
            .bind(payload)
            .execute(pool).await?;
        Ok(())
    }
}

use chrono::{DateTime, Utc};
use serde_json::Value;
use sqlx::{PgPool, Row};
use crate::domain::OsdrItem;

pub struct OsdrRepo;

impl OsdrRepo {
    pub async fn list(pool: &PgPool, limit: i64) -> anyhow::Result<Vec<OsdrItem>> {
        let rows = sqlx::query(
            "SELECT id, dataset_id, title, status, updated_at, inserted_at, raw
             FROM osdr_items
             ORDER BY inserted_at DESC
             LIMIT $1"
        ).bind(limit).fetch_all(pool).await?;

        Ok(rows.into_iter().map(|r| OsdrItem {
            id: r.get("id"),
            dataset_id: r.get("dataset_id"),
            title: r.get("title"),
            status: r.get("status"),
            updated_at: r.get("updated_at"),
            inserted_at: r.get("inserted_at"),
            raw: r.get("raw"),
        }).collect())
    }

    pub async fn upsert(pool: &PgPool, dataset_id: Option<String>, title: Option<String>, status: Option<String>, updated_at: Option<DateTime<Utc>>, raw: Value) -> anyhow::Result<()> {
        if let Some(ds) = dataset_id.clone() {
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1,$2,$3,$4,$5)
                 ON CONFLICT (dataset_id) DO UPDATE
                 SET title=EXCLUDED.title, status=EXCLUDED.status,
                     updated_at=EXCLUDED.updated_at, raw=EXCLUDED.raw"
            ).bind(ds).bind(title).bind(status).bind(updated_at).bind(raw).execute(pool).await?;
        } else {
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1,$2,$3,$4,$5)"
            ).bind::<Option<String>>(None).bind(title).bind(status).bind(updated_at).bind(raw).execute(pool).await?;
        }
        Ok(())
    }
}

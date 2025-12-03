use chrono::{DateTime, Utc};
use serde::Serialize;
use serde_json::Value;

#[derive(Serialize)]
pub struct SpaceCacheItem {
    pub source: String,
    pub fetched_at: DateTime<Utc>,
    pub payload: Value,
}

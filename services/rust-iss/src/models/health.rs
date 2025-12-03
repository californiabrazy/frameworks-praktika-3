use chrono::{DateTime, Utc};
use serde::Serialize;

#[derive(Serialize)]
pub struct Health {
    pub status: &'static str,
    pub now: DateTime<Utc>,
}

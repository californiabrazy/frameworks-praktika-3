use serde::Serialize;
use serde_json::Value;

#[derive(Serialize)]
pub struct SpaceSummary {
    pub apod: Value,
    pub neo: Value,
    pub flr: Value,
    pub cme: Value,
    pub spacex: Value,
    pub iss: Value,
    pub osdr_count: i64,
}

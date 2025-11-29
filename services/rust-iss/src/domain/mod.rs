use axum::{
    http::StatusCode,
    response::{IntoResponse, Response},
    Json,
};
use chrono::{DateTime, Utc};
use serde::Serialize;
use serde_json::Value;
use uuid::Uuid;

#[derive(Serialize)]
pub struct Health {
    pub status: &'static str,
    pub now: DateTime<Utc>,
}

#[derive(Serialize, serde::Deserialize)]
pub struct IssData {
    pub id: i64,
    pub fetched_at: DateTime<Utc>,
    pub source_url: String,
    pub payload: Value,
}

#[derive(Serialize)]
pub struct Trend {
    pub movement: bool,
    pub delta_km: f64,
    pub dt_sec: f64,
    pub velocity_kmh: Option<f64>,
    pub from_time: Option<DateTime<Utc>>,
    pub to_time: Option<DateTime<Utc>>,
    pub from_lat: Option<f64>,
    pub from_lon: Option<f64>,
    pub to_lat: Option<f64>,
    pub to_lon: Option<f64>,
}

#[derive(Serialize)]
pub struct OsdrItem {
    pub id: i64,
    pub dataset_id: Option<String>,
    pub title: Option<String>,
    pub status: Option<String>,
    pub updated_at: Option<DateTime<Utc>>,
    pub inserted_at: DateTime<Utc>,
    pub raw: Value,
}

#[derive(Serialize)]
pub struct SpaceCacheItem {
    pub source: String,
    pub fetched_at: DateTime<Utc>,
    pub payload: Value,
}

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

#[derive(Debug, Serialize)]
pub enum ApiError {
    InternalServerError(String),
    NotFound(String),
    BadRequest(String),
    UpstreamError(String),
}

impl IntoResponse for ApiError {
    fn into_response(self) -> Response {
        let (code, message) = match self {
            ApiError::InternalServerError(msg) => ("INTERNAL_SERVER_ERROR", msg),
            ApiError::NotFound(msg) => ("NOT_FOUND", msg),
            ApiError::BadRequest(msg) => ("BAD_REQUEST", msg),
            ApiError::UpstreamError(msg) => ("UPSTREAM_403", msg),
        };
        let trace_id = Uuid::new_v4().to_string();
        let body = Json(serde_json::json!({
            "ok": false,
            "error": {
                "code": code,
                "message": message,
                "trace_id": trace_id
            }
        }));
        (StatusCode::OK, body).into_response()
    }
}

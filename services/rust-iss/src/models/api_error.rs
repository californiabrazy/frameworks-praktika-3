use axum::{
    http::StatusCode,
    response::{IntoResponse, Response},
    Json,
};

use serde::Serialize;
use uuid::Uuid;

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
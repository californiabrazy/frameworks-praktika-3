use axum::{
    extract::State,
    Json,
};
use serde_json::Value;
use crate::config::AppState;
use crate::models::ApiError;
use crate::repo::OsdrRepo;
use crate::services;

pub async fn osdr_sync(State(st): State<AppState>) -> Result<Json<Value>, ApiError> {
    let written = services::fetch_and_store_osdr(&st).await
        .map_err(|e| ApiError::InternalServerError(e.to_string()))?;
    Ok(Json(serde_json::json!({ "written": written })))
}

pub async fn osdr_list(State(st): State<AppState>) -> Result<Json<Value>, ApiError> {
    let limit = std::env::var("OSDR_LIST_LIMIT").ok()
        .and_then(|s| s.parse::<i64>().ok()).unwrap_or(20);

    let items = OsdrRepo::list(&st.pool, limit).await
        .map_err(|e| ApiError::InternalServerError(e.to_string()))?;

    let out: Vec<Value> = items.into_iter().map(|item| {
        serde_json::json!({
            "id": item.id,
            "dataset_id": item.dataset_id,
            "title": item.title,
            "status": item.status,
            "updated_at": item.updated_at,
            "inserted_at": item.inserted_at,
            "raw": item.raw,
        })
    }).collect();

    Ok(Json(serde_json::json!({ "items": out })))
}

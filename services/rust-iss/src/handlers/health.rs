use axum::Json;
use crate::models::Health;

pub async fn health() -> Json<Health> {
    Json(Health {
        status: "ok",
        now: chrono::Utc::now(),
    })
}

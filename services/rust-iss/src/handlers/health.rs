use axum::Json;
use crate::domain::Health;

pub async fn health() -> Json<Health> {
    Json(Health {
        status: "ok",
        now: chrono::Utc::now(),
    })
}

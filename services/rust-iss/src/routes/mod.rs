use axum::{
    routing::get,
    Router,
};
use crate::config::AppState;
use crate::handlers;

pub fn create_router(state: AppState) -> Router {
    Router::new()
        // общее
        .route("/health", get(handlers::health))
        .with_state(state.clone())
        // ISS
        .route("/last", get(handlers::last_iss))
        .route("/fetch", get(handlers::trigger_iss))
        .route("/iss/trend", get(handlers::iss_trend))
        // OSDR
        .route("/osdr/sync", get(handlers::osdr_sync))
        .route("/osdr/list", get(handlers::osdr_list))
        // Space cache
        .route("/space/:src/latest", get(handlers::space_latest))
        .route("/space/refresh", get(handlers::space_refresh))
        .route("/space/summary", get(handlers::space_summary))
        .with_state(state)
}

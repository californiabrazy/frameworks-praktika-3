use axum::{
    routing::get,
    Router,
};
use crate::config::AppState;
use crate::handlers::health::health;
use crate::handlers::iss::{last_iss, trigger_iss, iss_trend};
use crate::handlers::osdr::{osdr_sync, osdr_list};
use crate::handlers::space::{space_latest, space_refresh, space_summary};

pub fn create_router(state: AppState) -> Router {
    Router::new()
        // общее
        .route("/health", get(health))
        .with_state(state.clone())
        // ISS
        .route("/last", get(last_iss))
        .route("/fetch", get(trigger_iss))
        .route("/iss/trend", get(iss_trend))
        // OSDR
        .route("/osdr/sync", get(osdr_sync))
        .route("/osdr/list", get(osdr_list))
        // Space cache
        .route("/space/:src/latest", get(space_latest))
        .route("/space/refresh", get(space_refresh))
        .route("/space/summary", get(space_summary))
        .with_state(state)
}

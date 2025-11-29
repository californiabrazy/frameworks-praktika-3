use axum::{
    extract::{Path, Query, State},
    Json,
};
use serde_json::Value;
use std::collections::HashMap;
use crate::config::AppState;
use crate::domain::{ApiError, SpaceSummary};
use crate::repo::{IssRepo, CacheRepo};
use crate::services;

pub async fn space_latest(Path(src): Path<String>, State(st): State<AppState>) -> Result<Json<Value>, ApiError> {
    match CacheRepo::get_latest(&st.pool, &src).await {
        Ok(Some(item)) => Ok(Json(serde_json::json!({
            "source": item.source,
            "fetched_at": item.fetched_at,
            "payload": item.payload
        }))),
        Ok(None) => Ok(Json(serde_json::json!({ "source": src, "message":"no data" }))),
        Err(e) => Err(ApiError::InternalServerError(e.to_string())),
    }
}

pub async fn space_refresh(Query(q): Query<HashMap<String, String>>, State(st): State<AppState>) -> Result<Json<Value>, ApiError> {
    let list = q.get("src").cloned().unwrap_or_else(|| "apod,neo,flr,cme,spacex".to_string());
    let mut done = Vec::new();
    for s in list.split(',').map(|x| x.trim().to_lowercase()) {
        match s.as_str() {
            "apod"   => { let _ = services::fetch_apod(&st).await;       done.push("apod"); }
            "neo"    => { let _ = services::fetch_neo_feed(&st).await;   done.push("neo"); }
            "flr"    => { let _ = services::fetch_donki_flr(&st).await;  done.push("flr"); }
            "cme"    => { let _ = services::fetch_donki_cme(&st).await;  done.push("cme"); }
            "spacex" => { let _ = services::fetch_spacex_next(&st).await; done.push("spacex"); }
            _ => {}
        }
    }
    Ok(Json(serde_json::json!({ "refreshed": done })))
}

pub async fn space_summary(State(st): State<AppState>) -> Result<Json<SpaceSummary>, ApiError> {
    let apod   = CacheRepo::get_latest_as_value(&st.pool, "apod").await;
    let neo    = CacheRepo::get_latest_as_value(&st.pool, "neo").await;
    let flr    = CacheRepo::get_latest_as_value(&st.pool, "flr").await;
    let cme    = CacheRepo::get_latest_as_value(&st.pool, "cme").await;
    let spacex = CacheRepo::get_latest_as_value(&st.pool, "spacex").await;

    let iss = IssRepo::get_last(&st.pool).await.ok().flatten()
        .map(|data| serde_json::json!({"at": data.fetched_at, "payload": data.payload}))
        .unwrap_or(Value::Null);

    let osdr_count = CacheRepo::get_osdr_count(&st.pool).await
        .map_err(|e| ApiError::InternalServerError(e.to_string()))?;

    Ok(Json(SpaceSummary {
        apod, neo, flr, cme, spacex, iss, osdr_count
    }))
}

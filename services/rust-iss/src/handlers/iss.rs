use axum::{
    extract::{Query, State},
    Json,
};
use serde_json::{json, Value};
use crate::config::AppState;
use crate::models::{ApiError, Trend};
use crate::repo::IssRepo;
use crate::services;

pub async fn last_iss(State(st): State<AppState>) -> Result<Json<Value>, ApiError> {
    let mut redis = st.redis.clone();
    match IssRepo::get_last(&st.pool, &mut redis).await {
        Ok(Some(data)) => Ok(Json(serde_json::json!({
            "id": data.id,
            "fetched_at": data.fetched_at,
            "source_url": data.source_url,
            "payload": data.payload
        }))),
        Ok(None) => Ok(Json(serde_json::json!({"message":"no data"}))),
        Err(e) => Err(ApiError::InternalServerError(e.to_string())),
    }
}

pub async fn trigger_iss(State(st): State<AppState>) -> Result<Json<Value>, ApiError> {
    let mut redis = st.redis.clone();
    services::fetch_and_store_iss(&st.pool, &st.fallback_url, &mut redis).await
        .map_err(|e| ApiError::InternalServerError(e.to_string()))?;
    last_iss(State(st)).await
}

#[derive(serde::Deserialize)]
pub struct TrendQuery {
    limit: Option<usize>,
}

pub async fn iss_trend(
    Query(query): Query<TrendQuery>,
    State(st): State<AppState>,
) -> Result<Json<Value>, ApiError> {
    if let Some(limit) = query.limit {
        if limit > 2 {
            // Return points for trajectory
            let rows = IssRepo::get_last_n_for_trend(&st.pool, limit).await
                .map_err(|e| ApiError::InternalServerError(e.to_string()))?;

            let points: Vec<Value> = rows.into_iter().rev().map(|(at, payload)| {
                json!({
                    "lat": payload["latitude"],
                    "lon": payload["longitude"],
                    "at": at.to_rfc3339(),
                    "velocity": payload["velocity"],
                    "altitude": payload["altitude"]
                })
            }).collect();

            return Ok(Json(json!({ "points": points })));
        }
    }

    // Default: return Trend struct for last two points
    let rows = IssRepo::get_last_two_for_trend(&st.pool).await
        .map_err(|e| ApiError::InternalServerError(e.to_string()))?;

    if rows.len() < 2 {
        return Ok(Json(json!(Trend {
            movement: false, delta_km: 0.0, dt_sec: 0.0, velocity_kmh: None,
            from_time: None, to_time: None,
            from_lat: None, from_lon: None, to_lat: None, to_lon: None
        })));
    }

    let t2 = rows[0].0;
    let t1 = rows[1].0;
    let p2 = &rows[0].1;
    let p1 = &rows[1].1;

    let lat1 = num(&p1["latitude"]);
    let lon1 = num(&p1["longitude"]);
    let lat2 = num(&p2["latitude"]);
    let lon2 = num(&p2["longitude"]);
    let v2 = num(&p2["velocity"]);

    let mut delta_km = 0.0;
    let mut movement = false;
    if let (Some(a1), Some(o1), Some(a2), Some(o2)) = (lat1, lon1, lat2, lon2) {
        delta_km = haversine_km(a1, o1, a2, o2);
        movement = delta_km > 0.1;
    }
    let dt_sec = (t2 - t1).num_milliseconds() as f64 / 1000.0;

    Ok(Json(json!(Trend {
        movement,
        delta_km,
        dt_sec,
        velocity_kmh: v2,
        from_time: Some(t1),
        to_time: Some(t2),
        from_lat: lat1, from_lon: lon1, to_lat: lat2, to_lon: lon2,
    })))
}

pub fn num(v: &Value) -> Option<f64> {
    if let Some(x) = v.as_f64() { return Some(x); }
    if let Some(s) = v.as_str() { return s.parse::<f64>().ok(); }
    None
}

pub fn haversine_km(lat1: f64, lon1: f64, lat2: f64, lon2: f64) -> f64 {
    let rlat1 = lat1.to_radians();
    let rlat2 = lat2.to_radians();
    let dlat = (lat2 - lat1).to_radians();
    let dlon = (lon2 - lon1).to_radians();
    let a = (dlat / 2.0).sin().powi(2) + rlat1.cos() * rlat2.cos() * (dlon / 2.0).sin().powi(2);
    let c = 2.0 * a.sqrt().atan2((1.0 - a).sqrt());
    6371.0 * c
}
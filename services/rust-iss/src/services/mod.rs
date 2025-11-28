use chrono::{DateTime, NaiveDateTime, TimeZone, Utc};
use serde_json::Value;
use sqlx::PgPool;
use crate::clients::{NasaClient, IssClient, SpacexClient};
use crate::config::AppState;
use crate::repo::{IssRepo, OsdrRepo, CacheRepo};

pub async fn fetch_and_store_iss(pool: &PgPool, url: &str) -> anyhow::Result<()> {
    let client = IssClient::new();
    let json = client.get(url).await?;
    IssRepo::insert(pool, url, json).await
}

pub async fn fetch_and_store_osdr(st: &AppState) -> anyhow::Result<usize> {
    let client = NasaClient::new(st.nasa_key.clone());
    let resp = client.get(&st.nasa_url, &[]).await?;
    let items = if let Some(a) = resp.as_array() { a.clone() }
        else if let Some(v) = resp.get("items").and_then(|x| x.as_array()) { v.clone() }
        else if let Some(v) = resp.get("results").and_then(|x| x.as_array()) { v.clone() }
        else { vec![resp.clone()] };

    let mut written = 0usize;
    for item in items {
        let id = s_pick(&item, &["dataset_id","id","uuid","studyId","accession","osdr_id"]);
        let title = s_pick(&item, &["title","name","label"]);
        let status = s_pick(&item, &["status","state","lifecycle"]);
        let updated = t_pick(&item, &["updated","updated_at","modified","lastUpdated","timestamp"]);
        OsdrRepo::upsert(&st.pool, id, title, status, updated, item).await?;
        written += 1;
    }
    Ok(written)
}

// APOD
pub async fn fetch_apod(st: &AppState) -> anyhow::Result<()> {
    let client = NasaClient::new(st.nasa_key.clone());
    let url = "https://api.nasa.gov/planetary/apod";
    let json = client.get(url, &[("thumbs","true")]).await?;
    CacheRepo::insert(&st.pool, "apod", json).await
}

// NeoWs
pub async fn fetch_neo_feed(st: &AppState) -> anyhow::Result<()> {
    let today = Utc::now().date_naive();
    let start = today - chrono::Days::new(2);
    let url = "https://api.nasa.gov/neo/rest/v1/feed";
    let client = NasaClient::new(st.nasa_key.clone());
    let json = client.get(url, &[
        ("start_date", &start.to_string()),
        ("end_date", &today.to_string()),
    ]).await?;
    CacheRepo::insert(&st.pool, "neo", json).await
}

// DONKI объединённая
pub async fn fetch_donki(st: &AppState) -> anyhow::Result<()> {
    let _ = fetch_donki_flr(st).await;
    let _ = fetch_donki_cme(st).await;
    Ok(())
}

pub async fn fetch_donki_flr(st: &AppState) -> anyhow::Result<()> {
    let (from,to) = last_days(5);
    let url = "https://api.nasa.gov/DONKI/FLR";
    let client = NasaClient::new(st.nasa_key.clone());
    let json = client.get(url, &[("startDate", &from), ("endDate", &to)]).await?;
    CacheRepo::insert(&st.pool, "flr", json).await
}

pub async fn fetch_donki_cme(st: &AppState) -> anyhow::Result<()> {
    let (from,to) = last_days(5);
    let url = "https://api.nasa.gov/DONKI/CME";
    let client = NasaClient::new(st.nasa_key.clone());
    let json = client.get(url, &[("startDate", &from), ("endDate", &to)]).await?;
    CacheRepo::insert(&st.pool, "cme", json).await
}

// SpaceX
pub async fn fetch_spacex_next(st: &AppState) -> anyhow::Result<()> {
    let client = SpacexClient::new();
    let json = client.get_next_launch().await?;
    CacheRepo::insert(&st.pool, "spacex", json).await
}

fn last_days(n: i64) -> (String,String) {
    let to = Utc::now().date_naive();
    let from = to - chrono::Days::new(n as u64);
    (from.to_string(), to.to_string())
}

fn s_pick(v: &Value, keys: &[&str]) -> Option<String> {
    for k in keys {
        if let Some(x) = v.get(*k) {
            if let Some(s) = x.as_str() { if !s.is_empty() { return Some(s.to_string()); } }
            else if x.is_number() { return Some(x.to_string()); }
        }
    }
    None
}

fn t_pick(v: &Value, keys: &[&str]) -> Option<DateTime<Utc>> {
    for k in keys {
        if let Some(x) = v.get(*k) {
            if let Some(s) = x.as_str() {
                if let Ok(dt) = s.parse::<DateTime<Utc>>() { return Some(dt); }
                if let Ok(ndt) = NaiveDateTime::parse_from_str(s, "%Y-%m-%d %H:%M:%S") {
                    return Some(Utc.from_utc_datetime(&ndt));
                }
            } else if let Some(n) = x.as_i64() {
                return Some(Utc.timestamp_opt(n, 0).single().unwrap_or_else(Utc::now));
            }
        }
    }
    None
}

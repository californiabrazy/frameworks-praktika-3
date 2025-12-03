use serde_json::Value;
use crate::clients::NasaClient;
use crate::config::AppState;
use crate::repo::OsdrRepo;
use crate::services::helpers::{s_pick, t_pick};

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

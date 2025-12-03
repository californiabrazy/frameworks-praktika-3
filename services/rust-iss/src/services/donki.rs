use crate::clients::NasaClient;
use crate::config::AppState;
use crate::repo::CacheRepo;
use crate::services::helpers::last_days;

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

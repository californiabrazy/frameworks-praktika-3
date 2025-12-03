use chrono::Utc;
use crate::clients::NasaClient;
use crate::config::AppState;
use crate::repo::CacheRepo;

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

use crate::clients::NasaClient;
use crate::config::AppState;
use crate::repo::CacheRepo;

pub async fn fetch_apod(st: &AppState) -> anyhow::Result<()> {
    let client = NasaClient::new(st.nasa_key.clone());
    let url = "https://api.nasa.gov/planetary/apod";
    let json = client.get(url, &[("thumbs","true")]).await?;
    CacheRepo::insert(&st.pool, "apod", json).await
}

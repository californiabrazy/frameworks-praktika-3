use crate::clients::SpacexClient;
use crate::config::AppState;
use crate::repo::CacheRepo;

pub async fn fetch_spacex_next(st: &AppState) -> anyhow::Result<()> {
    let client = SpacexClient::new();
    let json = client.get_next_launch().await?;
    CacheRepo::insert(&st.pool, "spacex", json).await
}

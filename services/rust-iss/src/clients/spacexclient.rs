use std::time::Duration;
use reqwest::Client;
use serde_json::Value;

pub struct SpacexClient {
    client: Client,
}

impl SpacexClient {
    pub fn new() -> Self {
        let client = Client::builder().timeout(Duration::from_secs(30)).build().unwrap();
        Self { client }
    }

    pub async fn get_next_launch(&self) -> anyhow::Result<Value> {
        let url = "https://api.spacexdata.com/v4/launches/next";
        let resp = self.client.get(url).send().await?;
        let json: Value = resp.json().await?;
        Ok(json)
    }
}

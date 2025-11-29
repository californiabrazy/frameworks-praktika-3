use std::time::Duration;
use reqwest::Client;
use serde_json::Value;

pub struct IssClient {
    client: Client,
}

impl IssClient {
    pub fn new() -> Self {
        let client = Client::builder().timeout(Duration::from_secs(20)).build().unwrap();
        Self { client }
    }

    pub async fn get(&self, url: &str) -> anyhow::Result<Value> {
        let resp = self.client.get(url).send().await?;
        let json: Value = resp.json().await?;
        Ok(json)
    }
}

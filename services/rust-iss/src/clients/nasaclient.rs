use std::time::Duration;
use reqwest::Client;
use serde_json::Value;

pub struct NasaClient {
    client: Client,
    api_key: String,
}

impl NasaClient {
    pub fn new(api_key: String) -> Self {
        let client = Client::builder().timeout(Duration::from_secs(30)).build().unwrap();
        Self { client, api_key }
    }

    pub async fn get(&self, url: &str, query: &[(&str, &str)]) -> anyhow::Result<Value> {
        let mut req = self.client.get(url);
        for (k, v) in query {
            req = req.query(&[(k, v)]);
        }
        if !self.api_key.is_empty() {
            req = req.query(&[("api_key", &self.api_key)]);
        }
        let resp = req.send().await?;
        if !resp.status().is_success() {
            anyhow::bail!("Request failed with status: {}", resp.status());
        }
        let json: Value = resp.json().await?;
        Ok(json)
    }
}

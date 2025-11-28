use std::time::Duration;
use reqwest::Client;
use serde_json::Value;
use crate::config::AppState;

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

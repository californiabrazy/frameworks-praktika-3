use std::time::Duration;
use reqwest::Client;
use reqwest_retry::{policies::ExponentialBackoff, RetryTransientMiddleware};
use reqwest_middleware::ClientBuilder;
use serde_json::Value;

use crate::clients::rate_limiter::CustomRateLimiter;

pub struct NasaClient {
    client: reqwest_middleware::ClientWithMiddleware,
    api_key: String,
}

impl NasaClient {
    pub fn new(api_key: String) -> Self {
        let retry_policy = ExponentialBackoff::builder().build_with_max_retries(3);
        let rate_limiter = CustomRateLimiter::new();
        let base_client = Client::builder().timeout(Duration::from_secs(30)).build().unwrap();
        let client = ClientBuilder::new(base_client)
            .with(RetryTransientMiddleware::new_with_policy(retry_policy))
            .with(rate_limiter)
            .build();
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

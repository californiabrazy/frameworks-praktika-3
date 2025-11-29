use std::time::Duration;
use reqwest::Client;
use reqwest_retry::{policies::ExponentialBackoff, RetryTransientMiddleware};
use reqwest_middleware::ClientBuilder;
use serde_json::Value;

use crate::clients::rate_limiter::CustomRateLimiter;

pub struct IssClient {
    client: reqwest_middleware::ClientWithMiddleware,
}

impl IssClient {
    pub fn new() -> Self {
        let retry_policy = ExponentialBackoff::builder().build_with_max_retries(3);
        let rate_limiter = CustomRateLimiter::new();

        let client = ClientBuilder::new(
            Client::builder()
                .timeout(Duration::from_secs(20))
                .build()
                .unwrap()
        )
        .with(RetryTransientMiddleware::new_with_policy(retry_policy))
        .with(rate_limiter)
        .build();

        Self { client }
    }

    pub async fn get(&self, url: &str) -> anyhow::Result<Value> {
        let resp = self.client.get(url).send().await?;
        Ok(resp.json().await?)
    }
}

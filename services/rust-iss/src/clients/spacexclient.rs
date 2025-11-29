use std::time::Duration;
use reqwest::Client;
use reqwest_retry::{policies::ExponentialBackoff, RetryTransientMiddleware};
use reqwest_middleware::ClientBuilder;
use serde_json::Value;

use crate::clients::rate_limiter::CustomRateLimiter;

pub struct SpacexClient {
    client: reqwest_middleware::ClientWithMiddleware,
}

impl SpacexClient {
    pub fn new() -> Self {
        let retry_policy = ExponentialBackoff::builder().build_with_max_retries(3);
        let rate_limiter = CustomRateLimiter::new();

        let client = ClientBuilder::new(
            Client::builder()
                .timeout(Duration::from_secs(30))
                .build()
                .unwrap()
        )
        .with(RetryTransientMiddleware::new_with_policy(retry_policy))
        .with(rate_limiter)
        .build();

        Self { client }
    }

    pub async fn get_next_launch(&self) -> anyhow::Result<Value> {
        let resp = self.client.get("https://api.spacexdata.com/v4/launches/next")
            .send().await?;

        Ok(resp.json().await?)
    }
}

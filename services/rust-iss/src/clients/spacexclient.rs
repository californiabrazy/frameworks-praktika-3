use std::time::Duration;
use reqwest::Client;
use reqwest_retry::{policies::ExponentialBackoff, RetryTransientMiddleware};
use reqwest_middleware::ClientBuilder;
use serde_json::Value;

pub struct SpacexClient {
    client: reqwest_middleware::ClientWithMiddleware,
}

impl SpacexClient {
    pub fn new() -> Self {
        let retry_policy = ExponentialBackoff::builder().build_with_max_retries(3);
        let client = ClientBuilder::new(Client::builder().timeout(Duration::from_secs(30)).build().unwrap())
            .with(RetryTransientMiddleware::new_with_policy(retry_policy))
            .build();
        Self { client }
    }

    pub async fn get_next_launch(&self) -> anyhow::Result<Value> {
        let url = "https://api.spacexdata.com/v4/launches/next";
        let resp = self.client.get(url).send().await?;
        let json: Value = resp.json().await?;
        Ok(json)
    }
}

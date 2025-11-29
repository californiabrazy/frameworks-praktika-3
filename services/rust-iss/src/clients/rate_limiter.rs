use std::num::NonZeroU32;
use std::sync::Arc;
use reqwest_middleware::Middleware;
use reqwest::Request;
use reqwest_middleware::Next;
use task_local_extensions::Extensions;
use governor::{RateLimiter, Quota, clock::DefaultClock, state::{InMemoryState, NotKeyed}};

pub struct CustomRateLimiter {
    limiter: Arc<RateLimiter<NotKeyed, InMemoryState, DefaultClock>>,
}

impl CustomRateLimiter {
    pub fn new() -> Self {
        let limiter = RateLimiter::direct(
            Quota::per_second(NonZeroU32::new(10).unwrap())
        );
        Self {
            limiter: Arc::new(limiter),
        }
    }
}

#[async_trait::async_trait]
impl Middleware for CustomRateLimiter {
    async fn handle(
        &self,
        req: Request,
        extensions: &mut Extensions,
        next: Next<'_>,
    ) -> reqwest_middleware::Result<reqwest::Response> {
        self.limiter.until_ready().await;
        next.run(req, extensions).await
    }
}

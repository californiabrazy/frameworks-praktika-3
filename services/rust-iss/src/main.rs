mod config;
mod repo;
mod clients;
mod handlers;
mod services;
mod routes;
mod models;

use axum::serve;
use tokio::net::TcpListener;

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    let state = config::AppState::new().await?;
    state.start_background_tasks();

    let app = routes::create_router(state);

    let listener = TcpListener::bind("0.0.0.0:3000").await?;
    println!("Listening on {}", listener.local_addr()?);
    serve(listener, app).await?;
    Ok(())
}

use axum_test::TestServer;
use rust_iss::routes;
use rust_iss::config::AppState;
use sqlx::PgPool;
use redis::aio::ConnectionManager;

// Mock AppState for testing
async fn create_mock_state() -> AppState {
    // For testing, use test DB and Redis URLs from env or defaults
    let db_url = "postgres://testuser:testpass@localhost:5435/testdb".to_string();
    let redis_url = std::env::var("TEST_REDIS_URL").or_else(|_| std::env::var("REDIS_URL")).unwrap_or_else(|_| "redis://localhost:6379".to_string());

    let pool = PgPool::connect(&db_url).await.expect("Failed to connect to test DB");
    let redis_client = redis::Client::open(redis_url).expect("Failed to open Redis client");
    let redis = ConnectionManager::new(redis_client).await.expect("Failed to create Redis connection manager");

    AppState {
        pool,
        redis,
        nasa_url: "https://test.nasa.api".to_string(),
        nasa_key: "test_key".to_string(),
        fallback_url: "https://test.iss.api".to_string(),
        every_osdr: 600,
        every_iss: 120,
        every_apod: 43200,
        every_neo: 7200,
        every_donki: 3600,
        every_spacex: 3600,
    }
}

#[tokio::test]
async fn test_health() {
    let state = create_mock_state().await;
    let app = routes::create_router(state);
    let server = TestServer::new(app).unwrap();

    let response = server.get("/health").await;

    response.assert_status_ok();
    let json = response.json::<serde_json::Value>();
    assert_eq!(json["status"], "ok");
}

#[tokio::test]
async fn test_trigger_iss() {
    let state = create_mock_state().await;
    let app = routes::create_router(state);
    let server = TestServer::new(app).unwrap();

    let response = server.get("/fetch").await;

    // Assuming it returns the last data or error; adjust based on logic
    response.assert_status_ok();
}

#[tokio::test]
async fn test_iss_trend_default() {
    let state = create_mock_state().await;
    let app = routes::create_router(state);
    let server = TestServer::new(app).unwrap();

    let response = server.get("/iss/trend").await;

    response.assert_status_ok();
    // Check for Trend structure
}

#[tokio::test]
async fn test_osdr_sync() {
    let state = create_mock_state().await;
    let app = routes::create_router(state);
    let server = TestServer::new(app).unwrap();

    let response = server.get("/osdr/sync").await;

    response.assert_status_ok();
}

#[tokio::test]
async fn test_osdr_list() {
    let state = create_mock_state().await;
    let app = routes::create_router(state);
    let server = TestServer::new(app).unwrap();

    let response = server.get("/osdr/list").await;

    response.assert_status_ok();
}

#[tokio::test]
async fn test_space_latest_apod() {
    let state = create_mock_state().await;
    let app = routes::create_router(state);
    let server = TestServer::new(app).unwrap();

    let response = server.get("/space/apod/latest").await;

    response.assert_status_ok();
}

#[tokio::test]
async fn test_space_latest_neo() {
    let state = create_mock_state().await;
    let app = routes::create_router(state);
    let server = TestServer::new(app).unwrap();

    let response = server.get("/space/neo/latest").await;

    response.assert_status_ok();
}

#[tokio::test]
async fn test_space_latest_donki() {
    let state = create_mock_state().await;
    let app = routes::create_router(state);
    let server = TestServer::new(app).unwrap();

    let response = server.get("/space/donki/latest").await;

    response.assert_status_ok();
}

#[tokio::test]
async fn test_space_refresh() {
    let state = create_mock_state().await;
    let app = routes::create_router(state);
    let server = TestServer::new(app).unwrap();

    let response = server.get("/space/refresh").await;

    response.assert_status_ok();
}

#[tokio::test]
async fn test_space_summary() {
    let state = create_mock_state().await;
    let app = routes::create_router(state);
    let server = TestServer::new(app).unwrap();

    let response = server.get("/space/summary").await;

    response.assert_status_ok();
}

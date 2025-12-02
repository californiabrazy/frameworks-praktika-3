use super::{last_days, s_pick, t_pick};
use chrono::{DateTime, NaiveDateTime, TimeZone, Utc};
use serde_json::Value;

#[test]
fn test_last_days() {
    let (from, to) = last_days(5);
    let to_date = Utc::now().date_naive();
    let from_date = to_date - chrono::Days::new(5);
    assert_eq!(from, from_date.to_string());
    assert_eq!(to, to_date.to_string());
}

#[test]
fn test_s_pick_string() {
    let json: Value = serde_json::from_str(r#"{"title": "Test Title", "name": "Test Name"}"#).unwrap();
    let result = s_pick(&json, &["title", "name"]);
    assert_eq!(result, Some("Test Title".to_string()));
}

#[test]
fn test_s_pick_number() {
    let json: Value = serde_json::from_str(r#"{"id": 123, "uuid": "abc"}"#).unwrap();
    let result = s_pick(&json, &["id", "uuid"]);
    assert_eq!(result, Some("123".to_string()));
}

#[test]
fn test_s_pick_empty_string() {
    let json: Value = serde_json::from_str(r#"{"title": "", "name": "Test"}"#).unwrap();
    let result = s_pick(&json, &["title", "name"]);
    assert_eq!(result, Some("Test".to_string()));
}

#[test]
fn test_s_pick_no_match() {
    let json: Value = serde_json::from_str(r#"{"other": "value"}"#).unwrap();
    let result = s_pick(&json, &["title", "name"]);
    assert_eq!(result, None);
}

#[test]
fn test_t_pick_datetime_string() {
    let json: Value = serde_json::from_str(r#"{"updated": "2023-01-01T00:00:00Z"}"#).unwrap();
    let result = t_pick(&json, &["updated"]);
    let expected = DateTime::parse_from_rfc3339("2023-01-01T00:00:00Z").unwrap().with_timezone(&Utc);
    assert_eq!(result, Some(expected));
}

#[test]
fn test_t_pick_naive_datetime_string() {
    let json: Value = serde_json::from_str(r#"{"updated": "2023-01-01 00:00:00"}"#).unwrap();
    let result = t_pick(&json, &["updated"]);
    let expected = Utc.from_utc_datetime(&NaiveDateTime::parse_from_str("2023-01-01 00:00:00", "%Y-%m-%d %H:%M:%S").unwrap());
    assert_eq!(result, Some(expected));
}

#[test]
fn test_t_pick_timestamp() {
    let json: Value = serde_json::from_str(r#"{"timestamp": 1672531200}"#).unwrap();
    let result = t_pick(&json, &["timestamp"]);
    let expected = Utc.timestamp_opt(1672531200, 0).single().unwrap();
    assert_eq!(result, Some(expected));
}

#[test]
fn test_t_pick_no_match() {
    let json: Value = serde_json::from_str(r#"{"other": "value"}"#).unwrap();
    let result = t_pick(&json, &["updated", "timestamp"]);
    assert_eq!(result, None);
}
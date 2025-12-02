use serde_json::json;
use super::iss::{num, haversine_km};


#[test]
fn test_num_f64() {
    let value = json!(42.5);
    assert_eq!(num(&value), Some(42.5));
}

#[test]
fn test_num_i64_converted_to_f64() {
    let value = json!(42); // целое число
    assert_eq!(num(&value), Some(42.0));
}

#[test]
fn test_num_from_valid_string() {
    let value = json!("42.5");
    assert_eq!(num(&value), Some(42.5));
}

#[test]
fn test_num_from_invalid_string() {
    let value = json!("not a number");
    assert_eq!(num(&value), None);
}

#[test]
fn test_num_from_null() {
    let value = json!(null);
    assert_eq!(num(&value), None);
}

#[test]
fn test_num_from_bool() {
    let value = json!(true);
    assert_eq!(num(&value), None);
}

#[test]
fn test_num_from_object() {
    let value = json!({"key": "value"});
    assert_eq!(num(&value), None);
}

#[test]
fn test_num_from_array() {
    let value = json!([1, 2, 3]);
    assert_eq!(num(&value), None);
}

#[test]
fn test_num_from_scientific_notation() {
    let value = json!("1.23e4");
    assert_eq!(num(&value), Some(12300.0));
}

// Тесты для функции haversine_km
#[test]
fn test_haversine_same_point() {
    // Расстояние от точки до самой себя должно быть 0
    let result = haversine_km(55.7558, 37.6173, 55.7558, 37.6173);
    assert!(result.abs() < 0.0001); // Позволяем небольшую погрешность
    assert_eq!(result, 0.0);
}

#[test]
fn test_haversine_equator() {
    // Расстояние между двумя точками на экваторе
    // 1 градус долготы на экваторе ≈ 111.32 км
    let result = haversine_km(0.0, 0.0, 0.0, 1.0);
    let expected = 111.319; // Приблизительное значение
    let diff = (result - expected).abs();
    assert!(diff < 1.0, "Expected {}, got {}, diff {}", expected, result, diff);
}

#[test]
fn test_haversine_north_pole_to_equator() {
    // Расстояние от северного полюса до экватора
    let result = haversine_km(90.0, 0.0, 0.0, 0.0);
    let expected = 10007.5; // 1/4 длины меридиана ≈ 10,000 км
    let diff = (result - expected).abs();
    assert!(diff < 100.0, "Expected {}, got {}, diff {}", expected, result, diff);
}

#[test]
fn test_haversine_moscow_to_spb() {
    // Москва -> Санкт-Петербург
    let moscow_lat = 55.7558;
    let moscow_lon = 37.6173;
    let spb_lat = 59.9343;
    let spb_lon = 30.3351;
    
    let result = haversine_km(moscow_lat, moscow_lon, spb_lat, spb_lon);
    let expected = 634.0; // Приблизительное расстояние
    let diff = (result - expected).abs();
    assert!(diff < 10.0, "Expected {}, got {}, diff {}", expected, result, diff);
}

#[test]
fn test_haversine_negative_coordinates() {
    // Координаты в южном и западном полушариях
    let result = haversine_km(-33.8688, 151.2093, -37.8136, 144.9631);
    // Сидней -> Мельбурн
    let expected = 713.0; // Приблизительное расстояние
    let diff = (result - expected).abs();
    assert!(diff < 10.0, "Expected {}, got {}, diff {}", expected, result, diff);
}

#[test]
fn test_haversine_across_180_meridian() {
    // Через линию смены дат
    let result = haversine_km(0.0, 179.0, 0.0, -179.0);
    // Всего 2 градуса на экваторе
    let expected = 2.0 * 111.319;
    let diff = (result - expected).abs();
    assert!(diff < 1.0, "Expected {}, got {}, diff {}", expected, result, diff);
}

#[test]
fn test_haversine_large_distance() {
    // Нью-Йорк -> Лондон
    let ny_lat = 40.7128;
    let ny_lon = -74.0060;
    let london_lat = 51.5074;
    let london_lon = -0.1278;
    
    let result = haversine_km(ny_lat, ny_lon, london_lat, london_lon);
    let expected = 5570.0; // Приблизительное расстояние
    let diff = (result - expected).abs();
    assert!(diff < 100.0, "Expected {}, got {}, diff {}", expected, result, diff);
}

#[test]
fn test_haversine_very_small_distance() {
    // Очень близкие точки (1 метр)
    let result = haversine_km(55.7558, 37.6173, 55.755801, 37.617301);
    assert!(result < 0.1, "Distance should be less than 0.1 km, got {}", result);
}

// Параметризованные тесты (опционально, можно использовать crate rstest)
#[test]
fn test_haversine_symmetry() {
    // Расстояние должно быть симметричным: dist(A,B) == dist(B,A)
    let lat1 = 55.7558;
    let lon1 = 37.6173;
    let lat2 = 59.9343;
    let lon2 = 30.3351;
    
    let d1 = haversine_km(lat1, lon1, lat2, lon2);
    let d2 = haversine_km(lat2, lon2, lat1, lon1);
    
    assert!((d1 - d2).abs() < 0.000001, "Distance should be symmetric: {} != {}", d1, d2);
}

#[test]
fn test_haversine_triangle_inequality() {
    // Неравенство треугольника: dist(A,C) ≤ dist(A,B) + dist(B,C)
    let a_lat = 55.7558;
    let a_lon = 37.6173;
    let b_lat = 59.9343;
    let b_lon = 30.3351;
    let c_lat = 52.5200;
    let c_lon = 13.4050;
    
    let ab = haversine_km(a_lat, a_lon, b_lat, b_lon);
    let bc = haversine_km(b_lat, b_lon, c_lat, c_lon);
    let ac = haversine_km(a_lat, a_lon, c_lat, c_lon);
    
    assert!(ac <= ab + bc + 0.001, "Triangle inequality failed: {} > {} + {}", ac, ab, bc);
}
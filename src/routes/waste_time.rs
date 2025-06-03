use axum::response::IntoResponse;

pub async fn waste_time() -> impl IntoResponse {
    "You laze around for a while and generally waste time.".to_string()
}

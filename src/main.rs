use axum::Router;
use axum::response::IntoResponse;
use axum::routing::get;
use data::character::{Character, Strife};
use templates::HtmlTemplate;
use templates::overview::OverviewTemplate;
use tokio::net::TcpListener;
use tower_http::services::ServeDir;

mod data;
mod templates;

#[tokio::main]
async fn main() {
    tracing_subscriber::fmt()
        .with_env_filter("overseer_reboot=trace")
        .init();

    let app = Router::new()
        .route("/overview", get(overview))
        .nest_service("/static", ServeDir::new("static"));

    let listener = TcpListener::bind("0.0.0.0:8010").await.unwrap();
    axum::serve(listener, app).await.unwrap();
}

async fn overview() -> impl IntoResponse {
    HtmlTemplate(OverviewTemplate {
        character: Character {
            name: "John Doe".to_string(),
            aspect: "Time".to_string(),
            class: "Knight".to_string(),
            strife: Strife {
                power: 10,
                health: 75,
                max_health: 100,
                health_percent: 75.0,
                energy: 50,
                max_energy: 50,
                energy_percent: 100.0,
            },
            echeladder: 1,
            boondollars: 1000,
            is_in_medium: true,
            symbol: "/images/symbols/jade1.png".to_string(),
        },
        background: "".to_string(),
    })
}

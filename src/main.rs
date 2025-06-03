use axum::Router;
use axum::routing::{get, post};
use tokio::net::TcpListener;
use tower_http::services::ServeDir;

use crate::error::Result;
use crate::routes::character::colour::character_colour_post;
use crate::routes::character::dreamer::character_dreamer_post;
use crate::routes::character::gates::debug_clear;
use crate::routes::character::symbol::character_symbol_post;
use crate::routes::overview::overview_get;
use crate::routes::waste_time::waste_time;

mod achievement;
mod error;
mod routes;

#[tokio::main]
async fn main() -> Result<()> {
    tracing_subscriber::fmt()
        .with_env_filter("overseer_reboot=trace")
        .init();

    let app = Router::new()
        .route("/", get(async || "hey, you're on the wrong index page!"))
        .route("/overview", get(overview_get))
        .route("/character/colour", post(character_colour_post))
        .route("/character/dreamer", post(character_dreamer_post))
        .route("/character/symbol", post(character_symbol_post))
        .route("/character/debug-clear", post(debug_clear))
        .route("/waste-time", post(waste_time))
        .nest_service("/static", ServeDir::new("static"));

    let listener = TcpListener::bind("0.0.0.0:8010").await?;
    axum::serve(listener, app).await?;

    Ok(())
}

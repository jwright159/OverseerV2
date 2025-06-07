use axum::routing::{get, post};
use axum::{Extension, Router};
use sqlx::MySqlPool;
use tokio::net::TcpListener;
use tower_http::services::ServeDir;
use tower_sessions::SessionManagerLayer;
use tracing::debug;

use crate::error::Result;
use crate::php::PhpStore;
use crate::routes::character::colour::character_colour_post;
use crate::routes::character::dreamer::character_dreamer_post;
use crate::routes::character::gates::debug_clear;
use crate::routes::character::symbol::character_symbol_post;
use crate::routes::overview::overview_get;
use crate::routes::waste_time::waste_time;

mod achievement;
mod error;
mod php;
mod routes;

#[tokio::main]
async fn main() -> Result<()> {
    tracing_subscriber::fmt()
        .with_env_filter(
            "debug,overseer_reboot=trace,sqlx::query=warn,sqlx_mysql::connection::tls=warn",
        )
        .init();

    dotenvy::dotenv()?;
    debug!(
        "loaded .env, sessions at {}",
        std::env::var("OVERSEER_PHP_SESSIONS_ROOT")?
    );
    let db = MySqlPool::connect("mysql://root:@localhost/overseerv2").await?;

    let session_layer = SessionManagerLayer::new(PhpStore).with_name("PHPSESSID");

    let app = Router::new()
        .route("/", get(async || "hey, you're on the wrong index page!"))
        .route("/overview", get(overview_get))
        .route("/character/colour", post(character_colour_post))
        .route("/character/dreamer", post(character_dreamer_post))
        .route("/character/symbol", post(character_symbol_post))
        .route("/character/debug-clear", post(debug_clear))
        .route("/waste-time", post(waste_time))
        .nest_service("/static", ServeDir::new("static"))
        .layer(session_layer)
        .layer(Extension(db));

    let listener = TcpListener::bind("0.0.0.0:8010").await?;
    axum::serve(listener, app).await?;

    Ok(())
}

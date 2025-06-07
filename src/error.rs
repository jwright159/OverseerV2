use std::array::TryFromSliceError;

use axum::http::StatusCode;
use axum::response::{IntoResponse, Response};
use tracing::error;

pub type Result<T> = std::result::Result<T, Error>;

#[derive(Debug, thiserror::Error)]
pub enum Error {
    #[error("failed to parse key: {0}")]
    TryFromSlice(#[from] TryFromSliceError),
    #[error("askama failed: {0}")]
    Askama(#[from] askama::Error),
    #[error("tokio join failed: {0}")]
    TokioJoin(#[from] tokio::task::JoinError),
    #[error("io error: {0}")]
    IO(#[from] std::io::Error),
    #[error("extension rejected: {0}")]
    ExtensionRejected(#[from] axum::extract::rejection::ExtensionRejection),
    #[error("path rejected: {0}")]
    PathRejected(#[from] axum::extract::rejection::PathRejection),
    #[error("query rejected: {0}")]
    QueryRejected(#[from] axum::extract::rejection::QueryRejection),
    #[error("form rejected: {0}")]
    FormRejected(#[from] axum::extract::rejection::FormRejection),
    #[error("image: {0}")]
    Image(#[from] imagesize::ImageError),
    #[error("persist: {0}")]
    Persist(#[from] tempfile::PersistError),
    #[error("sqlx error: {0}")]
    Sqlx(#[from] sqlx::Error),
    #[error("var error: {0}")]
    Var(#[from] std::env::VarError),
    #[error("php error: {0}")]
    Php(#[from] crate::php::Error),
    #[error("parse int error: {0}")]
    ParseInt(#[from] std::num::ParseIntError),
    #[error("parse float error: {0}")]
    ParseFloat(#[from] std::num::ParseFloatError),
    #[error("dotenv error: {0}")]
    Dotenv(#[from] dotenvy::Error),
    #[error("session error: {0}")]
    Session(#[from] axum_login::tower_sessions::session::Error),
    #[error("invalid filename")]
    InvalidFilename,
    #[error("not logged in as a character")]
    NotLoggedInCharacter,
    #[error("strifer not found: {0}")]
    StriferNotFound(i64),
    #[error("character not found: {0}")]
    CharacterNotFound(i64),
    #[error("should have dreamer: {0}")]
    ShouldHaveDreamer(i64),
}

impl IntoResponse for Error {
    fn into_response(self) -> Response {
        error!(err = ?self, "responding with error");
        (StatusCode::INTERNAL_SERVER_ERROR, self.to_string()).into_response()
    }
}

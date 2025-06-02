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
}

impl IntoResponse for Error {
    fn into_response(self) -> Response {
        error!(err = ?self, "responding with error");
        (StatusCode::INTERNAL_SERVER_ERROR, self.to_string()).into_response()
    }
}

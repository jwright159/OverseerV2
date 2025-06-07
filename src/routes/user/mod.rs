use axum::extract::FromRequestParts;
use axum::http::request::Parts;
use axum::{Extension, RequestPartsExt as _};
use sqlx::MySqlPool;
use tower_sessions::Session;

use crate::error::{Error, Result};

#[derive(Debug, Clone)]
pub struct User {
    pub id: i64,
    pub username: String,
    pub password_hash: String,
}

impl User {
    pub async fn load(id: i64, db: &MySqlPool) -> Result<Option<Self>> {
        sqlx::query_as!(
            User,
            "SELECT id, username, password as password_hash FROM Users WHERE id = ?",
            id
        )
        .fetch_optional(db)
        .await
        .map_err(Error::Sqlx)
    }

    pub async fn load_by_username(username: &str, db: &MySqlPool) -> Result<Option<Self>> {
        sqlx::query_as!(
            User,
            "SELECT id, username, password as password_hash FROM Users WHERE username = ?",
            username
        )
        .fetch_optional(db)
        .await
        .map_err(Error::Sqlx)
    }
}

impl<S> FromRequestParts<S> for User
where
    S: Send + Sync,
{
    type Rejection = Error;

    async fn from_request_parts(req: &mut Parts, _state: &S) -> Result<Self> {
        let session = req
            .extract::<Session>()
            .await
            .map_err(|(_, err)| Error::Extract(err.to_string()))?;
        let Extension(db): Extension<MySqlPool> = req.extract().await?;

        let user_id = session
            .get::<i64>("userid")
            .await?
            .ok_or(Error::NotLoggedIn)?;
        let user = User::load(user_id, &db)
            .await?
            .ok_or(Error::UserNotFound(user_id))?;

        Ok(user)
    }
}

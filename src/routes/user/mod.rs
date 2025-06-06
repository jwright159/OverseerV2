use sqlx::MySqlPool;

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

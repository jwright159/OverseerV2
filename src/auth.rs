use std::collections::HashSet;

use axum_login::{AuthUser, AuthnBackend, AuthzBackend, UserId};
use password_auth::verify_password;
use return_ok::ok_some;
use serde::Deserialize;
use sqlx::MySqlPool;
use tracing::debug;

use crate::error::{Error, Result};
use crate::routes::user::User;

impl AuthUser for User {
    type Id = i64;

    fn id(&self) -> Self::Id {
        self.id
    }

    fn session_auth_hash(&self) -> &[u8] {
        self.password_hash.as_bytes()
    }
}

#[derive(Clone)]
pub struct Backend {
    db: MySqlPool,
}

impl Backend {
    pub fn new(db: MySqlPool) -> Self {
        Self { db }
    }
}

#[derive(Clone, Deserialize)]
pub struct Credentials {
    pub username: String,
    pub password: String,
    // pub next: Option<String>,
}

#[derive(Clone, PartialEq, Eq, Hash)]
pub enum Permission {}

impl AuthnBackend for Backend {
    type User = User;
    type Credentials = Credentials;
    type Error = Error;

    async fn authenticate(&self, creds: Self::Credentials) -> Result<Option<Self::User>> {
        debug!("Authenticating user: {}", creds.username);
        let user: Self::User = ok_some!(User::load_by_username(&creds.username, &self.db).await);
        debug!("Got user: {}", user.id);

        Ok(tokio::task::spawn_blocking(|| {
            if verify_password(creds.password, &user.password_hash).is_ok() {
                Some(user)
            } else {
                None
            }
        })
        .await?)
    }

    async fn get_user(&self, user_id: &UserId<Self>) -> Result<Option<Self::User>> {
        User::load(*user_id, &self.db).await
    }
}

impl AuthzBackend for Backend {
    type Permission = Permission;

    async fn get_user_permissions(&self, _user: &Self::User) -> Result<HashSet<Self::Permission>> {
        let permissions = HashSet::new();
        Ok(permissions)
    }
}

pub type AuthSession = axum_login::AuthSession<Backend>;

// This allows us to extract the "next" field from the query string. We use this
// to redirect after log in.
// #[derive(Debug, Deserialize)]
// pub struct NextUrl {
//     pub next: Option<String>,
// }
// this was copied in case we ever want to make a dedicated login page

use askama::Template;
use axum::extract::FromRequestParts;
use axum::http::request::Parts;
use axum::{Extension, RequestPartsExt as _};
use return_ok::some_or_return_ok;
use sqlx::MySqlPool;
use tower_sessions::Session;

use crate::error::{Error, Result};

pub mod colour;
pub mod dreamer;
pub mod gates;
pub mod symbol;

#[derive(Debug, Clone)]
pub struct Character {
    pub id: i64,
    pub name: String,
    pub aspect: String,
    pub class: String,
    pub strife: Strifer,
    pub echeladder: i64,
    pub boondollars: i64,
    pub symbol: String,
    pub colour: String,
    pub dreamer: Option<String>,
    pub land_1: Option<String>,
    pub land_2: Option<String>,
    pub grist_type: Option<Vec<String>>,
    pub consort: Option<String>,
    pub house_build: i64,
    pub achievements: Vec<String>,
    pub dreaming_status: String,
    pub gates_cleared: i64,
}

impl Character {
    pub async fn load(id: i64, db: &MySqlPool) -> Result<Option<Character>> {
        let sql = some_or_return_ok!(sqlx::query!(
            r#"SELECT id, name, aspect, class, wakeself, dreamself, dreamingstatus, echeladder, boondollars, symbol, colour, dreamer, land1, land2, grist_type, consort, house_build, achievements, gatescleared FROM Characters WHERE id = ?"#,
            id
        )
        .fetch_optional(db)
        .await
        .map_err(Error::Sqlx)?);

        let strife = if sql.dreamingstatus == "Awake" {
            Strifer::load(sql.wakeself, db)
                .await?
                .ok_or(Error::StriferNotFound(sql.wakeself))?
        } else {
            Strifer::load(sql.dreamself, db)
                .await?
                .ok_or(Error::StriferNotFound(sql.dreamself))?
        };

        Ok(Some(Character {
            id: sql.id as i64,
            name: sql.name,
            aspect: sql.aspect,
            class: sql.class,
            strife,
            echeladder: sql.echeladder as i64,
            boondollars: sql.boondollars,
            symbol: sql.symbol,
            colour: sql.colour,
            dreamer: match sql.dreamer.as_str() {
                "" => None,
                _ => Some(sql.dreamer),
            },
            land_1: match sql.land1.as_str() {
                "" => None,
                _ => Some(sql.land1),
            },
            land_2: match sql.land2.as_str() {
                "" => None,
                _ => Some(sql.land2),
            },
            grist_type: match sql.grist_type.as_str() {
                "" => None,
                _ => Some(sql.grist_type.split('|').map(String::from).collect()),
            },
            consort: match sql.consort.as_str() {
                "" => None,
                _ => Some(sql.consort),
            },
            house_build: sql.house_build as i64,
            achievements: sql.achievements.split('|').map(String::from).collect(),
            dreaming_status: sql.dreamingstatus,
            gates_cleared: sql.gatescleared as i64,
        }))
    }

    pub fn profile_string(&self) -> String {
        let template = ProfileStringTemplate {
            character: self.clone(),
        };
        match template.render() {
            Ok(html) => html,
            Err(_err) => "[ERROR RETRIEVING PLAYER ID]".to_string(),
        }
    }

    pub fn gates_reached(&self) -> usize {
        [100, 1100, 11100, 111100, 1111100, 11111100, 24000000]
            .into_iter()
            .filter(|g| self.house_build > *g)
            .count()
    }
}

impl<S> FromRequestParts<S> for Character
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

        let character_id = session
            .get::<String>("character")
            .await?
            .map(|s| s.parse::<i64>())
            .transpose()?
            .ok_or(Error::NotLoggedInCharacter)?;
        let character = Character::load(character_id, &db)
            .await?
            .ok_or(Error::CharacterNotFound(character_id))?;

        Ok(character)
    }
}

#[derive(Template)]
#[template(path = "partial/profile-string.html.jinja")]
pub struct ProfileStringTemplate {
    pub character: Character,
}

#[derive(Debug, Clone)]
pub struct Strifer {
    pub power: i64,
    pub health: i64,
    pub max_health: i64,
    pub energy: i64,
    pub max_energy: i64,
    pub description: String,
    pub echeladder: i64,
}

impl Strifer {
    pub async fn load(id: i64, db: &MySqlPool) -> Result<Option<Strifer>> {
        sqlx::query_as!(
			Strifer,
			r#"SELECT power, health, maxhealth as max_health, energy, maxenergy as max_energy, description, echeladder FROM Strifers WHERE id = ?"#,
			id
		)
		.fetch_optional(db)
		.await
		.map_err(Error::Sqlx)
    }

    pub fn health_percent(&self) -> f64 {
        if self.max_health == 0 {
            0.0
        } else {
            (self.health as f64 / self.max_health as f64) * 100.0
        }
    }

    pub fn energy_percent(&self) -> f64 {
        if self.max_energy == 0 {
            0.0
        } else {
            (self.energy as f64 / self.max_energy as f64) * 100.0
        }
    }
}

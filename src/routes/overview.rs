use askama::Template;
use axum::Extension;
use axum::response::IntoResponse;
use axum_login::tower_sessions::Session;
use sqlx::MySqlPool;

use crate::achievement::Achievement;
use crate::error::{Error, Result};
use crate::routes::HtmlTemplate;
use crate::routes::character::Character;
use crate::routes::character::colour::CharacterColourTemplate;
use crate::routes::character::dreamer::CharacterDreamerTemplate;
use crate::routes::character::gates::CharacterGatesTemplate;
use crate::routes::character::symbol::CharacterSymbolTemplate;

pub async fn overview_get(
    session: Session,
    Extension(db): Extension<MySqlPool>,
) -> Result<impl IntoResponse> {
    let character_id = session
        .get::<String>("character")
        .await?
        .map(|s| s.parse::<i64>())
        .transpose()?
        .ok_or(Error::NotLoggedInCharacter)?;
    let character = Character::load(character_id, &db)
        .await?
        .ok_or(Error::CharacterNotFound(character_id))?;

    Ok(HtmlTemplate(OverviewTemplate {
        character: character.clone(),
        server_player: None,
        background: if character.dreaming_status == "Awake" {
            "".to_string()
        } else {
            character
                .dreamer
                .clone()
                .ok_or(Error::ShouldHaveDreamer(character_id))?
        },
        announcements: vec![
            "Welcome to the game!".to_string(),
            "New update available!".to_string(),
        ],
        character_dreamer: CharacterDreamerTemplate {
            dreamer: character.dreamer.clone(),
            input: "".to_string(),
            error: None,
        },
        character_colour: CharacterColourTemplate {
            colour: character.colour.clone(),
        },
        character_symbol: CharacterSymbolTemplate {
            symbol: character.symbol.clone(),
            error: None,
        },
        character_gates: CharacterGatesTemplate {
            gates_reached: character.gates_reached(),
            gates_cleared: character.gates_cleared as usize,
        },
        achievements: crate::achievement::get_achievements(),
    }))
}

#[derive(Template)]
#[template(path = "overview.html.jinja")]
pub struct OverviewTemplate {
    pub character: Character,
    pub server_player: Option<Character>,
    pub background: String,
    pub announcements: Vec<String>,
    pub character_dreamer: CharacterDreamerTemplate,
    pub character_colour: CharacterColourTemplate,
    pub character_symbol: CharacterSymbolTemplate,
    pub character_gates: CharacterGatesTemplate,
    pub achievements: Vec<Achievement>,
}

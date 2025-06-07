use askama::Template;
use axum::response::IntoResponse;

use crate::achievement::Achievement;
use crate::error::{Error, Result};
use crate::routes::HtmlTemplate;
use crate::routes::character::Character;
use crate::routes::character::colour::CharacterColourTemplate;
use crate::routes::character::dreamer::CharacterDreamerTemplate;
use crate::routes::character::gates::CharacterGatesTemplate;
use crate::routes::character::symbol::CharacterSymbolTemplate;

pub async fn overview_get(character: Character) -> Result<impl IntoResponse> {
    Ok(HtmlTemplate(OverviewTemplate {
        character: character.clone(),
        server_player: None,
        background: if character.dreaming_status == "Awake" {
            "".to_string()
        } else {
            character
                .dreamer
                .clone()
                .ok_or(Error::ShouldHaveDreamer(character.id))?
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

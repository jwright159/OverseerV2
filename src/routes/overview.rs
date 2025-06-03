use askama::Template;
use axum::response::IntoResponse;

use crate::achievement::Achievement;
use crate::routes::HtmlTemplate;
use crate::routes::character::colour::CharacterColourTemplate;
use crate::routes::character::dreamer::CharacterDreamerTemplate;
use crate::routes::character::gates::CharacterGatesTemplate;
use crate::routes::character::symbol::CharacterSymbolTemplate;
use crate::routes::character::{Character, Strife};

pub async fn overview_get() -> impl IntoResponse {
    let character = Character {
        id: 1,
        name: "John Doe".to_string(),
        aspect: "Time".to_string(),
        class: "Knight".to_string(),
        strife: Strife {
            power: 10,
            health: 75,
            max_health: 100,
            health_percent: 75.0,
            energy: 50,
            max_energy: 50,
            energy_percent: 100.0,
            description: "my waking self".to_string(),
            echeladder: 1,
        },
        echeladder: 1,
        boondollars: 1000,
        symbol: "/images/symbols/jade1.png".to_string(),
        colour: "#00ff00".to_string(),
        dreamer: None,
        achievements: vec!["medium".to_string()],
        consort: None,
        grist_type: None,
        land_1: Some("Light".to_string()),
        land_2: Some("Rain".to_string()),
        house_build: 0,
    };
    HtmlTemplate(OverviewTemplate {
        character: character.clone(),
        server_player: None,
        background: "".to_string(),
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
            gates_reached: 1,
            gates_cleared: 0,
        },
        achievements: crate::achievement::get_achievements(),
    })
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

use askama::Template;
use axum::response::IntoResponse;

use crate::routes::HtmlTemplate;
use crate::routes::character::dreamer::CharacterDreamerTemplate;
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
        },
        echeladder: 1,
        boondollars: 1000,
        symbol: "/images/symbols/jade1.png".to_string(),
        colour: "#00ff00".to_string(),
        dreamer: None,
    };
    HtmlTemplate(OverviewTemplate {
        character: character.clone(),
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
    })
}

#[derive(Template)]
#[template(path = "overview.html.jinja")]
pub struct OverviewTemplate {
    pub character: Character,
    pub background: String,
    pub announcements: Vec<String>,
    pub character_dreamer: CharacterDreamerTemplate,
}

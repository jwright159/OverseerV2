use askama::Template;
use axum::Form;
use axum::response::IntoResponse;
use serde::{Deserialize, Serialize};

use crate::routes::HtmlTemplate;

pub async fn character_dreamer_post(Form(form): Form<CharacterDreamerPost>) -> impl IntoResponse {
    let character_dreamer: Option<String> = None;
    if let Some(dreamer) = character_dreamer {
        return HtmlTemplate(CharacterDreamerTemplate {
            dreamer: None,
            input: form.moon.clone(),
            error: Some(format!("You're already a {} dreamer!", dreamer)),
        });
    };

    match form.moon.as_str() {
        "Prospit" | "Derse" => HtmlTemplate(CharacterDreamerTemplate {
            dreamer: Some(form.moon.clone()),
            input: form.moon.clone(),
            error: None,
        }),
        "Space station" => HtmlTemplate(CharacterDreamerTemplate {
            dreamer: None,
            input: form.moon.clone(),
            error: Some("That's not a moon...".to_string()),
        }),
        "The Battlefield" | "Battlefield" | "Skaia" => HtmlTemplate(CharacterDreamerTemplate {
            dreamer: None,
            input: form.moon.clone(),
            error: Some("Nice try, but no.".to_string()),
        }),
        _ if form.moon.contains("Land") => HtmlTemplate(CharacterDreamerTemplate {
            dreamer: None,
            input: form.moon.clone(),
            error: Some("You can't start your dreamself off on a Land, sorry.".to_string()),
        }),
        _ => HtmlTemplate(CharacterDreamerTemplate {
            dreamer: None,
            input: form.moon.clone(),
            error: Some(
                "Only Derse and Prospit are valid dream moons. Don't forget the capitalization!"
                    .to_string(),
            ),
        }),
    }
}

#[derive(Clone, Serialize, Deserialize)]
pub struct CharacterDreamerPost {
    pub moon: String,
}

#[derive(Template)]
#[template(path = "partial/character-dreamer.html.jinja")]
pub struct CharacterDreamerTemplate {
    pub dreamer: Option<String>,
    pub input: String,
    pub error: Option<String>,
}

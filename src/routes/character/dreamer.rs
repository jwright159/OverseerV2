use askama::Template;
use axum::response::IntoResponse;
use axum::{Extension, Form};
use serde::{Deserialize, Serialize};
use sqlx::MySqlPool;

use crate::error::Result;
use crate::routes::HtmlTemplate;
use crate::routes::character::Character;

pub async fn character_dreamer_post(
    character: Character,
    Extension(db): Extension<MySqlPool>,
    Form(form): Form<CharacterDreamerPost>,
) -> Result<impl IntoResponse> {
    let character_dreamer: Option<String> = None;
    if let Some(dreamer) = character_dreamer {
        return Ok(HtmlTemplate(CharacterDreamerTemplate {
            dreamer: None,
            input: form.moon.clone(),
            error: Some(format!("You're already a {} dreamer!", dreamer)),
        }));
    };

    Ok(HtmlTemplate(match form.moon.as_str() {
        "Prospit" | "Derse" => {
            sqlx::query!(
                "UPDATE Characters SET dreamer = ? WHERE id = ?",
                form.moon,
                character.id
            )
            .execute(&db)
            .await?;
            CharacterDreamerTemplate {
                dreamer: Some(form.moon.clone()),
                input: form.moon.clone(),
                error: None,
            }
        }
        "Space station" => CharacterDreamerTemplate {
            dreamer: None,
            input: form.moon.clone(),
            error: Some("That's not a moon...".to_string()),
        },
        "The Battlefield" | "Battlefield" | "Skaia" => CharacterDreamerTemplate {
            dreamer: None,
            input: form.moon.clone(),
            error: Some("Nice try, but no.".to_string()),
        },
        _ if form.moon.contains("Land") => CharacterDreamerTemplate {
            dreamer: None,
            input: form.moon.clone(),
            error: Some("You can't start your dreamself off on a Land, sorry.".to_string()),
        },
        _ => CharacterDreamerTemplate {
            dreamer: None,
            input: form.moon.clone(),
            error: Some(
                "Only Derse and Prospit are valid dream moons. Don't forget the capitalization!"
                    .to_string(),
            ),
        },
    }))
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

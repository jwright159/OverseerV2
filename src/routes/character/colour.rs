use askama::Template;
use axum::response::IntoResponse;
use axum::{Extension, Form};
use serde::{Deserialize, Serialize};
use sqlx::MySqlPool;

use crate::error::Result;
use crate::routes::HtmlTemplate;
use crate::routes::character::Character;

pub async fn character_colour_post(
    character: Character,
    Extension(db): Extension<MySqlPool>,
    Form(form): Form<CharacterColourSubmission>,
) -> Result<impl IntoResponse> {
    let colour = form.colour.replace("#", "");

    sqlx::query!(
        "UPDATE Characters SET colour = ? WHERE id = ?",
        colour,
        character.id,
    )
    .execute(&db)
    .await?;

    Ok(HtmlTemplate(CharacterColourTemplate { colour }))
}

#[derive(Clone, Serialize, Deserialize)]
pub struct CharacterColourSubmission {
    pub colour: String,
}

#[derive(Template)]
#[template(path = "partial/character-colour.html.jinja")]
pub struct CharacterColourTemplate {
    pub colour: String,
}

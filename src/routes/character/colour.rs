use askama::Template;
use axum::Form;
use axum::response::IntoResponse;
use serde::{Deserialize, Serialize};

use crate::routes::HtmlTemplate;

pub async fn character_colour_post(
    Form(form): Form<CharacterColourSubmission>,
) -> impl IntoResponse {
    HtmlTemplate(CharacterColourTemplate {
        colour: form.colour,
    })
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

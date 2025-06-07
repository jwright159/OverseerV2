use askama::Template;
use axum::response::IntoResponse;

use crate::routes::HtmlTemplate;

pub async fn debug_clear() -> impl IntoResponse {
    HtmlTemplate(CharacterGatesTemplate {
        gates_reached: 0,
        gates_cleared: 0,
    })
}

#[derive(Template)]
#[template(path = "partial/character-gates.html.jinja")]
pub struct CharacterGatesTemplate {
    pub gates_reached: usize,
    pub gates_cleared: usize,
}

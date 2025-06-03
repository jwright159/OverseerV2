use askama::Template;
use axum::response::IntoResponse;

use crate::routes::HtmlTemplate;

pub async fn debug_clear() -> impl IntoResponse {
    HtmlTemplate(CharacterGatesTemplate {
        gates_reached: 1,
        gates_cleared: 1,
    })
}

#[derive(Template)]
#[template(path = "partial/character-gates.html.jinja")]
pub struct CharacterGatesTemplate {
    pub gates_reached: i32,
    pub gates_cleared: i32,
}

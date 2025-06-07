use askama::Template;
use axum::Extension;
use axum::response::IntoResponse;
use sqlx::MySqlPool;

use crate::error::Result;
use crate::routes::HtmlTemplate;
use crate::routes::character::Character;

pub async fn debug_clear(
    mut character: Character,
    Extension(db): Extension<MySqlPool>,
) -> Result<impl IntoResponse> {
    if (character.gates_cleared as usize) < character.gates_reached() {
        character.gates_cleared += 1;
        sqlx::query!(
            "UPDATE Characters SET gatescleared = ? WHERE id = ?",
            character.gates_cleared,
            character.id
        )
        .execute(&db)
        .await?;
    }

    Ok(HtmlTemplate(CharacterGatesTemplate {
        gates_reached: character.gates_reached(),
        gates_cleared: character.gates_cleared as usize,
    }))
}

#[derive(Template)]
#[template(path = "partial/character-gates.html.jinja")]
pub struct CharacterGatesTemplate {
    pub gates_reached: usize,
    pub gates_cleared: usize,
}

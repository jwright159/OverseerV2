use askama::Template;
use axum::Extension;
use axum::response::IntoResponse;
use axum_typed_multipart::TypedMultipart;
use imagesize::ImageSize;
use sqlx::MySqlPool;

use crate::error::{Error, Result};
use crate::routes::HtmlTemplate;
use crate::routes::character::Character;

pub async fn character_symbol_post(
    character: Character,
    Extension(db): Extension<MySqlPool>,
    TypedMultipart(form): TypedMultipart<CharacterSymbolSubmission>,
) -> Result<impl IntoResponse> {
    let file = form.file;
    let filepath = file.path();
    let filename = filepath.file_name().ok_or(Error::InvalidFilename)?;
    let (_file_basename, file_ext) = filename
        .to_str()
        .ok_or(Error::InvalidFilename)?
        .rsplit_once('.')
        .ok_or(Error::InvalidFilename)?;
    let filesize = file.as_file().metadata()?.len();
    let allowed_file_types = ["png"];
    let ImageSize { width, height } = imagesize::size(filepath)?;
    let new_filename = format!("{}_{}.{}", "playername", "sessionname", file_ext);
    let new_filepath = format!("/images/symbols/{}", new_filename);

    if filesize > 2 * 1024 * 1024 {
        Ok(HtmlTemplate(CharacterSymbolTemplate {
            symbol: "".to_string(),
            error: Some("File size exceeds 2MB".to_string()),
        }))
    } else if width != 64 || height != 64 {
        return Ok(HtmlTemplate(CharacterSymbolTemplate {
            symbol: "".to_string(),
            error: Some("The file's dimensions need to be 64x64 pixels.".to_string()),
        }));
    } else if !allowed_file_types.contains(&file_ext) {
        Ok(HtmlTemplate(CharacterSymbolTemplate {
            symbol: "".to_string(),
            error: Some(format!(
                "Only these file types are allowed for upload: {}",
                allowed_file_types.join(", ")
            )),
        }))
    } else {
        file.persist(format!(
            "{}/{}",
            std::env::var("OVERSEER_ROOT")?,
            new_filepath
        ))?;
        sqlx::query!(
            "UPDATE Characters SET symbol = ? WHERE id = ?",
            new_filepath,
            character.id
        )
        .execute(&db)
        .await?;
        Ok(HtmlTemplate(CharacterSymbolTemplate {
            symbol: new_filepath,
            error: Some("File uploaded successfully".to_string()),
        }))
    }
}

// Doesn't like crate::error::Result
mod submission {
    use axum_typed_multipart::TryFromMultipart;
    use tempfile::NamedTempFile;

    #[derive(TryFromMultipart)]
    pub struct CharacterSymbolSubmission {
        pub file: NamedTempFile,
    }
}
pub use submission::CharacterSymbolSubmission;

#[derive(Template)]
#[template(path = "partial/character-symbol.html.jinja")]
pub struct CharacterSymbolTemplate {
    pub symbol: String,
    pub error: Option<String>,
}

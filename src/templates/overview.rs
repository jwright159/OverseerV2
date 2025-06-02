use askama::Template;

use crate::data::character::Character;

#[derive(Template)]
#[template(path = "overview.html.jinja")]
pub struct OverviewTemplate {
    pub character: Character,
    pub background: String,
}

use askama::Template;

pub mod colour;
pub mod dreamer;

#[derive(Debug, Clone)]
pub struct Character {
    pub id: i32,
    pub name: String,
    pub aspect: String,
    pub class: String,
    pub strife: Strife,
    pub echeladder: i32,
    pub boondollars: i32,
    pub symbol: String,
    pub colour: String,
    pub dreamer: Option<String>,
}

impl Character {
    pub fn profile_string(&self) -> String {
        let template = ProfileStringTemplate {
            character: self.clone(),
        };
        match template.render() {
            Ok(html) => html,
            Err(_err) => "[ERROR RETRIEVING PLAYER ID]".to_string(),
        }
    }
}

#[derive(Template)]
#[template(path = "partial/profile-string.html.jinja")]
pub struct ProfileStringTemplate {
    pub character: Character,
}

#[derive(Debug, Clone)]
pub struct Strife {
    pub power: i32,
    pub health: i32,
    pub max_health: i32,
    pub health_percent: f32,
    pub energy: i32,
    pub max_energy: i32,
    pub energy_percent: f32,
}

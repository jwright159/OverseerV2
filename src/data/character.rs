pub struct Character {
    pub name: String,
    pub aspect: String,
    pub class: String,
    pub strife: Strife,
    pub echeladder: i32,
    pub boondollars: i32,
    pub is_in_medium: bool,
    pub symbol: String,
}

pub struct Strife {
    pub power: i32,
    pub health: i32,
    pub max_health: i32,
    pub health_percent: f32,
    pub energy: i32,
    pub max_energy: i32,
    pub energy_percent: f32,
}

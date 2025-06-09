use crate::error::{Error, Result};
use crate::status::{StrifeBonus, StrifeBonusType, StrifeStatus};
use askama::Template;
use axum::extract::FromRequestParts;
use axum::http::request::Parts;
use axum::{Extension, RequestPartsExt as _};
use futures::future::try_join_all;
use itertools::Itertools;
use return_ok::some_or_return_ok;
use sqlx::MySqlPool;
use std::collections::HashMap;
use std::str::FromStr;
use tokio::sync::OnceCell;
use tower_sessions::Session;

pub mod colour;
pub mod dreamer;
pub mod gates;
pub mod symbol;

#[derive(Debug, Clone)]
pub struct Character {
    pub id: i64,
    pub name: String,
    pub aspect: String,
    pub class: String,
    pub strife: Strifer,
    pub echeladder: i64,
    pub boondollars: i64,
    pub symbol: String,
    pub colour: String,
    pub dreamer: Option<String>,
    pub land_1: Option<String>,
    pub land_2: Option<String>,
    pub grist_type: Option<Vec<String>>,
    pub consort: Option<String>,
    pub house_build: i64,
    pub achievements: Vec<String>,
    pub dreaming_status: String,
    pub gates_cleared: i64,
    pub session: i64,
    pub server_id: Option<i64>,
    pub old_enemy_data: HashMap<String, String>,
    pub down: bool,
    pub dreamer_down: bool,
    pub in_medium: bool,
    pub dungeon: Option<i64>,
    pub denizen_down: bool,
    pub dungeon_row: i64,
    pub dungeon_col: i64,
    pub old_dungeon_row: i64,
    pub old_dungeon_col: i64,
}

impl Character {
    pub async fn from_session(session: i64, db: &MySqlPool) -> Result<Vec<Character>> {
        let sql = sqlx::query!(r#"SELECT ID as id FROM Characters WHERE session = ?"#, session)
            .fetch_all(db)
            .await.map_err(Error::Sqlx)?;

        Ok(try_join_all(sql.into_iter()
            .map(|r| Character::load(r.id as i64, db)))
            .await?.into_iter().filter(|s| s.is_some()).map(|s| s.unwrap()).collect())
    }

    pub async fn load(id: i64, db: &MySqlPool) -> Result<Option<Character>> {
        let sql = some_or_return_ok!(sqlx::query!(
            r#"SELECT id, name, aspect, class, wakeself, dreamself, dreamingstatus, echeladder, boondollars, symbol, colour, dreamer, land1, land2, grist_type, consort, house_build, achievements, gatescleared, session, server, oldenemydata as old_enemy_data, down, dreamdown as dreamer_down, dungeon, inmedium as in_medium, denizendown as denizen_down, dungeonrow as dungeon_row, dungeoncol as dungeon_col, olddungeonrow as old_dungeon_row, olddungeoncol as old_dungeon_col FROM Characters WHERE id = ?"#,
            id
        )
        .fetch_optional(db)
        .await
        .map_err(Error::Sqlx)?);

        let strife = if sql.dreamingstatus == "Awake" {
            Strifer::load(sql.wakeself, db)
                .await?
                .ok_or(Error::StriferNotFound(sql.wakeself))?
        } else {
            Strifer::load(sql.dreamself, db)
                .await?
                .ok_or(Error::StriferNotFound(sql.dreamself))?
        };

        let old_enemy_data = sql.old_enemy_data
            .split('|')
            .filter(|s| !s.is_empty())
            .filter_map(|s| s.split(":").collect_array::<2>())
            .map(|[key, value]| (key.to_owned(), value.to_owned()))
            .collect::<HashMap<_, _>>();

        Ok(Some(Character {
            id: sql.id as i64,
            name: sql.name,
            aspect: sql.aspect,
            class: sql.class,
            strife,
            echeladder: sql.echeladder as i64,
            boondollars: sql.boondollars,
            symbol: sql.symbol,
            colour: sql.colour,
            dreamer: match sql.dreamer.as_str() {
                "" => None,
                _ => Some(sql.dreamer),
            },
            land_1: match sql.land1.as_str() {
                "" => None,
                _ => Some(sql.land1),
            },
            land_2: match sql.land2.as_str() {
                "" => None,
                _ => Some(sql.land2),
            },
            grist_type: match sql.grist_type.as_str() {
                "" => None,
                _ => Some(sql.grist_type.split('|').map(String::from).collect()),
            },
            consort: match sql.consort.as_str() {
                "" => None,
                _ => Some(sql.consort),
            },
            house_build: sql.house_build as i64,
            achievements: sql.achievements.split('|').map(String::from).collect(),
            dreaming_status: sql.dreamingstatus,
            gates_cleared: sql.gatescleared as i64,
            session: sql.session as i64,
            server_id: match sql.server {
                0 => None,
                _ => Some(sql.server as i64)
            },
            old_enemy_data,
            down: sql.down == 1,
            dreamer_down: sql.dreamer_down == 1,
            dungeon: match sql.dungeon {
                0 => None,
                _ => Some(sql.dungeon as i64)
            },
            in_medium: sql.in_medium == 1,
            denizen_down: sql.denizen_down == 1,
            dungeon_row: sql.dungeon_row as i64,
            dungeon_col: sql.dungeon_col as i64,
            old_dungeon_row: sql.old_dungeon_row as i64,
            old_dungeon_col: sql.old_dungeon_col as i64,
        }))
    }

    pub fn profile_string(&self) -> String {
        let template = ProfileStringTemplate {
            character_id: self.id,
            character_colour: self.colour.clone(),
            character_name: self.name.clone()
        };
        match template.render() {
            Ok(html) => html,
            Err(_err) => "[ERROR RETRIEVING PLAYER ID]".to_string(),
        }
    }

    pub fn gates_reached(&self) -> usize {
        [100, 1100, 11100, 111100, 1111100, 11111100, 24000000]
            .into_iter()
            .filter(|g| self.house_build > *g)
            .count()
    }
}

impl<S> FromRequestParts<S> for Character
where
    S: Send + Sync,
{
    type Rejection = Error;

    async fn from_request_parts(req: &mut Parts, _state: &S) -> Result<Self> {
        let session = req
            .extract::<Session>()
            .await
            .map_err(|(_, err)| Error::Extract(err.to_string()))?;
        let Extension(db): Extension<MySqlPool> = req.extract().await?;

        let character_id = session
            .get::<String>("character")
            .await?
            .map(|s| s.parse::<i64>())
            .transpose()?
            .ok_or(Error::NotLoggedInCharacter)?;
        let character = Character::load(character_id, &db)
            .await?
            .ok_or(Error::CharacterNotFound(character_id))?;

        Ok(character)
    }
}

#[derive(Template)]
#[template(path = "partial/profile-string.html.jinja")]
pub struct ProfileStringTemplate {
    pub character_id: i64,
    pub character_colour: String,
    pub character_name: String,
}

#[derive(Debug, Copy, Clone)]
pub struct Power {
    pub offense: i64,
    pub defense: i64,
}

#[derive(Debug, Clone)]
pub struct Strifer {
    pub id: i64,
    pub power: i64,
    pub health: i64,
    pub max_health: i64,
    pub energy: i64,
    pub max_energy: i64,
    pub description: String,
    pub echeladder: i64,
    pub owner: OnceCell<Option<Box<Character>>>,
    pub strife_id: Option<i64>,
    pub side: i8,
    pub current_motif: String,
    pub current_motif_name: String,
    pub name: String,
    pub bonuses: Vec<StrifeBonus>,
    pub equipment_bonuses: Vec<StrifeBonus>,
    pub grist: Option<String>,
    pub abilities: Vec<String>,
    pub statuses: Vec<StrifeStatus>, // In the format of [a1[:a2...]|...], e.g. a1:a2|b1:b2|c1:c2
    pub fatigue: i64,
    pub damage_taken: i64,
    pub active: Option<String>,
    pub passive: Option<String>,
    pub is_leader: bool,
    pub owner_id: Option<i64>,
    pub is_controllable: bool,
    pub last_active: String,
    pub last_passive: String,
    pub aspect: Option<String>,
}

impl Strifer {
    pub async fn from_strife_id(strife_id: i64, db: &MySqlPool) -> Result<Vec<Strifer>> {
        Ok(try_join_all(sqlx::query!(r#"SELECT ID as id FROM Strifers WHERE strifeID = ?"#, strife_id)
            .fetch_all(db)
            .await.map_err(Error::Sqlx)?
            .iter()
            .map(|r| Strifer::load(r.id, &db))).await?
            .into_iter().filter(|s| s.is_some()).map(|s| s.unwrap()).collect())
    }

    pub async fn load(id: i64, db: &MySqlPool) -> Result<Option<Strifer>> {
        let sql = some_or_return_ok!(sqlx::query!(
            r#" SELECT
                    ID as id,
                    power,
                    health,
                    maxhealth as max_health,
                    energy,
                    maxenergy as max_energy,
                    description,
                    echeladder,
                    owner as owner_id,
                    strifeID as strife_id,
                    side,
                    currentmotif as current_motif,
                    currentmotifname as current_motif_name,
                    name,
                    bonuses,
                    equipbonuses as equipment_bonuses,
                    grist,
                    abilities,
                    status,
                    fatigue,
                    leader,
                    control,
                    lastactive as last_active,
                    lastpassive as last_passive,
                    aspect
                FROM Strifers
                WHERE id = ?
                "#,
            id
        )
        .fetch_optional(db)
        .await
        .map_err(Error::Sqlx)?);

        let owner_id = if sql.owner_id == 0 { None } else { Some(sql.owner_id as i64) };

        Ok(Some(Strifer {
            id: sql.id,
            power: sql.power as i64,
            health: sql.health as i64,
            max_health: sql.max_health as i64,
            energy: sql.energy as i64,
            max_energy: sql.max_energy as i64,
            description: sql.description,
            echeladder: sql.echeladder as i64,
            owner_id,
            owner: OnceCell::new(),
            strife_id: match sql.strife_id {
                0 => None,
                _ => Some(sql.strife_id)
            },
            side: sql.side,
            current_motif: sql.current_motif,
            current_motif_name: sql.current_motif_name,
            name: sql.name,
            bonuses: sql.bonuses
                .split("|")
                .map(StrifeBonus::from_str)
                .filter_map(|s| s.ok())
                .collect(),
            equipment_bonuses: sql.equipment_bonuses
                .split("|")
                .map(StrifeBonus::from_str)
                .filter_map(|s| s.ok())
                .collect(),
            grist: if sql.grist == "None" { None } else { Some(sql.grist) },
            statuses: sql.status
                .split('|')
                .map(StrifeStatus::from_str)
                .filter_map(|s| s.ok())
                .collect(),
            fatigue: sql.fatigue as i64,
            damage_taken: 0,
            active: None,
            passive: None,
            abilities: sql.abilities.split("|").filter(|s| !s.is_empty()).map(str::to_string).collect(),
            is_leader: sql.leader == 1,
            is_controllable: sql.control == 1,
            last_active: sql.last_active,
            last_passive: sql.last_passive,
            aspect: if sql.aspect.is_empty() { None } else { Some(sql.aspect) }
        }))
    }

    pub fn health_percent(&self) -> f64 {
        if self.max_health == 0 {
            0.0
        } else {
            (self.health as f64 / self.max_health as f64) * 100.0
        }
    }

    pub fn energy_percent(&self) -> f64 {
        if self.max_energy == 0 {
            0.0
        } else {
            (self.energy as f64 / self.max_energy as f64) * 100.0
        }
    }

    pub fn strifer_string(&self) -> String {
        let template = StriferStringTemplate {
            strifer: self.clone(),
        };

        template.render()
            .unwrap_or_else(|_err| "[ERROR DISPLAYING STRIFER INFO]".to_string())
    }

    pub fn power(&self) -> Power {
        use self::Power as P;
        use crate::status::StrifeBonusType::{self, *};
        use crate::status::StrifeStatusType::*;

        let mut offense = self.power;
        let mut defense = self.power;

        let mut bonus_store: HashMap<StrifeBonusType, i64> = HashMap::from([
            (Aggrieve, 0),
            (Aggress, 0),
            (Assail, 0),
            (Assault, 0),

            (Abuse, 0),
            (Accuse, 0),
            (Abjure, 0),
            (Abstain, 0),
        ]);

        for StrifeBonus { bonus_type, value, .. } in &self.bonuses {
            match bonus_type {
                Power => {
                    offense += value;
                    defense += value;
                },
                Offense => {
                    offense += value;
                },
                Defense => {
                    defense += value;
                },
                _ => {
                    if let Some(v) = bonus_store.get_mut(bonus_type) {
                        *v += value;
                    } else {
                        bonus_store.insert(bonus_type.clone(), *value);
                    }
                }
            }
        }

        match self.current_motif.as_str() {
            "Breath/I" => {
                defense *= 2;
            },
            "Heart/I" => {
                offense = (offense as f64 * 1.2).ceil() as i64 + 3300;
                defense = (offense as f64 * 1.2).ceil() as i64 + 3300; // Sign concern this uses offense
            },
            "Hope/I" => {
                let factor = 1.0 + (((self.health + self.damage_taken) as f64 / self.max_health as f64) * 0.75);
                offense = (offense as f64 * factor).ceil() as i64;
                defense = (defense as f64 * factor).ceil() as i64;
            },
            "Rage/I" => {
                let factor = (3.0 + (((self.health + self.damage_taken) as f64 / self.max_health as f64) * 1.8)).max(1.2);
                offense = (offense as f64 * factor).ceil() as i64;
            },
            "Mind/II" => {
                offense = (offense as f64 * 1.33).ceil() as i64;
                defense *= 413;
            }
            _ => {}
        }

        if let Some(active) = &self.active {
            match active.as_str() {
                "AGGRIEVE" => {
                    offense = (offense as f64 * 1.05).floor() as i64 + bonus_store[&Aggrieve];
                },
                "AGGRESS" => {
                    offense = (offense as f64 * 1.2).floor() as i64 + bonus_store[&Aggress];
                    defense = (defense as f64 * 0.83).floor() as i64;
                },
                "ASSAIL" => {
                    offense = (offense as f64 * 1.5).floor() as i64 + bonus_store[&Assail];
                    defense = (defense as f64 * 0.66).floor() as i64;
                },
                "ASSAULT" => {
                    offense = (offense as f64 * 2.0).floor() as i64 + bonus_store[&Assault];
                    defense = (defense as f64 * 0.5).floor() as i64;
                }
                _ => {}
            }
        }

        if let Some(passive) = &self.passive {
            match passive.as_str() {
                "ABUSE" => {
                    offense = (offense as f64 * 1.05).floor() as i64 + bonus_store[&Abuse];
                },
                "ACCUSE" => {
                    offense = (offense as f64 * 1.2).floor() as i64 + bonus_store[&Accuse];
                    defense = (defense as f64 * 0.83).floor() as i64;
                },
                "ABJURE" => {
                    offense = (offense as f64 * 1.5).floor() as i64 + bonus_store[&Abjure];
                    defense = (defense as f64 * 0.66).floor() as i64;
                },
                "ABSTAIN" => {
                    offense = (offense as f64 * 2.0).floor() as i64 + bonus_store[&Abstain];
                    defense = (defense as f64 * 0.5).floor() as i64;
                }
                _ => {}
            }
        }

        for ability in &self.abilities {
            match ability.as_str() {
                // Each ability with an effect on offense or defense power has an entry in this switch statement
                "15" => { // One with Nothing
                    if self.description.contains("dreamself") {
                        let minimum = (self.echeladder as f64 * (1.0 + (self.echeladder as f64 * 0.04))).floor() as i64;
                        if offense < minimum && defense < minimum {
                            offense = minimum;
                            defense = minimum;
                        }
                    }
                },
                "-5" => { // Abraxas's hopeful power boost. 12k at max health, linearly down to 0 at 0 health.
                    let boost = (12000.0 * (self.health as f64 / self.max_health as f64)).floor() as i64;
                    offense += boost;
                    defense += boost;
                },
                _ => {}
            }
        }

        for status in &self.statuses {
            match status.status_type {
                CantAttack => {
                    offense = 0;
                },
                CantDefend => {
                    defense = 0;
                },
                Frozen => {
                    offense = 0;
                    defense = (defense as f64 * 1.1).floor() as i64;
                },
                Shrunk => {
                    offense = (offense as f64 * 0.8).floor() as i64;
                    defense = (defense as f64 * 0.8).floor() as i64;
                },
                Disoriented => {
                    offense = (offense as f64 * 0.96).floor() as i64;
                },
                Enraged => {
                    defense = (defense as f64 * 0.9).floor() as i64;
                },
                Mellow => {
                    offense = (offense as f64 * 0.9).floor() as i64;
                },
                Pinata => {
                    offense = 0;
                    defense = 0;
                },
                _ => {}
            }
        }

        if self.fatigue > 1025 {
            // TODO: Achievement [fatigue]
            let reduction = 1.0 - ((self.fatigue - 1025) as f64 / 500.0);
            offense = (offense as f64 * reduction).floor() as i64;
            defense = (defense as f64 * reduction).floor() as i64;
        }

        P { offense, defense }
    }

    pub fn bonus_of_types(&self, types: &[StrifeBonusType]) -> Vec<StrifeBonus> {
        // This ensures that if the requested types AREN'T in the bonus lists, an empty one will be provided.
        let init_vec = types.iter().map(|t| StrifeBonus { key: t.key().to_string(), bonus_type: t.clone(), value: 0, duration: None }).collect_vec();

        // This may potentially contain duplicate entries for effects!
        let combined_result = [&init_vec, &self.bonuses, &self.equipment_bonuses]
            .iter()
            .flat_map(|v| v.iter())
            .filter(|b| types.contains(&b.bonus_type))
            .map(|b| b.clone())
            .collect_vec();

        let mut cleansed_result: HashMap<StrifeBonusType, StrifeBonus> = HashMap::new();
        for result in combined_result {
            match cleansed_result.get_mut(&result.bonus_type) {
                None => { cleansed_result.insert(result.bonus_type.clone(), result); },
                Some(r) => {
                    r.value += result.value;
                    if let Some(duration) = result.duration {
                        match r.duration.as_mut() {
                            None => { r.duration = Some(duration); },
                            Some(d) => { *d += duration; }
                        }
                    }
                }
            };
        }

        cleansed_result.into_values().collect()
    }

    pub fn active_bonus(&self) -> Vec<StrifeBonus> {
        use crate::status::StrifeBonusType::*;
        self.bonus_of_types(&[Aggrieve, Aggress, Assail, Assault])
    }

    pub fn passive_bonus(&self) -> Vec<StrifeBonus> {
        use crate::status::StrifeBonusType::*;
        self.bonus_of_types(&[Abuse, Accuse, Abjure, Abstain])
    }

    pub fn get_owner(&self) -> Option<&Character> {
        match self.owner.get() {
            None => None,
            Some(c) => c.as_ref().map(|c| c.as_ref())
        }
    }

    pub async fn fetch_owner(&self, db: &MySqlPool) -> Result<Option<&Character>> {
        let owner_id = match &self.owner_id {
            Some(owner_id) => owner_id,
            None => return Ok(None)
        };

        // This is cursed, but indirection is required, so we Box Character, ideally Characters will only be
        // populated as needed, so hopefully this isn't too big of a performance hit.
        Ok(self.owner.get_or_try_init(|| async {
            Character::load(*owner_id, db).await.map(|c| c.map(|c| Box::new(c)))
        }).await?.as_ref().map(|c| c.as_ref()))
    }
}

#[derive(Template)]
#[template(path = "partial/strifer-string.html.jinja")]
pub struct StriferStringTemplate {
    pub strifer: Strifer,
}
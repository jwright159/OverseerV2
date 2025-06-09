pub mod leader;

pub use leader::*;

use std::collections::HashMap;
use askama::Template;
use axum::Extension;
use axum::response::IntoResponse;
use sqlx::MySqlPool;
use crate::error::{Error, Result};
use crate::routes::character::{Character, Strifer};
use crate::routes::HtmlTemplate;

pub async fn strife_display(character: Character, Extension(db): Extension<MySqlPool>) -> Result<impl IntoResponse> {
    let main_strifer = character.strife.clone();
    let db = &db;

    let strifers = if let Some(strife_id) = main_strifer.strife_id {
        Strifer::from_strife_id(strife_id, db).await?
    } else { Vec::new() };

    let (player_side, fraymotif_strifer) = if main_strifer.strife_id.is_none() {
        (-1, None)
    } else {
        let mut player_side: i8 = -1;
        let mut fraymotif_strifer = None;
        for strifer in &strifers {
            if strifer.fetch_owner(db).await?.is_some_and(|o| o.id == character.id) {
                player_side = strifer.side;
                if !strifer.current_motif.is_empty() {
                    fraymotif_strifer = Some(strifer);
                }
            }
        }

        (player_side, fraymotif_strifer)
    };

    let fraymotif_message = match fraymotif_strifer {
        None => None,
        Some(strifer) => {
            let template = StrifeFraymotifTemplate {
                fraymotif: strifer.current_motif.clone(),
                fraymotif_name: strifer.current_motif_name.clone(),
                strifer_name: strifer.name.clone(),
            };

            Some(template.render()
                .unwrap_or_else(|_err| "[ERROR RENDERING FRAYMOTIF MESSAGE]".to_string()))
        }
    };

    let chumroll = Character::from_session(character.session, db).await?;

    let chain = {
        let mut chain: HashMap<i64, bool> = HashMap::new();
        let mut mates: HashMap<i64, &Character> = HashMap::new();

        for chum in &chumroll {
            chain.insert(chum.id, false);
            mates.insert(chum.id, chum);
        }

        if character.gates_cleared >= 1 {
            chain.insert(character.id, true);
        }

        if character.gates_cleared >= 2 {
            let mut current_chum = &character;
            let mut no_break = true;
            let mut minus_2_row: Option<&Character> = None;
            let mut minus_1_row: Option<&Character> = None;

            while let Some(server_id) = current_chum.server_id && server_id != character.id && no_break {
                let minus_3_row = minus_2_row;
                minus_2_row = minus_1_row;
                minus_1_row = Some(&current_chum);

                current_chum = mates.get(&server_id).ok_or(Error::CharacterNotFound(server_id))?;
                no_break =
                    current_chum.gates_cleared >= 6 && minus_3_row.is_some_and(|c| c.gates_cleared >= 6)
                 || current_chum.gates_cleared >= 4 && minus_2_row.is_some_and(|r| r.gates_cleared >= 4)
                 || current_chum.gates_cleared >= 2 && minus_1_row.is_some_and(|c| c.gates_cleared >= 2);

                if no_break {
                    if let Some(c) = chain.get_mut(&current_chum.id) {
                        *c = true;
                    }
                }
            }
        }

        chain
    };

    let dream_enemies: Vec<(String, i64)> = if character.dreaming_status == "Awake" { Vec::new() } else {
        sqlx::query!(r#"SELECT basename as base_name, basepower as base_power FROM Enemy_Types WHERE appearson = ? ORDER BY base_power ASC"#, character.dreaming_status)
            .map(|r| (r.base_name, r.base_power))
            .fetch_all(db).await?
    };

    let allies: Vec<(i64, String)> = {
        chumroll.iter()
            .filter(|c| c.id != character.id)
            .map(|c| &c.strife)
            .filter(|s| s.strife_id.is_some())
            .map(|s| (s.strife_id.unwrap(), s.name.clone()))
            .collect()
    };

    let background = if character.dreaming_status == "Awake" {
        "".to_string()
    } else {
        character
            .dreamer
            .clone()
            .ok_or(Error::ShouldHaveDreamer(character.id))?
    };

    let potential_leaders = if !main_strifer.is_leader { Vec::new() } else {
        strifers
            .iter()
            .filter(|s| s.aspect.is_some() && s.side == main_strifer.side && s.id != main_strifer.id)
            .map(|s| s.clone())
            .collect()
    };

    let strife_commands = StrifeCommandsTemplate {
        character: character.clone(),
        main_strifer: main_strifer.clone(),
        strifers: strifers.clone(),
        potential_leaders,
    }.render().unwrap_or_else(|_err| "[ERROR RENDERING STRIFE COMMANDS]".to_string());

    Ok(HtmlTemplate(StrifeDisplayTemplate {
        character,
        main_strifer,
        strifers,
        background,
        announcements: vec![],
        player_side,
        fraymotif_message,
        chain,
        chumroll,
        allies,
        dream_enemies,
        strife_commands
    }))
}

#[derive(Template)]
#[template(path = "strife_display.html.jinja")]
pub struct StrifeDisplayTemplate {
    pub character: Character,
    pub main_strifer: Strifer,
    pub strifers: Vec<Strifer>,
    pub background: String,
    pub announcements: Vec<String>,
    pub player_side: i8,
    pub fraymotif_message: Option<String>,
    pub chain: HashMap<i64, bool>,
    pub chumroll: Vec<Character>,
    pub allies: Vec<(i64, String)>,
    pub dream_enemies: Vec<(String, i64)>,
    pub strife_commands: String,
}

#[derive(Template)]
#[template(path = "partial/strife-fraymotif.html.jinja")]
pub struct StrifeFraymotifTemplate {
    pub fraymotif: String,
    pub fraymotif_name: String,
    pub strifer_name: String,
}
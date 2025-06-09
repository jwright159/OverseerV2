use askama::Template;
use axum::{Extension, Form};
use axum::response::IntoResponse;
use serde::{Deserialize, Serialize};
use sqlx::MySqlPool;
use tokio::sync::broadcast::Sender;
use crate::broadcast::BroadcastMessage;
use crate::routes::character::{Character, Strifer};
use crate::error::{Result};
use crate::routes::HtmlTemplate;

pub async fn swap_leader(
    mut character: Character,
    Extension(db): Extension<MySqlPool>,
    Extension(sse): Extension<Sender<BroadcastMessage>>,
    Form(form): Form<LeaderSwapSubmission>,
) -> Result<impl IntoResponse> {
    if character.strife.strife_id.is_none() {
        return Ok(HtmlTemplate(LeaderSwapTemplate {
            error: Some("ERROR: You are not currently engaged in strife!".to_string())
        }));
    }

    if character.dungeon.is_some() {
        return Ok(HtmlTemplate(LeaderSwapTemplate {
            error: Some("As the player exploring the dungeon, you must continue to lead the strife!".to_string())
        }));
    }

    if !character.strife.is_leader {
        return Ok(HtmlTemplate(LeaderSwapTemplate {
            error: Some("ERROR: You are not the leader!".to_string())
        }));
    }

    let mut new_leader = match Strifer::load(form.leader_id, &db).await? {
        Some(new_leader) => new_leader,
        None => return Ok(HtmlTemplate(LeaderSwapTemplate {
            error: Some("ERROR: The strifer you tried to give the lead to was not found or was not another player!".to_string())
        }))
    };

    character.strife.is_leader = false;
    new_leader.is_leader = true;
    sqlx::query!(
        "UPDATE Strifers SET leader = 0 WHERE ID = ?",
        character.strife.id
    ).execute(&db).await?;
    sqlx::query!(
        "UPDATE Strifers SET leader = 1 WHERE ID = ?",
        new_leader.id
    ).execute(&db).await?;

    let strifers = Strifer::from_strife_id(character.strife.strife_id.unwrap(), &db).await?;
    let old_leader_id = character.strife.id;
    let new_leader_id = new_leader.id;
    let potential_leaders = strifers
        .iter()
        .filter(|s| s.aspect.is_some() && s.side == new_leader.side && s.id != new_leader.id)
        .map(|s| s.clone())
        .collect();

    let leader_removed_template = StrifeCommandsTemplate {
        character: character.clone(),
        main_strifer: character.strife.clone(),
        strifers: strifers.clone(),
        potential_leaders: Vec::new(),
    };
    let leader_added_template = StrifeCommandsTemplate {
        character: new_leader.fetch_owner(&db).await?.unwrap().clone(),
        main_strifer: new_leader,
        strifers,
        potential_leaders
    };

    let leader_removed_strife_commands = leader_removed_template.render().unwrap_or_else(|_err| "[ERROR GENERATING STRIFE COMMAND LIST]".to_string());
    let leader_added_strife_commands = leader_added_template.render().unwrap_or_else(|_err| "[ERROR GENERATING STRIFE COMMAND LIST]".to_string());

    sse.send(BroadcastMessage::LeaderRemove {
        id: old_leader_id,
        command_select_string: leader_removed_strife_commands,
    })?;
    sse.send(BroadcastMessage::LeaderAdd {
        id: new_leader_id,
        command_select_string: leader_added_strife_commands,
    })?;

    Ok(HtmlTemplate(LeaderSwapTemplate { error: None }))
}

#[derive(Clone, Serialize, Deserialize)]
pub struct LeaderSwapSubmission {
    pub leader_id: i64,
}

#[derive(Template)]
#[template(path = "partial/strife-leader-swap.html.jinja")]
pub struct LeaderSwapTemplate {
    pub error: Option<String>,
}

#[derive(Template)]
#[template(path = "partial/strife-commands.html.jinja")]
pub struct StrifeCommandsTemplate {
    pub character: Character,
    pub main_strifer: Strifer,
    pub strifers: Vec<Strifer>,
    pub potential_leaders: Vec<Strifer>,
}
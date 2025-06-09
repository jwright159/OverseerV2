use askama::Template;
use axum::Extension;
use axum::response::IntoResponse;
use itertools::Itertools;
use sqlx::MySqlPool;
use tokio::sync::broadcast::Sender;
use crate::broadcast::BroadcastMessage;
use crate::routes::character::{Character, Strifer};
use crate::routes::HtmlTemplate;
use crate::error::Result;
use crate::routes::strife::{StrifeCommandsTemplate, StrifersTemplate};

pub async fn strife_abscond(
    mut character: Character,
    Extension(db): Extension<MySqlPool>,
    Extension(sse): Extension<Sender<BroadcastMessage>>,
) -> Result<impl IntoResponse> {
    if let Some(strife_id) = character.strife.strife_id {
        let mut party_ids: Vec<i64> = Vec::new();
        let strifers = Strifer::from_strife_id(strife_id, &db)
            .await?;
        let mut find_leader = character.strife.is_leader; // Only look for a new leader if we were the leader of the Strife

        for strifer in &strifers {
            if strifer.owner_id.is_some_and(|o_id| o_id == character.id) {
                // Strifer is part of the fleeing player's entourage
                party_ids.push(strifer.id);
            } else if strifer.aspect.is_some() && find_leader {
                // We found another player character. They're the leader now.
                find_leader = false;
                sqlx::query!(
                    "UPDATE Strifers SET leader = 1 WHERE ID = ?",
                    strifer.id
                ).execute(&db).await?;
                let mut strifer = strifer.clone();
                strifer.is_leader = true;

                // Remove anyone who might be absconding with the current leader.
                let strifers = strifers
                    .iter()
                    .filter(|s| s.owner_id.is_none_or(|o_id| o_id != character.id))
                    .map(|s| s.clone())
                    .collect_vec();

                let potential_leaders = strifers
                    .iter()
                    .filter(|s| s.aspect.is_some() && s.side == strifer.side && s.id != strifer.id && s.id != character.strife.id)
                    .map(|s| s.clone())
                    .collect();

                let strifers_string = StrifersTemplate {
                    strifers: strifers.clone(),
                    player_side: strifer.side
                }.render().unwrap_or_else(|_err| "[ERROR GENERATING STRIFERS STRING]".to_string());

                sse.send(BroadcastMessage::StrifersUpdate {
                    strife_id,
                    strifers_string
                })?;

                let leader_commands = StrifeCommandsTemplate {
                    character: strifer.fetch_owner(&db).await?.unwrap().clone(),
                    main_strifer: strifer.clone(),
                    strifers,
                    potential_leaders,
                }.render().unwrap_or_else(|_err| "[ERROR GENERATING STRIFE COMMAND LIST]".to_string());

                sse.send(BroadcastMessage::LeaderAdd {
                    id: strifer.id,
                    command_select_string: leader_commands,
                })?;
            }
        }

        if !party_ids.is_empty() {
            // Have to do this bullshittery because `IN` clauses don't work with sqlx and MySql.
            // See: https://github.com/launchbadge/sqlx/blob/main/FAQ.md#how-can-i-do-a-select--where-foo-in--query
            let params = if party_ids.len() == 1 { "?".to_string() } else {
                format!("?{}", ", ?".repeat(party_ids.len() - 1))
            };
            let sql = format!("UPDATE Strifers SET strifeID = 0 WHERE ID IN ({})", params);

            let mut query = sqlx::query(&sql);
            for id in party_ids {
                query = query.bind(id);
            }

            query.execute(&db).await?;
        }

        // Waking self was KOed
        if !character.strife.description.contains("dreamself") && character.dungeon.is_some() {
            let (old_dungeon_row, old_dungeon_col) = (character.old_dungeon_row, character.old_dungeon_col);

            sqlx::query!(
                "UPDATE Characters SET dungeonrow = ?, dungeoncol = ? WHERE ID = ?",
                old_dungeon_row,
                old_dungeon_col,
                character.id
            ).execute(&db).await?;

            character.dungeon_row = old_dungeon_row;
            character.dungeon_col = old_dungeon_col;
        }
    }

    Ok(HtmlTemplate(StrifeAbscondTemplate { character }))
}

#[derive(Template)]
#[template(path = "partial/strife-abscond.html.jinja")]
pub struct StrifeAbscondTemplate {
    pub character: Character
}
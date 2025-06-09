#[derive(Debug, Clone)]
pub enum BroadcastMessage {
    ProfileString { id: i64, profile_string: String },
    LeaderAdd { id: i64, command_select_string: String },
    LeaderRemove { id: i64, command_select_string: String },
}

impl BroadcastMessage {
    pub fn name(&self) -> String {
        match self {
            BroadcastMessage::ProfileString { id, .. } => format!("profile-string-{}", id),
            BroadcastMessage::LeaderAdd { id, .. } => format!("leader-add-{}", id),
            BroadcastMessage::LeaderRemove { id, .. } => format!("leader-remove-{}", id),
        }
    }

    pub fn data(&self) -> String {
        Self::cleanse_data(match self {
            BroadcastMessage::ProfileString { profile_string, .. } => profile_string.clone(),
            BroadcastMessage::LeaderAdd { command_select_string, .. } => command_select_string.clone(),
            BroadcastMessage::LeaderRemove { command_select_string, .. } => command_select_string.clone(),
        })
    }

    fn cleanse_data(data: String) -> String {
        // Remove Carriage Returns, as they aren't supported by SSE
        data.replace("\r", "")
    }
}

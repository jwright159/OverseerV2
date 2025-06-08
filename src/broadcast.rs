#[derive(Debug, Clone)]
pub enum BroadcastMessage {
    ProfileString { id: i64, profile_string: String },
}

impl BroadcastMessage {
    pub fn name(&self) -> String {
        match self {
            BroadcastMessage::ProfileString { id, .. } => format!("profile-string-{}", id),
        }
    }

    pub fn data(&self) -> String {
        match self {
            BroadcastMessage::ProfileString { profile_string, .. } => profile_string.clone(),
        }
    }
}

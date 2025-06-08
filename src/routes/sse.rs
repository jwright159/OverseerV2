use std::time::Duration;

use axum::Extension;
use axum::response::sse::Event;
use axum::response::{IntoResponse, Sse};
use tokio::sync::broadcast::{Receiver, Sender};
use tracing::debug;

use crate::broadcast::BroadcastMessage;
use crate::error::Result;

pub async fn sse_get(Extension(sse): Extension<Sender<BroadcastMessage>>) -> impl IntoResponse {
    async fn get_valid_single(sub: &mut Receiver<BroadcastMessage>) -> Result<Event> {
        let msg = sub.recv().await?;
        let event = Event::default().event(msg.name()).data(msg.data());
        debug!("Sending SSE event: {:?}", event);
        Ok(event)
    }

    let sub = sse.subscribe();
    let stream = futures::stream::unfold(sub, async move |mut sub| {
        Some((get_valid_single(&mut sub).await, sub))
    });

    let mut res = Sse::new(stream)
        .keep_alive(
            axum::response::sse::KeepAlive::new()
                .interval(Duration::from_secs(1))
                .text("keep-alive-text"),
        )
        .into_response();
    // Prevent nginx from slowing everything down a ton
    res.headers_mut()
        .insert("X-Accel-Buffering", "no".parse().unwrap());
    res
}

use std::collections::HashMap;
use std::fmt::Display;
use std::hash::Hash;
use std::path::Path;

use async_trait::async_trait;
use axum_login::tower_sessions::cookie::time::{Duration, OffsetDateTime};
use axum_login::tower_sessions::session::{Id, Record};
use axum_login::tower_sessions::{SessionStore, session_store};
use sqlx::types::JsonValue;

use crate::error::Result;

#[derive(Clone, Debug, Default)]
pub struct PhpStore;

#[async_trait]
impl SessionStore for PhpStore {
    async fn create(&self, record: &mut Record) -> session_store::Result<()> {
        let mut session_path = get_session_path(&record.id.to_string())
            .map_err(|e| session_store::Error::Backend(e.to_string()))?;
        while Path::new(&session_path).exists() {
            // Session ID collision mitigation.
            record.id = Id::default();
            session_path = get_session_path(&record.id.to_string())
                .map_err(|e| session_store::Error::Backend(e.to_string()))?;
        }
        save_session(
            record.id.to_string(),
            record
                .data
                .iter()
                .map(|(k, v)| (k.clone(), PhpValue::from(v.clone())))
                .collect::<HashMap<_, _>>(),
        )
        .map_err(|e| session_store::Error::Encode(e.to_string()))
    }

    async fn save(&self, record: &Record) -> session_store::Result<()> {
        save_session(
            record.id.to_string(),
            record
                .data
                .iter()
                .map(|(k, v)| (k.clone(), PhpValue::from(v.clone())))
                .collect::<HashMap<_, _>>(),
        )
        .map_err(|e| session_store::Error::Encode(e.to_string()))
    }

    async fn load(&self, session_id: &Id) -> session_store::Result<Option<Record>> {
        load_session(session_id.to_string())
            .map(|data| match data {
                None => None,
                Some(data) => {
                    let data = data
                        .into_iter()
                        .map(|(k, v)| (k, v.into()))
                        .collect::<HashMap<_, _>>();
                    Some(Record {
                        id: *session_id,
                        data,
                        expiry_date: OffsetDateTime::now_utc()
                            .checked_add(Duration::days(1))
                            .unwrap(),
                    })
                }
            })
            .map_err(|e| session_store::Error::Decode(e.to_string()))
    }

    async fn delete(&self, session_id: &Id) -> session_store::Result<()> {
        let session_path = get_session_path(&session_id.to_string())
            .map_err(|e| session_store::Error::Backend(e.to_string()))?;
        if Path::new(&session_path).exists() {
            std::fs::remove_file(session_path)
                .map_err(|e| session_store::Error::Backend(e.to_string()))
        } else {
            Ok(())
        }
    }
}

#[derive(Debug, Clone)]
pub enum PhpValue {
    String(String),
    Integer(i64),
    Float(f64),
    Boolean(bool),
    Array(HashMap<PhpValue, PhpValue>),
    Null,
}
impl PartialEq for PhpValue {
    fn eq(&self, other: &Self) -> bool {
        match (self, other) {
            (PhpValue::String(a), PhpValue::String(b)) => a == b,
            (PhpValue::Integer(a), PhpValue::Integer(b)) => a == b,
            (PhpValue::Float(a), PhpValue::Float(b)) => a == b,
            (PhpValue::Boolean(a), PhpValue::Boolean(b)) => a == b,
            (PhpValue::Array(a), PhpValue::Array(b)) => {
                a.len() == b.len() && a.iter().all(|(k, v)| b.get(k) == Some(v))
            }
            (PhpValue::Null, PhpValue::Null) => true,
            _ => false,
        }
    }
}
impl Eq for PhpValue {}
impl Hash for PhpValue {
    fn hash<H: std::hash::Hasher>(&self, state: &mut H) {
        match self {
            PhpValue::String(s) => s.hash(state),
            PhpValue::Integer(i) => i.hash(state),
            PhpValue::Float(f) => f.to_bits().hash(state),
            PhpValue::Boolean(b) => b.hash(state),
            PhpValue::Array(map) => {
                for (k, v) in map {
                    k.hash(state);
                    v.hash(state);
                }
            }
            PhpValue::Null => 0_u8.hash(state),
        }
    }
}
impl Display for PhpValue {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        match self {
            PhpValue::String(s) => write!(f, "{}", s),
            PhpValue::Integer(i) => write!(f, "{}", i),
            PhpValue::Float(fl) => write!(f, "{}", fl),
            PhpValue::Boolean(b) => write!(f, "{}", b),
            PhpValue::Array(map) => {
                let mut entries: Vec<String> =
                    map.iter().map(|(k, v)| format!("{} => {}", k, v)).collect();
                entries.sort();
                write!(f, "[{}]", entries.join(", "))
            }
            PhpValue::Null => write!(f, "null"),
        }
    }
}
impl From<JsonValue> for PhpValue {
    fn from(value: JsonValue) -> Self {
        match value {
            JsonValue::String(s) => PhpValue::String(s),
            JsonValue::Number(n) if n.is_i64() => PhpValue::Integer(n.as_i64().unwrap()),
            JsonValue::Number(n) => PhpValue::Float(n.as_f64().unwrap()),
            JsonValue::Bool(b) => PhpValue::Boolean(b),
            JsonValue::Object(map) => {
                let mut php_map = HashMap::new();
                for (k, v) in map {
                    php_map.insert(PhpValue::String(k), v.into());
                }
                PhpValue::Array(php_map)
            }
            JsonValue::Null => PhpValue::Null,
            JsonValue::Array(arr) => {
                let mut php_array = HashMap::new();
                for (i, v) in arr.into_iter().enumerate() {
                    php_array.insert(PhpValue::Integer(i as i64), v.into());
                }
                PhpValue::Array(php_array)
            }
        }
    }
}
impl From<PhpValue> for JsonValue {
    fn from(value: PhpValue) -> Self {
        match value {
            PhpValue::String(s) => JsonValue::String(s),
            PhpValue::Integer(i) => JsonValue::Number(serde_json::Number::from(i)),
            PhpValue::Float(f) => JsonValue::Number(serde_json::Number::from_f64(f).unwrap()),
            PhpValue::Boolean(b) => JsonValue::Bool(b),
            PhpValue::Array(map) => {
                let mut json_map = serde_json::Map::new();
                for (k, v) in map {
                    json_map.insert(k.to_string(), v.into());
                }
                JsonValue::Object(json_map)
            }
            PhpValue::Null => JsonValue::Null,
        }
    }
}

#[derive(Debug, thiserror::Error)]
pub enum Error {
    ExpectedPipe,
    ExpectedColon,
    ExpectedSemicolon,
    ExpectedQuote,
    ExpectedOpenBrace,
    ExpectedCloseBrace,
    UnknownDatatype(String),
}
impl Display for Error {
    fn fmt(&self, f: &mut std::fmt::Formatter<'_>) -> std::fmt::Result {
        match self {
            Error::ExpectedPipe => write!(f, "expected pipe character '|'"),
            Error::ExpectedColon => write!(f, "expected colon character ':'"),
            Error::ExpectedSemicolon => write!(f, "expected semicolon character ';'"),
            Error::ExpectedQuote => write!(f, "expected quote character '\"'"),
            Error::ExpectedOpenBrace => write!(f, "expected open brace character '{{'"),
            Error::ExpectedCloseBrace => write!(f, "expected close brace character '}}'"),
            Error::UnknownDatatype(datatype) => write!(f, "unknown datatype '{}'", datatype),
        }
    }
}

pub fn load_session(session_id: String) -> Result<Option<HashMap<String, PhpValue>>> {
    let session_path = get_session_path(&session_id)?;
    if !Path::new(&session_path).exists() {
        return Ok(None);
    }
    let session_data = std::fs::read_to_string(session_path)?;
    deserialize_session(session_data.as_str()).map(Some)
}

pub fn deserialize_session(session_data: &str) -> Result<HashMap<String, PhpValue>> {
    let mut session_data = session_data;
    let mut session_map = HashMap::new();
    while !session_data.is_empty() {
        let (name, value, session_data_) = deserialize_key_value(session_data)?;
        session_map.insert(name.to_string(), value);
        session_data = session_data_;
    }
    Ok(session_map)
}

pub fn deserialize_key_value(session_data: &str) -> Result<(String, PhpValue, &str)> {
    let (name, session_data) = session_data.split_once('|').ok_or(Error::ExpectedPipe)?;
    let (value, session_data) = deserialize_value(session_data)?;
    Ok((name.to_string(), value, session_data))
}

pub fn deserialize_value(session_data: &str) -> Result<(PhpValue, &str)> {
    let (datatype, session_data) = session_data.split_once(':').ok_or(Error::ExpectedColon)?;
    match datatype {
        "i" => {
            let (value, session_data) = session_data
                .split_once(';')
                .ok_or(Error::ExpectedSemicolon)?;
            let value = value.parse::<i64>()?;
            Ok((PhpValue::Integer(value), session_data))
        }
        "d" => {
            let (value, session_data) = session_data
                .split_once(';')
                .ok_or(Error::ExpectedSemicolon)?;
            let value = value.parse::<f64>()?;
            Ok((PhpValue::Float(value), session_data))
        }
        "b" => {
            let (value, session_data) = session_data
                .split_once(';')
                .ok_or(Error::ExpectedSemicolon)?;
            let value = value.parse::<i64>()? != 0;
            Ok((PhpValue::Boolean(value), session_data))
        }
        "s" => {
            let (length, session_data) = session_data
                .split_once(':')
                .ok_or(Error::ExpectedSemicolon)?;
            let length: usize = length.parse()?;
            let (_, session_data) = session_data.split_once('"').ok_or(Error::ExpectedQuote)?;
            let (value, session_data) = session_data.split_at(length);
            let (_, session_data) = session_data.split_once('"').ok_or(Error::ExpectedQuote)?;
            let (_, session_data) = session_data
                .split_once(';')
                .ok_or(Error::ExpectedSemicolon)?;
            Ok((PhpValue::String(value.to_string()), session_data))
        }
        "a" => {
            let mut map = HashMap::new();
            let (length, session_data) = session_data
                .split_once(':')
                .ok_or(Error::ExpectedSemicolon)?;
            let length: usize = length.parse()?;
            let (_, mut session_data) = session_data
                .split_once('{')
                .ok_or(Error::ExpectedOpenBrace)?;
            for _ in 0..length {
                let (key, session_data_) = deserialize_value(session_data)?;
                let (value, session_data_) = deserialize_value(session_data_)?;
                map.insert(key, value);
                session_data = session_data_;
            }
            let (_, session_data) = session_data
                .split_once('}')
                .ok_or(Error::ExpectedCloseBrace)?;
            Ok((PhpValue::Array(map), session_data))
        }
        "n" => Ok((PhpValue::Null, session_data)),
        _ => Err(Error::UnknownDatatype(datatype.to_string()).into()),
    }
}

pub fn get_session_path(session_id: &str) -> Result<String> {
    let root = std::env::var("OVERSEER_PHP_SESSIONS_ROOT")?;
    Ok(format!("{}/sess_{}", root, session_id))
}

pub fn save_session(session_id: String, session_data: HashMap<String, PhpValue>) -> Result<()> {
    let session_path = get_session_path(&session_id)?;
    let session_data = serialize_session(session_data)?;
    std::fs::write(session_path, session_data).map_err(|e| e.into())
}

pub fn serialize_session(session_data: HashMap<String, PhpValue>) -> Result<String> {
    let mut session_str = String::new();
    for (name, value) in session_data {
        session_str.push_str(&serialize_key_value(&name, &value)?);
    }
    Ok(session_str)
}

pub fn serialize_key_value(name: &str, value: &PhpValue) -> Result<String> {
    Ok(format!("{}|{}", name, serialize_value(value)?))
}

pub fn serialize_value(value: &PhpValue) -> Result<String> {
    match value {
        PhpValue::String(s) => Ok(format!("s:{}:\"{}\";", s.len(), s)),
        PhpValue::Integer(i) => Ok(format!("i:{};", i)),
        PhpValue::Float(f) => Ok(format!("d:{};", f)),
        PhpValue::Boolean(b) => Ok(format!("b:{};", if *b { 1 } else { 0 })),
        PhpValue::Array(map) => {
            let mut entries = String::new();
            for (k, v) in map {
                entries.push_str(&format!("{}{}", serialize_value(k)?, serialize_value(v)?));
            }
            Ok(format!("a:{}:{{{}}};", map.len(), entries))
        }
        PhpValue::Null => Ok("n;".to_string()),
    }
}

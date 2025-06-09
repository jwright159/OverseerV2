use std::borrow::Cow;
use std::fmt::{Display, Formatter};
use std::str::FromStr;
use std::str::pattern::Pattern;
use include_lines::include_lines;
use itertools::Itertools;
use rand::seq::IndexedRandom;
use crate::error::Error;

const GLITCH_STATUSES: &[&str] = &include_lines!("glitches.txt");

fn generate_glitch_string() -> String {
    let horrible_mess = |_, _, _| -> String {
        const CHAR_ARRAY: &[&str] = &["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z",
                                      "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z",
                                      "!", "@", "#", "$", "%", "^", "&", "*", "+", "=", "?", "/", "\\", " "];

        let what_is_it = rand::random_range(1..4);
        let rand = CHAR_ARRAY.choose(&mut rand::rng()).unwrap();
        let mut result = String::new();

        for _ in (1..=rand::random_range(5..15)).rev() {
            if what_is_it == 1 {
                result.push_str(rand);
            } else {
                result.push_str(CHAR_ARRAY.choose(&mut rand::rng()).unwrap());
            }
        }

        result
    };

    GLITCH_STATUSES
        .choose(&mut rand::rng())
        .unwrap_or(&"We're not very creative :/")
        .replace_with("GLITCH", horrible_mess)
        .to_string()
}

#[derive(Debug, Clone)]
pub enum StrifeStatusType {
    Timestop,
    Hopeless,
    Knockdown,
    WateryGel,
    Poison,
    Bleeding,
    Disoriented,
    Distracted,
    Enraged,
    Mellow,
    Glitched,
    Charmed,
    Delayed,
    Pinata,
    Unlucky,
    HasEffect(String),
    HasResist(String),
    Inheritance,
    Isolated,
    Burning,
    Unstuck,
    Regeneration,
    Energized,
    Recovery,
    BoostDrain,
    Frozen,
    Shrunk,
    Paralyzed,
    Irradiated,
    SelfInflict(String),
    LuckBoost(String),
    CantAttack,
    CantDefend,
    #[allow(dead_code)]
    Unknown(String, Option<String>)
}

#[derive(Debug, Clone)]
pub struct StrifeStatus {
    pub status_type: StrifeStatusType,
    pub duration: Option<i64>
}

impl FromStr for StrifeStatus {
    type Err = Error;

    fn from_str(s: &str) -> Result<Self, Self::Err> {
        let (name, duration, value) = s.split(':')
            .collect_array::<3>()
            .map(|[n, d, v]| (n, d, Some(v)))
            .or_else(|| s.split(':').collect_array::<2>().map(|[n, d]| (n, d, None)))
            .ok_or(Error::TupleParse("tuple destructuring yielded incorrect element count".to_string()))?;

        let duration = match duration.parse::<i64>().ok() {
            Some(duration) if duration != 0 => Some(duration),
            _ => None
        };

        use StrifeStatusType::*;

        let status_type = match name {
            "TIMESTOP" => Timestop,
            "HOPELESS" => Hopeless,
            "KNOCKDOWN" => Knockdown,
            "WATERYGEL" => WateryGel,
            "POISON" => Poison,
            "BLEEDING" => Bleeding,
            "DISORIENTED" => Disoriented,
            "DISTRACTED" => Distracted,
            "ENRAGED" => Enraged,
            "MELLOW" => Mellow,
            "GLITCHED" => Glitched,
            "CHARMED" => Charmed,
            "DELAYED" => Delayed,
            "PINATA" => Pinata,
            "UNLUCKY" => Unlucky,
            "HASEFFECT" => {
                let effect = value
                    .ok_or(Error::TupleParse("Attempting to construct HasEffect with no effect".to_string()))?
                    .split("@").collect::<Vec<&str>>();
                HasEffect(effect[0].to_string())
            },
            "HASRESIST" => {
                let effect = value
                    .ok_or(Error::TupleParse("Attempting to construct HasResist with no effect".to_string()))?
                    .split("@").collect::<Vec<&str>>();
                HasResist(effect[0].to_string())
            },
            "INHERITANCE" => Inheritance,
            "ISOLATED" => Isolated,
            "BURNING" => Burning,
            "UNSTUCK" => Unstuck,
            "REGENERATION" => Regeneration,
            "ENERGIZED" => Energized,
            "RECOVERY" => Recovery,
            "BOOSTDRAIN" => BoostDrain,
            "FROZEN" => Frozen,
            "SHRUNK" => Shrunk,
            "PARALYZED" => Paralyzed,
            "IRRADIATED" => Irradiated,
            "SELFINFLICT" => {
                let effect = value
                    .ok_or(Error::TupleParse("Attempting to construct HasResist with no effect".to_string()))?
                    .split("@").collect::<Vec<&str>>();
                SelfInflict(effect[0].to_string())
            },
            "LUCKBOOST" => LuckBoost(value
                .ok_or(Error::TupleParse("Attempting to construct LuckBoost with no boost percentage".to_string()))?
                .to_string()),
            "CANTATTACK" => CantAttack,
            "CANTDEFEND" => CantDefend,
            _ => Unknown(name.to_string(), value.map(str::to_string)),
        };

        Ok(StrifeStatus { status_type, duration })
    }
}

impl Display for StrifeStatus {
    fn fmt(&self, f: &mut Formatter<'_>) -> std::fmt::Result {
        match &self.status_type {
            StrifeStatusType::Timestop => write!(f, "Timestop: This strifer is frozen in time.")?,
            StrifeStatusType::Hopeless => write!(f, "Hopeless: This strifer does not believe in itself.")?,
            StrifeStatusType::Knockdown => write!(f, "Knocked over: This strifer needs to get back on its feet or feet analogue.")?,
            StrifeStatusType::WateryGel => write!(f, "Watery health gel: This strifer's health vial is currently easier to dislodge.")?,
            StrifeStatusType::Poison => write!(f, "Poisoned: This strifer is suffering from poison.")?,
            StrifeStatusType::Bleeding => write!(f, "Bleeding: This strifer has a nasty-looking wound.")?,
            StrifeStatusType::Disoriented => write!(f, "Disoriented: This strifer looks kind of dazed and isn't cooperating effectively.")?,
            StrifeStatusType::Distracted => write!(f, "Distracted: This strifer has been distracted by a distaction you mean distraction.")?,
            StrifeStatusType::Enraged => write!(f, "Enraged: This strifer is behaving unusually recklessly.")?,
            StrifeStatusType::Mellow => write!(f, "Mellowed Out: This strifer seem unusually laid back for someone involved in a fight to the death.")?,
            StrifeStatusType::Glitched => write!(f, "Glitched Out: {}", generate_glitch_string())?,
            StrifeStatusType::Charmed => write!(f, "Charmed: This strifer is fighting for a different side!")?,
            StrifeStatusType::Delayed => write!(f, "???: This strifer is going to be affected by an unknown status effect in...")?,
            StrifeStatusType::Pinata => write!(f, "This strifer has been replaced by a small replica of itself. Taped to the replica is a note saying \"Pinata. Enjoy! -The Management\"")?,
            StrifeStatusType::Unlucky => write!(f, "Unlucky: This strifer appears unlucky. Huh? What does unlucky look like? How should I know?")?,
            StrifeStatusType::HasEffect(effect) => write!(f, "Empowered: This strifer possesses the {} on-hit effect.", effect)?,
            StrifeStatusType::HasResist(effect) => write!(f, "Resistant: This strifer is being artificially granted {} resistance.", effect)?,
            StrifeStatusType::Inheritance => write!(f, "Attuned: This strifer has focused on allowing their Aspect to permeate them and will reap the benefits this round.")?,
            StrifeStatusType::Isolated => write!(f, "Isolated: This strifer is unable to fight with their allies at present.")?,
            StrifeStatusType::Burning => write!(f, "On Fire: Do you really need any further explanation?")?,
            StrifeStatusType::Unstuck => write!(f, "Unstuck: This strifer's fakeness attribute is fluctuating wildly!")?,
            StrifeStatusType::Regeneration => write!(f, "Regeneration: This strifer is regaining health every round.")?,
            StrifeStatusType::Energized => write!(f, "Energized: This strifer is regaining energy every round.")?,
            StrifeStatusType::Recovery => write!(f, "Power recovery: If their power is reduced below maximum, this strifer will regain some of their lost power every round.")?,
            StrifeStatusType::BoostDrain => write!(f, "Boost drain: This strifer's opponents, if boosted, will see their power boosts degrade every turn.")?,
            StrifeStatusType::Frozen => write!(f, "Frozen: This strifer is frozen solid!")?,
            StrifeStatusType::Shrunk => write!(f, "Shrunk: This strifer has been reduced in size.")?,
            StrifeStatusType::Paralyzed => write!(f, "Paralyzed: This strifer may not be able to act.")?,
            StrifeStatusType::Irradiated => write!(f, "Irradiated: This strifer has been afflicted with radiation.")?,
            StrifeStatusType::SelfInflict(effect) => write!(f, "Afflicted?: This strifer is being hit with the {} status effect at random.", effect)?,
            StrifeStatusType::LuckBoost(percentage) => write!(f, "Luck Boosted: This strifer is {}% luckier! This has a very distinct visual cue that I'm not going to tell you about.", percentage)?,
            StrifeStatusType::Unknown(name, _) => write!(f, "No message for status {}. This is probably a bug, please submit a report!", name)?,
            _ => {},
        };

        write!(f, " Duration: ")?;
        if let Some(duration) = self.duration {
            write!(f, "{} turn(s).", duration)?;
        } else {
            write!(f, "Entire strife.")?;
        }
        write!(f, "<br />")?;

        Ok(())
    }
}

#[derive(Debug, Clone, PartialEq, Eq, Hash)]
pub enum StrifeBonusType {
    Power,
    Offense,
    Defense,
    Aggrieve,
    Aggress,
    Assail,
    Assault,
    Abuse,
    Accuse,
    Abjure,
    Abstain,
    Unknown,
}

impl StrifeBonusType {
    pub fn key(&self) -> &'static str {
        match self {
            StrifeBonusType::Power => "POWER",
            StrifeBonusType::Offense => "OFFENSE",
            StrifeBonusType::Defense => "DEFENSE",
            StrifeBonusType::Aggrieve => "AGGRIEVE",
            StrifeBonusType::Aggress => "AGGRESS",
            StrifeBonusType::Assail => "ASSAIL",
            StrifeBonusType::Assault => "ASSAULT",
            StrifeBonusType::Abuse => "ABUSE",
            StrifeBonusType::Accuse => "ACCUSE",
            StrifeBonusType::Abjure => "ABJURE",
            StrifeBonusType::Abstain => "ABSTAIN",
            StrifeBonusType::Unknown => "UNKNOWN"
        }
    }
}

#[derive(Debug, Clone)]
pub struct StrifeBonus {
    pub key: String,
    pub bonus_type: StrifeBonusType,
    pub duration: Option<i64>,
    pub value: i64
}

impl FromStr for StrifeBonus {
    type Err = Error;

    fn from_str(s: &str) -> Result<Self, Self::Err> {
        let [name, duration, value] = s.split(':').collect_array::<3>().ok_or(Error::TupleParse("tuple destructuring yielded incorrect element count".to_string()))?;

        let duration = match duration.parse::<i64>() {
            Ok(duration) if duration != 0 => Some(duration),
            _ => None
        };

        use StrifeBonusType::*;

        let bonus_type = match name {
            "POWER" => Power,
            "OFFENSE" => Offense,
            "DEFENSE" => Defense,
            "AGGRIEVE" => Aggrieve,
            "AGGRESS" => Aggress,
            "ASSAIL" => Assail,
            "ASSAULT" => Assault,
            "ABUSE" => Abuse,
            "ACCUSE" => Accuse,
            "ABJURE" => Abjure,
            "ABSTAIN" => Abstain,
            _ => Unknown
        };


        Ok(StrifeBonus { key: name.to_string(), bonus_type, duration, value: value.parse()? })
    }
}

impl Display for StrifeBonus {
    fn fmt(&self, f: &mut Formatter<'_>) -> std::fmt::Result {
        match &self.bonus_type {
            StrifeBonusType::Power => write!(f, "Power boost.")?,
            StrifeBonusType::Offense => write!(f, "Offense boost.")?,
            StrifeBonusType::Defense => write!(f, "Defense boost.")?,
            StrifeBonusType::Aggrieve => write!(f, "Aggrieve boost.")?,
            StrifeBonusType::Aggress => write!(f, "Aggress boost.")?,
            StrifeBonusType::Assail => write!(f, "Assail boost.")?,
            StrifeBonusType::Assault => write!(f, "Assault boost.")?,
            StrifeBonusType::Abuse => write!(f, "Abuse boost.")?,
            StrifeBonusType::Accuse => write!(f, "Accuse boost.")?,
            StrifeBonusType::Abjure => write!(f, "Abjure boost.")?,
            StrifeBonusType::Abstain => write!(f, "Abstain boost.")?,
            StrifeBonusType::Unknown => write!(f, "No message for bonus {}. THis is probably a bug, please submit a report!", self.key.as_str())?,
        }

        write!(f, " Duration: ")?;
        if let Some(duration) = self.duration {
            write!(f, "{} turn(s).", duration)?;
        } else {
            write!(f, "Entire strife.")?;
        }
        write!(f, "<br />")?;

        Ok(())
    }
}

trait StrExt {
    #[allow(clippy::needless_lifetimes)]
    fn replace_with<'a, P, F, S>(&'a self, pattern: P, replacer: F) -> Cow<'a, str>
    where
        P: Pattern,
        F: FnMut(usize, usize, &'a str) -> S,
        S: AsRef<str>;
}

impl StrExt for str {
    #[allow(clippy::needless_lifetimes)]
    fn replace_with<'a, P, F, S>(&'a self, pattern: P, mut replacer: F) -> Cow<'a, str>
    where
        P: Pattern,
        F: FnMut(usize, usize, &'a str) -> S,
        S: AsRef<str>
    {
        let mut result = String::new();
        let mut lastpos = 0;

        for (idx, (pos, substr)) in self.match_indices(pattern).enumerate() {
            result.push_str(&self[lastpos..pos]);
            lastpos = pos + substr.len();
            let replacement = replacer(idx, pos, substr);
            result.push_str(replacement.as_ref());
        }

        if lastpos == 0 {
            Cow::Borrowed(self)
        } else {
            result.push_str(&self[lastpos..]);
            Cow::Owned(result)
        }
    }
}
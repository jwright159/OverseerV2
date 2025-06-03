pub struct Achievement {
    pub id: String,
    pub name: String,
    pub description: String,
}

pub fn get_achievements() -> Vec<Achievement> {
    vec![
        Achievement {
            id: "medium".to_string(),
            name: "[S] Enter".to_string(),
            description: "Enter the medium".to_string(),
        },
        Achievement {
            id: "ko".to_string(),
            name: "You Tried".to_string(),
            description: "Get KO'd".to_string(),
        },
        Achievement {
            id: "fatigue".to_string(),
            name: "Weekend At Player's".to_string(),
            description: "It's time to stop clicking".to_string(),
        },
        Achievement {
            id: "deadconsort".to_string(),
            name: "Black Liquid Sorrow".to_string(),
            description: "Visit the grave of a consort mercenary".to_string(),
        },
        Achievement {
            id: "itemfull".to_string(),
            name: "Act 1 Nostalgia".to_string(),
            description: "Fill your inventory".to_string(),
        },
        Achievement {
            id: "assist".to_string(),
            name: "Game Bro".to_string(),
            description: "Assist a fellow player on a strife and win".to_string(),
        },
        Achievement {
            id: "aspectheal".to_string(),
            name: "Doctor Remix".to_string(),
            description: "Heal a fellow player by using an Aspect Pattern".to_string(),
        },
        Achievement {
            id: "allgrist".to_string(),
            name: "Colours And Mayhem".to_string(),
            description: "Recycle a Perfectly Unique Object".to_string(),
        },
        Achievement {
            id: "boonshop".to_string(),
            name: "LODS OF BOONE".to_string(),
            description: "Spend one hundred Boonbucks on the Consort Shop".to_string(),
        },
        Achievement {
            id: "fray3".to_string(),
            name: "Fraymothree In The Morning".to_string(),
            description: "Get the full set of your Aspect's Fraymotifs".to_string(),
        },
        Achievement {
            id: "fullport".to_string(),
            name: "Like Fucking Christmas Up In Here".to_string(),
            description: "Get fully equipped".to_string(),
        },
        Achievement {
            id: "ultweapon".to_string(),
            name: "Nonanonacontanonactanonaliagonal Ultimatum".to_string(),
            description: "Equip an Ultimate Weapon".to_string(),
        },
        Achievement {
            id: "topeche".to_string(),
            name: "Sike, That's The Right Number".to_string(),
            description: "Reach rung 612 of the Echeladder".to_string(),
        },
        Achievement {
            id: "dungeon1".to_string(),
            name: "Tentacle Therapist".to_string(),
            description: "Kill the Kraken".to_string(),
        },
        Achievement {
            id: "dungeon2".to_string(),
            name: "Here Come The Arms".to_string(),
            description: "Kill the Hekatonchire".to_string(),
        },
        Achievement {
            id: "dungeon3".to_string(),
            name: "Killer Queen".to_string(),
            description: "Kill the Lich Queen".to_string(),
        },
        Achievement {
            id: "moonprince".to_string(),
            name: "Princes Of The Incipisphere".to_string(),
            description: "Defeat a full set of the best your moon has to offer".to_string(),
        },
        Achievement {
            id: "gate7".to_string(),
            name: "Clientship Aneurysm".to_string(),
            description: "Build your client's house up to Gate 7".to_string(),
        },
        Achievement {
            id: "denizen".to_string(),
            name: "Screw The Choice".to_string(),
            description: "Defeat your denizen".to_string(),
        },
        Achievement {
            id: "thebug".to_string(),
            name: "Achievement Name".to_string(),
            description: "Face The Bug".to_string(),
        },
        Achievement {
            id: "itemsub".to_string(),
            name: "Not Another Sword".to_string(),
            description: "Submit a non-QIC item and get it greenlit".to_string(),
        },
        Achievement {
            id: "artsub".to_string(),
            name: "Not Another Sword Pic".to_string(),
            description: "Submit art for an item and get it approved".to_string(),
        },
    ]
}

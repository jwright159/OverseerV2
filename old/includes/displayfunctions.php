<?php
function displayStatus($statustr) {
	$statuses = explode('|', $statustr);
	$i = 0;
	while (!empty($statuses[$i])) {
		$currentstatus = explode(':', $statuses[$i]);
		switch ($currentstatus[0]) {
			case "TIMESTOP":
				echo "Timestop: This strifer is frozen in time.";
				break;
			case "HOPELESS":
				echo "Hopeless: This strifer does not believe in itself.";
				break;
			case "KNOCKDOWN":
				echo "Knocked over: This strifer needs to get back on its feet or feet analogue.";
				break;
			case "WATERYGEL":
				echo "Watery health gel: This strifer's health vial is currently easier to dislodge.";
				break;
			case "POISON":
				echo "Poisoned: This strifer is suffering from poison.";
				break;
			case "BLEEDING":
				echo "Bleeding: This strifer has a nasty-looking wound.";
				break;
			case "DISORIENTED":
				echo "Disoriented: This strifer looks kind of dazed and isn't cooperating effectively.";
				break;
			case "DISTRACTED":
				echo "Distracted: This strifer has been distracted by a distaction you mean distraction.";
				break;
			case "ENRAGED":
				echo "Enraged: This strifer is behaving unusually recklessly.";
				break;
			case "MELLOW":
				echo "Mellowed Out: This strifer seems unusually laid back for someone involved in a fight to the death.";
				break;
			case "GLITCHED":
				echo "Glitched Out: " . generateStatusGlitchString();
				break;
			case "CHARMED":
				echo "Charmed: This strifer is fighting for a different side!";
				break;
			case "DELAYED":
				echo "???: This strifer is going to be affected by an unknown status effect in...";
				break;
			case "PINATA":
				echo 'This strifer has been replaced by a small replica of itself. Taped to the replica is a note saying "Pinata. Enjoy! -The Management"';
				break;
			case "UNLUCKY":
				echo "Unlucky: This strifer appears unlucky. Huh? What does unlucky look like? How should I know?";
				break;
			case "HASEFFECT":
				$effect = explode("@", $currentstatus[2]);
				echo "Empowered: This strifer possesses the $effect[0] on-hit effect.";
				break;
			case "HASRESIST":
				$effect = explode("@", $currentstatus[2]);
				echo "Resistant: This strifer is being artificially granted $effect[0] resistance.";
				break;
			case "INHERITANCE":
				echo "Attuned: This strifer has focused on allowing their Aspect to permeate them and will reap the benefits this round.";
				break;
			case "ISOLATED":
				echo "Isolated: This strifer is unable to fight with their allies at present.";
				break;
			case "BURNING":
				echo "On Fire: Do you really need any further explanation?";
				break;
			case "UNSTUCK":
				echo "Unstuck: This strifer's fakeness attribute is fluctuating wildly!";
				break;
			case "REGENERATION":
				echo "Regeneration: This strifer is regaining health every round.";
				break;
			case "ENERGIZED":
				echo "Energized: This strifer is regaining energy every round.";
				break;
			case "RECOVERY":
				echo "Power recovery: If their power is reduced below maximum, this strifer will regain some of their lost power every round.";
				break;
			case "BOOSTDRAIN":
				echo "Boost drain: This strifer's opponents, if boosted, will see their power boosts degrade every turn.";
				break;
			case "FROZEN":
				echo "Frozen: This strifer is frozen solid!";
				break;
			case "SHRUNK":
				echo "Shrunk: This strifer has been reduced in size.";
				break;
			case "PARALYZED":
				echo "Paralyzed: This strifer may not be able to act.";
				break;
			case "IRRADIATED":
				echo "Irradiated: This strifer has been afflicted with radiation.";
				break;
			case "SELFINFLICT":
				$effect = explode("@", $currentstatus[2]);
				echo "Afflicted?: This strifer is getting hit with the $effect[0] status effect at random.";
				break;
			case "LUCKBOOST":
				echo "Luck Boosted: This strifer is $currentstatus[2]% luckier! This has a very distinct visual cue that I'm not going to tell you about.";
				break;
			default:
				echo "No message for status $currentstatus[0]. This is probably a bug, please submit a report!";
				break;
		}
		if (intval($currentstatus[1]) != 0) {
			echo " Duration: $currentstatus[1] turn(s).<br />"; //Line break and duration after each status message.
		} else { //Duration of 0 represents no expiry
			echo " Duration: Entire strife.<br />";
		}
		$i++;
	}
}
function displayBonus($bonustr) {
	$bonuses = explode('|', $bonustr);
	$i = 0;
	while (!empty($bonuses[$i])) {
		$currentbonus = explode(':', $bonuses[$i]);
		if ($currentbonus[2] != "0") { //Bonus actually has a value
			switch ($currentbonus[0]) {
				case "POWER":
					echo "Power boost. ";
					break;
				case "OFFENSE":
					echo "Offense boost. ";
					break;
				case "DEFENSE":
					echo "Defense boost. ";
					break;
				case "AGGRIEVE":
					echo "Aggrieve boost. ";
					break;
				case "AGGRESS":
					echo "Aggress boost. ";
					break;
				case "ASSAIL":
					echo "Assail boost. ";
					break;
				case "ASSAULT":
					echo "Assault boost. ";
					break;
				case "ABUSE":
					echo "Abuse boost. ";
					break;
				case "ACCUSE":
					echo "Accuse boost. ";
					break;
				case "ABJURE":
					echo "Abjure boost. ";
					break;
				case "ABSTAIN":
					echo "Abstain boost. ";
					break;
			default:
					echo "No message for bonus $currentbonus[0]. This is probably a bug, please submit a report!";
					break;
			}
			echo " Value: $currentbonus[2]. ";
			if (intval($currentbonus[1]) != 0) {
				echo " Duration: $currentbonus[1] turn(s).<br />"; //Line break and duration after each status message.
			} else { //Duration of 0 represents no expiry
				echo " Duration: Entire strife.<br />";
			}
		}
		$i++;
	}
}
function fraymotifMessage($strifer) { //Function takes a strifer and prints out the deets on their current fraymotif
	if(empty($strifer['currentmotif'])) return "Whoops! $strifer[name] isn't currently using a fraymotif.<br>";
	$message = "Now Playing: " . $strifer['currentmotif'] . ": " . $strifer['currentmotifname'] . "<br>";
	$message .= "User: $strifer[name]<br>";
	$message .= "Effects: ";
	//DATA SECTION: The description strings for the fraymotifs are stored here.
	switch($strifer['currentmotif']) {
		case "Breath/I":
			$message .= "The Breeze softens blows against the user, doubling their defense.";
			break;
		case "Heart/I":
			$message .= "Unlocks inner power, providing a large power boost this round with a smaller ongoing one.";
			break;
		case "Life/I":
			$message .= "The user is healed for 100% of their Health Vial this round, while allies are healed for 41.3%.";
			break;
		case "Hope/I":
			$message .= "Multiplies offense and defense by an amount increasing the healthier the user is.";
			break;
		case "Light/I":
			$message .= "The user gains +100% luck from the start of this strife round to the end of the strife.";
			break;
		case "Mind/I":
			$message .= "Enemies the user damages will suffer extra flat damage and a power reduction.";
			break;
		case "Blood/I":
			$message .= "Enemies damaged by the user will suffer extra flat damage and minor bleeding. Enemies so damaged will bolster the morale of all allies, providing a power boost.";
			break;
		case "Doom/I":
			$message .= "The user deals bonus damage against injured opponents.";
			break;
		case "Rage/I":
			$message .= "The user gains an offense multiplier based on their missing health, as well as a residual power boost for a few turns after this one.";
			break;
		case "Void/I":
			$message .= "Enemies damaged by the user suffer a series of glitchy effects, suffering power and health randomization.";
			break;
		case "Space/I":
			$message .= "The user's next attack gains a spatial warping property. If the user hits the enemy leader, they will suffer massive damage!";
			break;
		case "Time/I":
			$message .= "The user makes four attacks against every enemy this round.";
			break;
		case "Breath/II":
			$message .= "Empowers the user's blows to knock over and deal bonus damage to any enemy they successfully damage.";
			break;
		case "Heart/II":
			$message .= "Each enemy hit by the user suffers bonus damage based on their own power.";
			break;
		case "Life/II":
			$message .= "A massive surge of Life washes over the user and their allies. They are sure to be on full health at the end of the round.";
			break;
		case "Mind/II":
			$message .= "A sudden surge of insight, the perfect battle plan. The user and those under their command will not take combat damage this round, and will find themselves more effective.";
			break;
		case "Blood/II":
			$message .= "The user's strikes sever the bonds each enemy shares with their allies, temporarily nullifying their ability to use teamwork and applying a power reduction.";
			break;
		case "Doom/II":
			$message .= "If the user hits the enemy leader, they are isolated and suffer significant damage and hopelessness.";
			break;
		case "Rage/II":
			$message .= "Enemies struck by the user will temporarily join a random side in the strife, almost certainly placing them in opposition to all other combatants.";
			break;
		case "Void/II":
			$message .= "Enemies hit by the user become unstuck from reality, gaining a random chance to simply not exist on any given combat round.";
			break;
		case "Space/II":
			$message .= "This terminology can be quite literal sometimes. The user borrows the unfathomable heat of a star, setting struck foes ablaze and dealing bonus damage.";
			break;
		case "Time/II":
			$message .= "Freezes the strife in time for a round. All strifers but the user will fail to attack OR defend.";
			break;
		case "Light/II":
			$message .= "Any attack that connects will deal critical damage.";
			break;
		default:
			$message .= "Effects not yet implemented!";
			break;
	}
	$message .= "<br><br>";
	return $message;
}
?>
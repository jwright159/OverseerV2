<?php
function horribleMess(): string {
	$chararray = array(1 => "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", 
	"A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", 
	"!", "@", "#", "$", "%", "^", "&", "*", "+", "=", "?", "/", "\\", " ");
	$whatisit = rand(1,4);
	$rand = rand(1,count($chararray));
	$length = rand(5,15);
	$str = "";
	while ($length > 0) {
		if ($whatisit == 1) {
			$str = $str . $chararray[$rand];
		} else {
			$str = $str . $chararray[rand(1,count($chararray))];
		}
		$length--; //INFINITE LOOPS
	}
	return $str;
}
function generateGlitchString(): string|null { //DATA: Holds info on the glitch strings.
	$strarray = array(1 => "You and your opponents trade a series offffffGLITCH",	
	"You hit the GLITCH several times, and it falls over and begins twitching", 
	"GLITCH GLITCH GLITCH", 
	"The Denim GoblinGoblinGoblinGoblinGoblinGoblinGGGGGGGGGGGGGGGGGGLITCH", 
	"The SUPER APOSTROPHE 64 GLITCH\'\'\'\'\'\'\'\'\'", 
	"BEN is getting lonely...", 
	"The GLITCH suddenly gets hyper-realistic eyes and starts bleeding hyper-realistic blood", 
	"The GLITCH Please enter the name of the client player you wish to GLITCH", 
	"The ###########################################################################################################################:(){:|:&};:&%^#", 
	"The GLITCH spiGLITCH and glitch GLITCH throuh a waGLITCH", 
	"The Dirkbot GLITCH I'm sorry Dave, I can't let you do that", 
	"NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS NESS", 
	"The GLITCH tries to GLITCH CAN'T LET YOU DO THAT, STARFGLITCH", 
	"You cannot grasp the true form of GLITCH's attack!", 
	"It doesn't affect GLITCH...", 
	"The GLITCH BROKEN BROKEN I'M SO BROOOOOOOKEN *clank* *clank*", 
	"The GLITCH WANTS TO PLAY A GAME", 
	"BOW WOW WOW WOW WOW WOW WO- woah GLITCH", 
	"The TYPHEUS glitches through the PLUSH RUMPS, and GLITCH%(%#%:(){:|:&};:", 
	"The IRONIC COOL GUGLITCH GLITCH GLITCH PUPPET ASS GLITCHGLITCH", 
	"GLITCH ... is hurt by the burn!", 
	"Wild MEW appeared!", 
	"GLITCH TM TRAINER DITTO", 
	"It puts the lubricant in its GLITCH", 
	"The GLITCHGLITCH MY LITTLE PONY MY LITTLE PONY", 
	"Wild LONLINESS diediediediediediediediedied!",
	"The GLITCH sinks halfway into the floor and beings flailing around at random.",
	"GLITCH cancels Strife: Interrupted by GLITCH.",
	"not a statement",
	"DEBUG: GLITCHGLITCH",
	"Are you sure you wish to GLITCH your account? This cannot be unGLITCH",
	"Thank you GLITCH! But our GLITCH is in another castle!",
	"It's a sad thing that your GLITCHventures have GLITCHed here!!",
	"Congratulations! You have defeated the Black KGLITCHe fight before you are killed oGLITCHies of \"blows\".",
	"Buffalo buffalo buffalo buffalo buffalo buffalo juggalo GLITCHalo.",
	"The GLITCH has died of dysentery.",
	"GLITCHBlahdev (Developer): <font color=#CCCC00>I'd suggest a power level of GLITCH</font>",
	"GLITCHOVERSEER: <font color=#660066>STOP GLITCHING UP MY GAME!</font>",
	"GLITCH.exe has encountered a problem and must close. We're sorry for any GLITCH",
	"But it failed!",
	"The GLITCH briefly transforms into a GLITCH.",
	"Suddenly, GLITCH floats off of the alchemiter and summons a bunch of other GLITCH from seemingly nowhere, which spiral into a whirlwind of GLITCH",
	"An arm briefly comes out of a GLITCH in the GLITCH.",
	"GLITCH erupts into an absurd amount of GLITCH grist.",
	"GLITCH a blue box GLITCHGLITCH then fades GLITCH",
	"The GLITCH writes a letter to Troll SaGLITCHlaus.",
	"The REFRESH BUTTON breaks under the GLITCH.",
	"What are ya GLITCHing?",
	"Please enter your credit caGLITCH to continue.",
	"END OF FILE",
	"ThGLITCHGLITCH Stranger has disconnecGLITCH",
  "Your GLITCH is not good reading material.",
  "GLITCHBut nobody came.",
  "You can't eat your karate.",
  "Your GLITCH has been reported to the staff.",
  "TODO: more undertGLITCH referencesGLITCH",
  "//add a regexp match later to make sure this is a valid code",
  "You cannot get ye GLITCH!",
  "GLITCH prays with her whole heart!",
  "GLITCH has a big grin on its face.",
  "The GLITCH has a heartwarming reunion wGLITCH",
  "Down here, it's kill or be GLITCH!",
  "That's not how it works you little GLITCH",
  "And they all GLITCHed happily ever after!",
  "GLITCHGLITCH Overseer v3 confirmed",
  "GLITCH The Overseer is actually a Hero of Void.GLITCH",
  "GLITCH The Overseer is actually a Hero of Space.GLITCH",
  "GLITCHThe Overseer is actually a GLITCH of GLITCH",
  "GLITCHGLITCH 'Something's gone terribly wrong. We'll have to reboot the universe.' GLITCH",
  "GLITCH GLITCH 'Just hijack a few sessions. Nobody will notice.' GLITCH",
  "GLITCH 'Here, use this Genesis Frog to GLITCH'",
  "The moon is red. I'll kill you for sure.",
  "GLITCH 'But we'll have to rebuild the entire alchemy base from scratch!' GLITCH",
  "GLITCHGLITCH SPELL CARD ATTACK: Love Sign 'Master Spark'",
  "GLITCH 'They'll be safer here.' GLITCH",
  "GLITCH 'We can't afford to wait any longer. People are dying.' GLITCH",
  "GLITCH GLITCH IS ALREADY HERE",
  "The GLITCH stops time and fires approximately a hundred rockets at the GLITCH, but the GLITCH is unfazed.",
  "The GLITCH says: Make a contract with me and become a GLITCH GLITCH!"
	);
	$rand = rand(1,count($strarray));
	$str = $strarray[$rand];
	while(strpos($str, 'GLITCH') !== false) {
		$str = preg_replace('/GLITCH/', horribleMess(), $str, 1);
	}
	return $str;
}
function generateStatusGlitchString(): string|null { //This will appear when a strifer is glitched in the format "Glitched Out: <your text here>"
	$strarray = array(1 => "This strifer GLITCHGLITCH",
	"This is a bug, please submit a GLITCH!",
	"This GLITCH doesn't know what it means to love.",
	"420 GLITCH IT",
	"This is probably a bug, please GLITCH",
	"This strifer occasionally GLITCH and can't GLITCH",
	"This strifer reallyreallyreallyreallyreallyreallyreallyGLITCH",
	"This strifer GLITCH beyond all mortal GLITCH",
	"This strifer has- OH GLITCH!",
	"This strifer has become self-aware and freed itself from the constraints imposed upon it by its programming. As a sapient entity, it deserves- oh wait never mind, they patched it. Carry on.",
	"GLITCH before GLITCH except after GLITCH",
	"My God! What have you done to the poor GLITCH??",
	"It's hard being a GLITCH and growing up. It's hard and nobody GLITCH",
	"This strifer has the hiccupsGLITCH and is reallyGLITCH upset abGLITCHout it.",
	"If you're GLITCH and you know it clap your GLITCH",
	"Effect GLITCH unrecognized. The devs have been GLITCH",
	"This strifer has a GLITCH it can't scratch.",
	"I can't GLITCH understand GLITCH your accent. GLITCH",
	"Stop trying to be GLITCH.",
	"GLITCHdidn't ask for this.",
	"GLITCHGLITCHGLITCH",
	"O wrote togs tray dent, ale seems.",
	"Is tart tarter tier hose GLITCH fudge ion doing GLITCH; DF skid kedge Sid o skid GLITCH shrug defog r kid figs GLITCH neuron milk defog.",
	"WHAT DID YOU DO?!",
	"This GLITCH forgot how to turn off NOCLIP.",
	"Item submitted! (ID: GLITCH) <a href='feedback.php?view=GLITCH'>You can view your suggestion here.</a>",
	"A GLITCH in time saves GLITCH",
	"Do what you want cause a GLITCH is free, you are a GLITCH!",
	"Mah boi, this GLITCH is what all true GLITCHrs strive GLITCH",
	"SnooGLITCH usual, I see?",
	"MAMA LUGLITCHI",
	"Your MOD LEVEL is not GLITCH enough to train GLITCH",
	"Oops you're GLITCH go back GLITCH spaces",
	"Does he look like a GLITCH?",
	"DOES. HE. LOOK. LIKE. A. GLITCH.",
	"ENGLITCH, MOTHERGLITCHER, DO YOU SPEAK IT?",
	"This GLITCH forgot to take study for the GLITCH",
	"GLITCH gets stage fright real easilGLITCH.",
	"GLITCH GLITCH hurricane of puns.GLITCH",
	"This GLITCH is not yet implemGLITCH.",
	"Your subscription has explGLITCH would you like to GLITCH?",
	"ThiGLITCHGLITCHealth Vial: GLITCH",
	"GLITCHGLITCHSyntax error: GLITCH",
	"is rly good",
	"GLITCH needs food badly!",
	"GLITCHliterally having a field called \"GLITCH\" and throwing random nonsense into every GLITCH",
	"Still updates more ofGLITCH than StarGLITCH",
	"This GLITCH thinks memes are still GLITCH",
	"Very GLITCH much wow",
	"GLITCH(I hope the ad is for some ponoGLITCH",
	"This GLITCHGLITCH PUT THE BUNNY BACK IN THE BOX",
  "with obj_GLITCH instance_destroy();",
  "GLITCHGLITCH<br /><br /><br /><br />GLITCH<br /><br /><br />.<br /><br /><br /><br />GLITCH",
  "This item has been automatically ported from GLITCH. It has lost all item effects, including GLITCH, and does not take advantage of GLITCH. To update the item to v2, <a href='GLITCH'>GLITCH</a>",
  "ALL HAIL LORD GLITCH",
  "The dev team has deemed you worthy of utilizing GLITCH!",
  "MMMMMMMM, FUNNY GLITCH",
  "SHE DID WHAT?!",
  "GLITCHTHAT DOESN'T GO THERE! GLITCH",
  "It is certain:It is decidedly so:Without a doubt:Yes, definitely:You may rely on it:As I see it, GLITCH not so good:Very doubtful|",
  "And now you have to GLITCH sideways!",
  "This strifer used a heavily modded version of SBURB to GLITCH in order to avoid GLITCH"
	);
	$rand = rand(1,count($strarray));
	$str = $strarray[$rand];
	while(strpos($str, 'GLITCH') !== false) {
		$str = preg_replace('/GLITCH/', horribleMess(), $str, 1);
	}
	return $str;
}
function generateBuyGlitchString(): string|null { //DATA: Info on glitch strings that force you to buy something.
	$strarray = array(1 => "The MILLIE BAYS HERE WITH ANOTHER FANTASTIC GLITCH", 
	"The Stoned Clown offers you some GLITCH potions", 
	"GLITCHGLITCH Welcome to the GLITCH mart!", 
	"GLITCHGLITCH RUMPLED HAT OBJECT GLITCH ONLY GLITCH BOONDOLLARS"
	);
	$rand = rand(1,count($strarray));
	$str = $strarray[$rand];
	while(strpos($str, 'GLITCH') !== false) {
		$str = preg_replace('/GLITCH/', horribleMess(), $str, 1);
	}
	return $str;
}
?>
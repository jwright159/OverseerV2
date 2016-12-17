<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/global_functions.php");

function renderItem2($irow, $meta = "", $contextid = "", $showart = true, $holo = false) {
	if (!empty($irow)) {
		if (!empty($irow['art'])) {
			$imgstring = '<img src="/images/art/' . $irow['art'] . '" title="Image by ' . $irow['credit'] . '" />';
		}
		if(!isset($imgstring) && !empty($irow['abstratus'])) {
			$abstratus = explode(', ', $irow['abstratus'])[0];
			$imgsrc = "/images/art/default/$abstratus.png";
			if(file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$imgsrc)) {
				$imgstring = '<img src="'.$imgsrc.'" title="No art available" />';
			}
		}
		if(!isset($imgstring) && $irow['wearable'] != "none" && $irow['wearable'] != "") {
			$wearable = trim($irow['wearable']);
			$imgsrc = "/images/art/default/wear_$wearable.png";
			if(file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$imgsrc)) {
				$imgstring = '<img src="'.$imgsrc.'" title="No art available" />';
			}
		}
		if(!isset($imgstring)) {
			$imgstring = '<img src="/images/art/00noart.png" title="No art available" />';
		}

		if ($contextid != "") {
			echo '<span class="item" contextmenu="'.$contextid.'">';
		} else {
			echo '<span class="item">';
		}

		if($showart) echo $imgstring;

		echo '<div class="iteminfo">';
		echo '<div style="font-weight:bold">'.$irow['name'].'</div>';
		echo '<div>Code: <span class="itemcode">'.  $irow['code'] . '</span>' . '</div>';
		if($meta != "") {
			echo '<div>Metadata: <span style="border: 1px solid black; padding-left: 1px; padding-right: 1px;">'.$meta.'</span></div>';
		}
		echo '<div style="font-style:italic">'.$irow['description'].'</div>';

		if(!$holo) {
			if ($irow['abstratus'] != "notaweapon" && $irow['abstratus'] != "") echo '<div>Abstratus: <span style="font-weight:bold">' . $irow['abstratus'] . '</span></div>';
			if ($irow['wearable'] != "none" && $irow['wearable'] != "") echo '<div>Wearable type: <span style="font-weight:bold">' . $irow['wearable'] . '</span></div>';

			if (!empty($irow['power'])) {
				echo '<table class="powertable">';
				echo '<tr><td>power</td><td>' . $irow['power'] . '</td></tr>';
				if (!empty($irow['aggrieve'])) echo '<tr><td>aggrieve</td><td>' . bonusStr($irow['aggrieve']) . '</td></tr>';
				if (!empty($irow['aggress'])) echo '<tr><td>aggress</td><td>' . bonusStr($irow['aggress']) . '</td></tr>';
				if (!empty($irow['assail'])) echo '<tr><td>assail</td><td>' . bonusStr($irow['assail']) . '</td></tr>';
				if (!empty($irow['assault'])) echo '<tr><td>assault</td><td>' . bonusStr($irow['assault']) . '</td></tr>';
				if (!empty($irow['abuse'])) echo '<tr><td>abuse</td><td>' . bonusStr($irow['abuse']) . '</td></tr>';
				if (!empty($irow['accuse'])) echo '<tr><td>accuse</td><td>' . bonusStr($irow['accuse']) . '</td></tr>';
				if (!empty($irow['abjure'])) echo '<tr><td>abjure</td><td>' . bonusStr($irow['abjure']) . '</td></tr>';
				if (!empty($irow['abstain'])) echo '<tr><td>abstain</td><td>' . bonusStr($irow['abstain']) . '</td></tr>';
				echo '</table>';
			}
		}

		if($irow['old'] == 1){
			if ($irow['abstratus'] != "notaweapon" && $irow['abstratus'] != "") 
				/*echo 'This item has been automatically ported from Overseer v1. It has lost all on-hit effects and it does not take advantage of the new grist types. To update the item to v2, <a href="submissions.php">submit a suggestion!</a><br/>';
			else echo 'This item has been automatically ported from Overseer v1. It does not take advantage of the new grist types. To update the item to v2, <a href="submissions.php">submit a suggestion!</a><br/>'*/;
		}

		echo '<div class="copybtn"><button title="Copy Code" class="btn" data-clipboard-text="'. $irow['code'] .'"><img class="clippy" width="15" src="images/ui/clippy.svg" alt="Copy to clipboard"></button></div>';
		echo '</div>';

		echo '</span>';
	} else {
		// empty card
		echo '<span class="item"><img src="/images/art/emptycard.png" /></span>';
	}
}

?>

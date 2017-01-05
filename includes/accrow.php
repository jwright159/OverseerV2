<?php
/*
 * Hydrate the request-global $user variable from the DB.
 * Uses $_SESSION['uid'] to do so. This session variable should be set on login.
 */

use Overseer\Models\CharacterQuery;
use Overseer\Models\UserQuery;

if (!empty($_SESSION['user_id'])) {
	$uid = $_SESSION['user_id'];

	// fetch the user object from the db
	$user = UserQuery::create()->findPK($uid); // findPK = find by primary key = find by id (since User's pk = `id`)
	$_SESSION['username'] = $user->getUsername();

	if (!empty($_SESSION['character_id'])) {
		$cid = $_SESSION['character_id'];

		$character = CharacterQuery::create()->findPK($cid);
		if ($character->getOwner() !== $user) {
			// TODO: replace with a real error flash
			echo "ERROR: You tried to select a character that doesn't belong to you!";

			// wipe the character out of the request and session
			unset($_SESSION['character']);
			unset($character);
		}
	}
}

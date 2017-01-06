<?php
/*
 * Hydrate the request-global $currentUser variable from the DB.
 * Uses $_SESSION['uid'] to do so. This session variable should be set on login.
 */

use Overseer\Models\CharacterQuery;
use Overseer\Models\UserQuery;

if (!empty($_SESSION['userId'])) {
	$uid = $_SESSION['userId'];

	// fetch the user object from the db
	$currentUser = UserQuery::create()->findPK($uid); // findPK = find by primary key = find by id (since User's pk = `id`)
	$_SESSION['username'] = $currentUser->getUsername();

	if (!empty($_SESSION['characterId'])) {
		$cid = $_SESSION['characterId'];

		$currentCharacter = CharacterQuery::create()->findPK($cid);
		if ($currentCharacter->getOwner() !== $currentUser) {
			$flash->error("Your selected character does not belong to you.");

			unset($_SESSION['characterId']);
			unset($currentCharacter);
		}
	}
}

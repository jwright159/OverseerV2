<?php
session_start();
require_once __DIR__.'/../includes/bootstrap.php';

use Overseer\Models\CharacterQuery;

if (isset($_GET['id'])) {
	$character = CharacterQuery::create()->findPK($_GET['id']);
	if (!$character) {
		echo 'No such character.';
	} else {
		if ($character->getOwner() !== $currentUser) {
			$flash->error('That character doesn\'t belong to you!');
		} else {
			$_SESSION['characterId'] = $_GET['id'];
		}
		header('Location: /');
	}
} else {
	// unselect current character
	unset($_SESSION['characterId']);
	header('Location: /');
}

<?php
session_start();
require_once(__DIR__.'/../includes/bootstrap.php');

use Overseer\Models\Character;
use Overseer\Models\SessionQuery;
use Overseer\Models\CharacterQuery;


function validateCharacterName($name) {
	return (bool) preg_match('/^[\p{L}\p{M}\p{Z}]+$/', $name) && (strlen($name) <= 64);
}

function validateChumhandle($handle) {
	return (bool) preg_match('/^[a-zA-Z]+$/', $handle) && (strlen($handle) <= 24);
}

$characterName = $_POST['characterName'];
$species = $_POST['species'];
$chumHandle = $_POST['chumHandle'];
$sessionName = $_POST['sessionName'];
$sessionPass = $_POST['sessionPass'];

if (empty($characterName) || empty($chumHandle) || empty($sessionName) || empty($sessionPass)) {
	$flash->error("Please fill out all fields.");
	redirect_to('/charcreate.php');
} elseif (!validateCharacterName($characterName)) {
	$flash->error("Invalid character name. Character names consist of letters, accents, and spaces.");
	redirect_to('/charcreate.php');
} elseif (!validateChumhandle($chumHandle)) {
	$flash->error("Invalid chumhandle. Chumhandles can only consist of letters.");
	redirect_to('/charcreate.php');
} else {
	$session = SessionQuery::create()->findOneByName($sessionName);
	if ($session) {
		if (password_verify($sessionPass, $session->getPassword())) {
			$sessCharsQuery = CharacterQuery::create()->filterBySession($session);
			if (!$sessCharsQuery->findOneByName($characterName)) { // no character exists in this session with the same name
				if ($sessCharsQuery->findOneByChumhandle($chumHandle)) { // is chumhandle taken?
					$flash->error("Chumhandle taken in this session!");
					redirect_to('/charcreate.php');
				} else {
					// finally create the character
					$character = new Character();
					$character->fromArray(["Name" => $characterName, "Chumhandle" => $chumHandle, "Species" => $species]);
					// attach it to the current user and the session the user provided
					$character->setOwner($currentUser);
					$character->setSession($session);
					// save it
					$character->save();

					// WE'RE DONE HERE BOIS
					$flash->success("Character creation succeeded!");
					redirect_to('/');
				}
			} else {
				$flash->error("Character name taken in this session!");
				redirect_to('/charcreate.php');
			}
		} else {
			$flash->error("Incorrect session password!");
			redirect_to('/charcreate.php');
		}
	} else {
		$flash->error("A session doesn't exist with that name.");
		redirect_to('/charcreate.php');
	}
}

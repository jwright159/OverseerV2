<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php'; // Run Composer's autoloader.
require_once $_SERVER['DOCUMENT_ROOT'].'/build/orm/conf/config.php';

use Overseer\Models\User;
use Overseer\Models\UserQuery;

/*
$user = new User();
$user->setUsername("john.egbert");
$user->setPassword("foof");
$user->save();
*/

$users = UserQuery::create()->find();
foreach ($users as $user) {
	echo $user;
}

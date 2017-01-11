<?php
/* This file should be required by *any* code needing access to the database,
 * global functions, or the account and character rows/ID session variables.
 */

// Save the time we started loading so we can compute how long it took
$renderStartTime = microtime(true);

session_start(); // Begin the PHP session

// Connect to the database. Loads a global mysqli object, $connection.
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database.php');

// General functions required by Overseer code
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/global_functions.php');

// Load global account and character state into $accountRow and $characterRow,
// as well as populating all IDs in $_SESSION.
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/accrow.php');

// Make sure all errors are reported by PHP
error_reporting(E_ALL);

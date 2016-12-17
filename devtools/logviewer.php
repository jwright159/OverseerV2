<?php
//REPLACE $logpaths for whatever your server uses
require_once($_SERVER['DOCUMENT_ROOT'] . "/header.php");
$pagetitle = "Log Viewer";
$headericon = "/images/header/spirograph.png";
$phplogpath = 'C:/wamp/logs/php_error.log';
$apachelogpath = 'C:/wamp/logs/apache_error.log';
$accesslogpath = 'C:/wamp/logs/access.log';

	function tailCustom($filepath, $lines = 1, $adaptive = true) {

		// Open file
		$f = @fopen($filepath, "rb");
		if ($f === false) return false;

		// Sets buffer size
		if (!$adaptive) $buffer = 4096;
		else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

		// Jump to last character
		fseek($f, -1, SEEK_END);

		// Read it and adjust line number if necessary
		// (Otherwise the result would be wrong if file doesn't end with a blank line)
		if (fread($f, 1) != "\n") $lines -= 1;

		// Start reading
		$output = '';
		$chunk = '';

		// While we would like more
		while (ftell($f) > 0 && $lines >= 0) {

			// Figure out how far back we should jump
			$seek = min(ftell($f), $buffer);

			// Do the jump (backwards, relative to where we are)
			fseek($f, -$seek, SEEK_CUR);

			// Read a chunk and prepend it to our output
			$output = ($chunk = fread($f, $seek)) . $output;

			// Jump back to where we started reading
			fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

			// Decrease our line counter
			$lines -= substr_count($chunk, "\n");

		}

		// While we have too many lines
		// (Because of buffer size we might have read too many)
		while ($lines++ < 0) {

			// Find first newline and remove all text before that
			$output = substr($output, strpos($output, "\n") + 1);

		}

		// Close file and return
		fclose($f);
		return trim($output);

	}
if ($accrow['modlevel'] < 10) {
  echo "You don't have sufficient permissions to view the log viewer!";
 } else {
 	if(isset($_GET['log'])){
		if($_GET['log']=='php'){
			echo "<br>Select Log<br>";
			echo '<form name="log" action="logviewer.php" method="GET">
				<input type="radio" name="log" value="php" checked> PHP error log<br>
				<input type="radio" name="log" value="apache"> Apache error log<br>
				<input type="radio" name="log" value="access"> Access log<br>
				<input type="submit" value="Retrieve log"></form><br>';		
			echo "<pre>";
			echo(tailCustom($phplogpath, 1000));
			echo "</pre>";
		}
		elseif($_GET['log']=='apache'){
			echo "<br>Select Log<br>";
			echo '<form name="log" action="logviewer.php" method="GET">
				<input type="radio" name="log" value="php" checked> PHP error log<br>
				<input type="radio" name="log" value="apache"> Apache error log<br>
				<input type="radio" name="log" value="access"> Access log<br>
				<input type="submit" value="Retrieve log"></form><br>';
			echo "<pre>";
			echo(tailCustom($apachelogpath, 1000));
			echo "</pre>";
		}
		elseif($_GET['log']=='access'){
			echo "<br>Select Log<br>";
			echo '<form name="log" action="logviewer.php" method="GET">
				<input type="radio" name="log" value="php" checked> PHP error log<br>
				<input type="radio" name="log" value="apache"> Apache error log<br>
				<input type="radio" name="log" value="access"> Access log<br>
				<input type="submit" value="Retrieve log"></form><br>';		
			echo "<pre>";
			echo(tailCustom($accesslogpath, 1000));
			echo "</pre>";
		}
	}

	echo "<br>Select Log<br>";
	echo '<form name="log" action="logviewer.php" method="GET">
		<input type="radio" name="log" value="php" checked> PHP error log<br>
		<input type="radio" name="log" value="apache"> Apache error log<br>
		<input type="radio" name="log" value="access"> Access log<br>
		<input type="submit" value="Retrieve log"></form><br>';
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/footer.php");
?>

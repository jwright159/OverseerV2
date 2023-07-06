<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/header.php";

if ($accrow['modlevel'] < 99) {
	echo "denied.<br />";
} else {
	if (!empty($_POST['query'])) {
		$query = $_POST['query'];
		if ($_POST['teststring'] == "test\\'s") { //auto-escape detected, fix before executing query
		$query = str_replace("\\'", "'", $query);
		$query = str_replace("\\\"", "\"", $query);
		$query = str_replace("\\\\", "\\", $query);
		}
		echo $query . "<br />";
		if (strpos($_POST['query'], "SELECT") !== false) {
		  $result = mysqli_query($connection, $query);
		  if (!$result) echo "We got an error... " . mysqli_error($connection);
			else {
			  echo strval(mysqli_num_rows($connection, $result)) . " row(s) returned.<br />";
				if (!empty($_POST['tabler'])) {
					while ($row = mysqli_fetch_array($result)) {
						print_r($row);
						echo "<br />";
					}
				} else {
  			$fields = 0;
  			echo '<font size="1"><table border="1" bordercolor="#CCCCCC" style="background-color:#EEEEEE" width="100%" cellpadding="1" cellspacing="1"><tr>';
  			while ($field = mysqli_fetch_field($result)) {
  				$name = $field->name;
  				switch ($name) {
  					case 'consumable':
  						$name = "cnsm";
  						break;
  					case 'catalogue':
  						$name = "base";
  						break;
  					case 'lootonly':
  						$name = "loot";
  						break;
  					case 'refrance':
  						$name = "ref";
  						break;
  					case 'aggrieve':
  						$name = "AGRV";
  						break;
  					case 'aggress':
  						$name = "AGRS";
  						break;
  					case 'assail':
  						$name = "ASSL";
  						break;
  					case 'assault':
  						$name = "ASLT";
  						break;
  					case 'abuse':
  						$name = "ABUS";
  						break;
  					case 'accuse':
  						$name = "ACCS";
  						break;
  					case 'abjure':
  						$name = "ABJR";
  						break;
  					case 'abstain':
  						$name = "ABST";
  						break;
  				}
  				echo "<td>$name</td>";
  				$fields++;
  			}
  			$totalfields = $fields;
  			echo "</tr>";
  			while ($row = mysqli_fetch_array($result)) {
  				echo "<tr>";
  				$fields = 0;
  				while ($fields < $totalfields) {
  					$name = $row[$fields];
  					echo "<td>$name</td>";
  					$fields++;
  				}
  				echo "</tr>";
  			}
  		echo '</table></font></br>';
				}
			}
		} elseif (strpos($_POST['query'], "INSERT") !== false) {
		  $result = mysqli_query($connection, $query);
			if (!$result) echo "We got an error... " . mysqli_error($connection);
			else {
				echo strval(mysqli_affected_rows($connection)) . " row(s) inserted.<br />";
				logDebugMessage($username . " - executed query: $query");
			}
		} elseif (strpos($_POST['query'], "UPDATE") !== false) {
		  $result = mysqli_query($connection, $query);
			if (!$result) echo "We got an error... " . mysqli_error($connection);
			else {
				echo strval(mysqli_affected_rows($connection)) . " row(s) affected.<br />";
				logDebugMessage($username . " - executed query: $query");
			}
		} else echo "I don't think it's safe to do that kind of thing from here.<br />";
		echo "<br />";
	}
	
	echo "New from Blahsadfeguie Inc., it's I Can't Believe it's Not PHPmyadmin! 99% of squirrels can't tell the difference!<br /><br />";
	echo '<form action="blahsquirrel.php" method="post" id="blahsql">Query to execute:<br /><textarea name="query" rows="6" cols="40" form="blahsql"></textarea><br />';
	echo '<input type="checkbox" name="tabler" value="tabler" />Display results in text format instead of a table<br /><input type="hidden" name="teststring" value="test\'s" />';
	echo '<input type="submit" value="Execute it!" /></form><br />';
	echo "Current tables:  ";

	$tablenames = mysqli_query($connection, "SELECT `TABLE_NAME` FROM information_schema.tables WHERE `TABLE_SCHEMA` = 'OverseerDev';");
	while ($tablename = mysqli_fetch_array($tablenames)) 
	  {
	    echo $tablename['TABLE_NAME'] . ", ";
	  }
	echo "<br />";
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/footer.php";
?>

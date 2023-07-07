<?php
$pagetitle = "Chain Viewer";
$headericon = "/images/header/spirograph.png";
require_once "header.php";
require_once "includes/global_functions.php";
?>

<form action="chainviewer.php" method="get">
Session to retrieve info about: <input id="session" name="session" type="text" /><input type="submit" value="Examine it!" /> </form></br>

<?php
if(!empty($_GET['session']))
{
	$sessionesc = str_replace("'", "''", $_GET['session']);
	$ownsession = mysqli_query($connection, "SELECT * FROM Sessions WHERE `name` ='" . mysqli_real_escape_string($connection, $sessionesc) . "';");
	$sessiont = mysqli_fetch_array($ownsession);
}
else
{
	$ownsession = mysqli_query($connection, "SELECT * FROM Sessions WHERE ID = $charrow[session];");
	$sessiont = mysqli_fetch_array($ownsession);
}

if(!$sessiont)
{
	echo 'Session not found';
}
else
{
	echo "Showing chains in session " . $sessiont['name'] . ":</br>";

	$characterids = array_filter(explode("|", $sessiont['members']), function($item) { return (bool)$item; });

	while (!empty($characterids))
	{
		$original = array_values($characterids)[0];
		
		// Start by navigating to the head of the chain (the most client-ways) until there is no client or until we reach the original char
		$head = $original;
		while (($client = getChar($head)['client']) && $client != $original)
		{
			$head = $client;
		}
		$original = $head;
		
		// Now that we have the head, go server-wise and add members to the chain
		$chain = [$head];
		while (($server = getChar($head)['server']) && $server != $original)
		{
			$head = $server;
			$chain[] = $head;
		}
		
		$characterids = array_diff($characterids, $chain);
		
		$chainStr = urlencode(serialize($chain));
		echo "<img src='/sessiongraph.php?chain=$chainStr'/>";
	}
}

require_once "footer.php";

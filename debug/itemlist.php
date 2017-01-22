<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/item.php');
?>
<div class="container">
	<h1>Difarem's Debug Item List™</h1>
	
<?php
	if (isset($_POST['name'])) {
		// add an item to the database
	}
?>
	
	<div class="container">
		<ul>
			<?php
			$result = mysqli_query($connection, "SELECT * FROM `Items`;");
			while($row = mysqli_fetch_array($result)) {
				$item = Item::from_row($row);
				echo '<li>'.htmlspecialchars($item->name);
				if (!is_null($item->code)) {
					echo ' ['.$item->code->to_string().']';
				}
				echo '<ul><li>'.htmlspecialchars($item->description);
				echo '</li></ul></li>';
			}
			?>
		</ul>
	</div>
</div>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');
?>

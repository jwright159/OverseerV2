<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/item.php');
?>
<div class="container">
	<h1>Difarem's Debug Item List™</h1>
	
<?php
	if (isset($_POST['name'])) {
		// add an item to the database
		// TODO: DOESN'T CHECK FOR COLLISIONS FOR NOW
		$item = new Item;
		if (empty($_POST['name'])) {
			echo 'No name provided.';
			goto invalid_form;
		}
		$item->name = $_POST['name'];
		// TODO: code validation?
		if (!empty($_POST['code'])) {
			$item->code = Code::from_string($_POST['code']);
		}
		if (empty($_POST['description'])) {
			echo 'No description provided.';
			goto invalid_form;
		}
		$item->description = $_POST['description'];
		$item->insert($connection);
		
		invalid_form:; // i'm so sorry
	}
?>
	
	<form action="" method="POST">
		<h2>Add item</h2>
		<div class="form-group">
			<label for="name">Name:</label>
			<input type="text" class="form-control" id="name" name="name">
		</div>
		<div class="form-group">
			<label for="code">Code (if any):</label>
			<input type="text" class="form-control" id="code" name="code">
		</div>
		<div class="form-group">
			<label for="description">Description:</label>
			<textarea class="form-control" rows="5" id="description" name="description"></textarea>
		</div>
		<button type="submit" class="btn btn-default">Add</button>
	</form>
	
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

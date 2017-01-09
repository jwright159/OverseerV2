<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');

//Title
?>
<div class="container">
	<h1>Create a Character!</h1>
	<form role="form" class="form-horizontal" id="SessionForm" action="forms/charcreate.php" method="post">
		<div class="form-group">
			<label class="control-label col-sm-2" for="species">Species</label>
				<label class="radio-inline"><input type="radio" checked="checked" name="species" value="0">Human</label>
				<label class="radio-inline"><input type="radio" name="species" value="1">Troll</label>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2" for="characterName">Character Name: </label>
			<div class="col-sm-3">
				<input id="characterName" name="characterName" class="form-control" type="text" />
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2" for="chumHandle">Trollian/Pesterchum Handle: </label>
			<div class="col-sm-3">
				<input id="chumHandle" name="chumHandle" class="form-control" type="text" /><br>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2" for="sessionName">Session Name: </label>
			<div class="col-sm-3">
				<input id="sessionName" name="sessionName" class="form-control" type="text" />
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2" for="sessionPass">Session Password: </label>
			<div class="col-sm-3">
				<input id="sessionPass" name="sessionPass" class="form-control" maxlength="50" type="password" />
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-2 col-sm-10">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" form="SessionForm">Next</button>
			</div><br>
		</div>

<?php



require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');

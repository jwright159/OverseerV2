<?php
$pageTitle = 'Login';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<div class="container">
	<p>Login</p>
	<form role="form" class="form-horizontal" id="LoginForm" action="forms/dologin.php" method="post">
		<div class="form-group">
			<label class="control-label col-sm-2" for="username">Username: </label>
			<div class="col-sm-3">
				<input id="username" name="username" class="form-control" type="text" />
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2" for="password">Password: </label>
			<div class="col-sm-3">
				<input id="password" name="password" class="form-control" type="password" />
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-2 col-sm-10">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" form="LoginForm">Submit</button>
			</div>
		</div>
	</form>
</div> 

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');
?>

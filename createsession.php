<?php
$pageTitle = 'Create a session';
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
?>

<script type="text/javascript">
	$(document).ready(function() {
		$('#SessionForm').submit(function(event) { // catch the form's submit event
			event.preventDefault();
			$.ajax('forms/addsession.php',{ // Do it
				data: $(this).serialize(), // Gets form Data
				type: 'POST', // GET or POST
				url: $(this).attr('forms/addsession.php'), //Calls the file
				success: function(response) {// on success..
					console.log(response.substr(0,10));
					$('#DisplayDiv').html(response); // update the DIV
				}
			});
			return false; // cancel original event to prevent form submitting
		});
	});
</script>
<div class="container">
	<p>Create a session!</p>
	<form role="form" class="form-horizontal" id="SessionForm" action="forms/addsession.php" method="post">
		<div class="form-group">
			<label class="control-label col-sm-2" for="sessionName">Session Name: </label>
			<div class="col-sm-3">
				<input id="sessionName" name="sessionName" class="form-control" type="text" />
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2" for="sessionPassword">Session Password: </label>
			<div class="col-sm-3">
				<input id="sessionPassword" name="sessionPassword" class="form-control" type="password" />
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-sm-2" for="confPass">Confirm Password: </label>
			<div class="col-sm-3">
				<input id="confPass" name="confPass" class="form-control" maxlength="50" type="password" />
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-2 col-sm-10">
				<button name="submit" type="submit" class="btn btn-primary" id="submit" form="SessionForm">Submit</button>
			</div><br>
		</div>
		<div id="DisplayDiv"></div>
	</form>
</div>

<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');
?>

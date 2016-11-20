<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
 // Includes the header ?>
 <script type="text/javascript">
  $(document).ready(function() {
    $('#RegisterForm').submit(function(event) { // catch the form's submit event
	  event.preventDefault();
    $.ajax('addaccount.php',{ // Do it
        data: $(this).serialize(), // Gets form Data
        type: 'POST', // GET or POST
        url: $(this).attr('addaccount.php'), //Calls the file
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

<p>Register an account!</p>
<form role="form" class="form-horizontal" id="RegisterForm" action="addaccount.php" method="post">
	<div class="form-group">
		<label class="control-label col-sm-2" for="email">E-mail address: </label>
		<div class="col-sm-3">
			<input id="email" name="email" class="form-control" type="text" />
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-sm-2" for="emailconf">E-mail (again!): </label>
		<div class="col-sm-3">
			<input id="emailconf" name="emailconf" class="form-control" type="text" />
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-sm-2" for="username">Username: </label>
		<div class="col-sm-3">
			<input id="username" name="username" class="form-control" maxlength="50" type="text" />
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-sm-2" for="password">Password: </label>
		<div class="col-sm-3">
			<input id="password" name="password" class="form-control" type="password" />
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-sm-2" for="confirmpw">Password (again!): </label>
		<div class="col-sm-3">
			<input id="confirmpw" name="confirmpw" class="form-control" type="password" />
		</div>
	</div>
	<div class="checkbox">
 		<label><input id="tos" type="checkbox" value="">I agree to the <a href="rules.php">Terms and Conditions</a> and am happy with the <a href="privacy.php">Privacy Policy.<a></label>
	</div>
<div class="form-group">
      <div class="col-sm-offset-2 col-sm-10">
	    <button name="submit" type="submit" class="btn btn-primary" id="submit" form="RegisterForm">Submit</button>
	  </div><br>

</div>
<div id="DisplayDiv"></div>
<br /><br /><div class="alert alert-warning" role="alert">Be advised: You'll be asked to confirm your email address, and it'll be needed if you ever need to reset your password. </div><br>
<br>


<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');
?>

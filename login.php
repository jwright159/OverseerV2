<?php
$pageTitle = 'Login';
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
    // Includes the header ?>
<script type="text/javascript">
    $(document).ready(function() {
        $('#LoginForm').submit(function(event) { // catch the form's submit event
            event.preventDefault();
            $.ajax('forms/dologin.php',{ // Do it
                data: $(this).serialize(), // Gets form Data
                type: 'POST', // GET or POST
                url: $(this).attr('forms/dologin.php'), //Calls the file
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
    <p>Login</p>
    <form role="form" class="form-horizontal" id="LoginForm" action="dolgin.php" method="post">
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
            </div><br>
        </div>
        <div id="DisplayDiv"></div>
        <br><br>
        <br>
        <br>
    </form>
</div> 

    <?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');
?>

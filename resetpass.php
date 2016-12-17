<?php

//require_once("header.php");
require_once("includes/global_functions.php");
require_once("inc/database.php");

if (!isset($_POST['email']) && !isset($_POST['resetkey']) && !isset($_POST['password'])) {
    echo 'Enter your email address to reset your password.<br><br>'; ?>
    <form id='email' action='resetpass.php' method='post'>
        <input type="text" id="email" name="email" placeholder="Email Address">
        <input type="submit">
    </form>
<?php    
} elseif (!empty($_POST['email'])) {
    $email = mysqli_escape_string($connection, $_POST['email']);
    $resetKey = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 32); // Generates a key 32 characters long
    mysqli_query($connection, "UPDATE `Users` SET `password_recovery` = '$resetKey' WHERE `email` = '$email';");
    $to      = $email;
    $subject = 'Overseer 2 Password Reset';
    $message = 'Hello, you requested a password reset on Overseer 2. Your key is '.$resetKey.' which should be entered on the page you were shown.';
    $headers = 'From: no-reply@overseer2.com' . "\r\n" .
    'Reply-To: no-reply@overseer2.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
    echo "A key has been emailed to you. Please enter it here:<br>
    <form id='resetpass' action='resetpass.php' method='post'>
        <input type='text' id='resetkey' name='resetkey' autofocus>
        <input type='submit'>
    </form>";
    
} elseif (isset($_POST['resetkey']) && !isset($_POST['password'])) {
    $resetkey = mysqli_escape_string($_POST['resetkey']);
    $resetQuery = mysqli_query($connection, "SELECT * FROM `Users` WHERE `password_recovery` = '$resetkey' LIMIT 1;");
    $resetRow = mysqli_fetch_array($resetQuery);
    if ($resetkey == $resetRow['password_recovery']) {
        ?>
        Please enter your new password.
        <form id='newpass' action='resetpass.php' method='post'>
            <input type='password' id='password' name='password' placeholder='Password' autofocus>
            <input type='password' id='confirmpass' name='confirmpass' placeholder='Confirm Password'>
            <input type='text' id='resetkey' name='resetkey' placeholder='Reset Key Again'>
            <input type='submit'>
        </form>
        <?php
    } else {
        echo 'Didn\'t match! Please go back and enter it again.';
    }
} elseif (isset($_POST['password'])) {
    $password = mysqli_escape_string($connection, $_POST['password']);
    $confirmpass = mysqli_escape_string($connection, $_POST['confirmpass']);
    if ($password == $confirmpass && !empty($_POST['resetkey']) && $_POST['resetkey']!=NULL) {
        $enterpass = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($connection, "UPDATE `Users` SET `password` = '$enterpass' WHERE `password_recovery` = '". mysqli_real_escape_string($connection, $_POST['resetkey']) ."' LIMIT 1;");
        echo 'Password updated!';
    } else {
        echo 'Passwords didn\'t match!';
    }
} else {
    echo 'Email was empty, or something went wrong.';
}



//require_once("footer.php");

?>
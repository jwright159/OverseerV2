<?php

$to      = 'thethellere@gmail.com'; // Send email to our user
$subject = 'Mail test'; // Give the email a subject 
$message = 'This is a testmail to make sure it works
 
'; // Our message above including the link
                     
$headers = 'From:noreply@overseer2.com' . "\r\n"; // Set from headers
mail($to, $subject, $message, $headers); // Send our email
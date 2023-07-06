<?php
require_once "devconnect.php";
mysqli_query($devConnect,"UPDATE Users SET modlevel = 99 WHERE username = 'Blahnotdumb' LIMIT 1;");
echo "you have blah mod level now have a nice day";
?>
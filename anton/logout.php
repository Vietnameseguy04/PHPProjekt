<!-- Anton Nguyen -->
<!-- Ends the current session and redirects the user to the login page.-->

<?php

session_start();
session_destroy();
header("Location: ../niklas/index.php");
exit;

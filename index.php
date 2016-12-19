<?php
/* Redirect browser */
$url = 'http://' . $_SERVER['HTTP_HOST'];            // Get the server
$url .= rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); // Get the current directory
$url .= '/usage-report.php';            // <-- Your relative path
header('Location: ' . $url, true, 302);              // Use either 301 or 302

/* Make sure that code below does not get executed when we redirect. */
die();
?>

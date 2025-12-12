<?php
echo "Server Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current Script: " . $_SERVER['PHP_SELF'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Base URL should be: " . dirname($_SERVER['PHP_SELF']) . "<br>";
?>
<?php
$connect = mysqli_connect('localhost', 'root', '');
mysqli_select_db( $connect,'test');

session_start();

// Register session variables (deprecated)
// session_register('type');
// session_register('user_id');
$_SESSION['type']="master";
$_SESSION['user_id']="1";
$_SESSION['user_name']="john_smith@gmail.com";
$_SESSION['password']="password";


?>

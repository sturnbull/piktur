<?php
$passwotd_hash = hash( 'sha512', $password );
$sql = "INSERT INTO `piktur`.`users` (`user_id`, `first_name`, `last_name`, `email_address`, `password_hash`, `admin_flag`, `account_status`) VALUES (NULL, '$first_name', '$last_name', '$email_address', '$password_hash', b'$admin_flag', b'$account_status');";

echo $sql;
?>

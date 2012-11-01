<?php
require_once 'global.inc';

# Destroy session for authenticated user
session_destroy();

# Redirect to home page
header ( 'Location: http://'.$_SERVER['SERVER_NAME'] );
?>

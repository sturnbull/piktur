<?php
require_once 'global.inc';

# Destroy session for authenticated user
session_destroy();

# Invalidate token cookie
setcookie ( 'token', md5( $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . session_id() ), time()-60 );

# Redirect to home page
header ( 'Location: http://'.$_SERVER['SERVER_NAME'] );
?>

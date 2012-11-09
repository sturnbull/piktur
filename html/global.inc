<?php

# Defined constants
defined( 'DB_NAME' ) or define( 'DB_NAME', 'piktur' );
defined( 'DB_HOST' ) or define( 'DB_HOST', 'piktur2.poly.edu' );
defined( 'DB_PORT' ) or define( 'DB_PORT', '3306' );
defined( 'DB_USER' ) or define( 'DB_USER', 'ssluser' );
defined( 'DB_PASS' ) or define( 'DB_PASS', 'piktur100' );
defined( 'DEBUG' ) or define( 'DEBUG', FALSE );

$protocol = $_SERVER['HTTPS'] == '' ? 'http://' : 'https://';

# Database connectivity over SSL
$db = mysqli_init(); 
if ( !$db->ssl_set( '/etc/pki/private/piktur.poly.edu.key', '/etc/pki/public/piktur.poly.edu.pem', '/etc/pki/cacerts/ca-cert.pem', NULL, NULL ) ) {
    die( 'Unable to enable SSL (' . mysqli_connect_errno() . ') ' . mysqli_connect_error() );
}
if ( !$db->real_connect( DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT ) ) {
    die( 'Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error() );
}

# Ensure we're trcking sessions for each connection
session_start();

# Check to see if $_SESSION['timeout'] is set
if ( isset( $_SESSION['timeout'] ) ) {
    # Calculate the session's time-to-live and kill if needed
    $sessionTTL = time() - $_SESSION['timeout'];
    if ( $sessionTTL > 600 ) {
        session_destroy();
        header( 'Location: '.$protocol.$_SERVER['SERVER_NAME'].'/signout.php' );
    }
}

# Check session toke
if ( isset( $_SESSION['authenticated'] ) and isset( $_COOKIE['token'] ) ) {
    if ( $_COOKIE['token'] != md5( $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR'] . session_id() ) ) {
        header( 'Location: '.$protocol.$_SERVER['SERVER_NAME'].'/signout.php' );
    }
}
?>

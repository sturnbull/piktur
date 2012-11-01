<?php

# Defined constants
defined( 'DB_NAME' ) or define( 'DB_NAME', 'piktur' );
defined( 'DB_HOST' ) or define( 'DB_HOST', 'piktur2.poly.edu' );
defined( 'DB_PORT' ) or define( 'DB_PORT', '3306' );
defined( 'DB_USER' ) or define( 'DB_USER', 'root' );
defined( 'DB_PASS' ) or define( 'DB_PASS', 'piktur100' );
defined( 'DEBUG' ) or define( 'DEBUG', 'false' );

# Configure database connection
$db = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT );
if ( $db->connect_errno ) {
    die( 'Failed to connect to database: (' . $db->connect_errno . ') ' . $db->connect_error );
}



# Ensure we're trcking sessions for each connection
session_start();

# Check to see if $_SESSION['timeout'] is set
if ( isset( $_SESSION['timeout'] ) ) {
    # Calculate the session's time-to-live and kill if needed
    $sessionTTL = time() - $_SESSION['timeout'];
    if ( $sessionTTL > $inactive ) {
        session_destroy();
        header( 'Location: http://' . $_SERVER['SERVER_NAME'] . '/signout.php' );
    }
}


/*
# TEST CODE FOR SSL SUPPORT  *** NOT WORKING ***
$db = mysqli_init(); 
if ( !$db->ssl_set( '/etc/pki/private/piktur.poly.edu.key', '/etc/pki/public/piktur.poly.edu.pem', '/etc/pki/cacerts/ca.pem', NULL, NULL ) ) {
    die( 'Unable to enable SSL (' . mysqli_connect_errno() . ') ' . mysqli_connect_error() );
}
if ( !$db->real_connect( $database_host, $database_user, $database_password, $database_name ) ) {
    die( 'Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error() );
}
*/
?>

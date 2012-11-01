<?php
require_once 'global.inc';

# Validate user input from form post
$name = filter_input( INPUT_POST, 'username', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_]{1,64}$/" ) ) );
$password = filter_input( INPUT_POST, 'password', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/" ) ) );
if ( $password ) $password_hash =  hash( 'sha512', $password );
$failure = filter_input( INPUT_GET, 'failure', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^true$/" ) ) );


if ( DEBUG ) {
    echo "USERNAME: '$name'<br>";
    if ( isset( $password_hash ) ) { echo "PASSHASH: '$password_hash'<br>"; }
}

# Only insert user if valid variabls exist
if ( isset( $name ) and isset( $password_hash ) ) {
    # Prepare the MySQL select statement on the server
    if ( !( $stmt = $db->prepare( "SELECT `user_id`, `name`, `email_address`, `admin_flag` FROM `piktur`.`users` WHERE `account_status` = b'1' AND `name` = ? AND `password_hash` = ? LIMIT 1" ) ) ) {
        
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    }
    else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'ss', $name, $password_hash ) ) {
            die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        }
        else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
                die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            }
            else {
                # Bind results
                $stmt->bind_result( $user_id, $name, $email_address, $admin_flag );

                # Fetch the value
                $stmt->fetch();

                # Cleanup statement
                $stmt->close();
            }
        }
    }

    # Review results
    if ( DEBUG ) {
        echo "USER_ID: $user_id<br>";
        echo "NAME: $name<br>";
        echo "EMAIL: $email_address<br>";
        echo "ADMIN: $admin_flag<br>";
    }

    # If we have a valid user, create session
    if ( isset( $user_id ) and isset ( $name )  and isset( $email_address ) and isset( $admin_flag ) ) {
        # Now that we're authenticated regenerate session
        session_regenerate_id();

        # Set time-out period (in seconds)
        $inactive = 600;
 
        $_SESSION['timeout'] = time();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['name'] = $name;
        $_SESSION['email_address'] = $email_address;
        $_SESSION['admin_flag'] = $admin_flag;
        $_SESSION['authenticated'] = 'true';

        # Redirect user to albumview
        header ( 'Location: http://'.$_SERVER['SERVER_NAME'].'/albumview.php' );
    }
    else {
        header ( 'Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?failure=true' );
    }
}

require 'header.php';
?>
<?php if ( $failure ) { ?>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td class="notice">Invalid username or password!</td>
        </tr>
      </tbody>
    </table>
<?php } ?>
    <form id="signin_form" name="signin_form" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
      <table border="0" cellpadding="2" cellspacing="2" width="100%">
        <tr>
          <td colspan="1" rowspan="1" align="center" height="200" valign="top">
            <table border="0" cellpadding="2" cellspacing="4" width="100%">
              <tr>
                <td class="formlabel">Username:</td>
                <td class="forminput">
                  <input size="18" name="username" id="username" type="text"<?php if ( $name ) echo " value=\"$name\""; ?>>
                </td>
              </tr>
              <tr>
                <td class="formlabel">Password:</td>
                <td class="forminput">
                  <input size="18" name="password" id="password" type="password"<?php if ( $password ) echo " value=\"$password\""; ?>>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td align="center" valign="middle" colspan="1">
            <input type="image" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/signinbutton.png" height="45" width="125" border="0" alt="Signin Button">
          </td>
        </tr>
      </table>
    </form>
<?php require 'footer.php'; ?>
    <script type="text/javascript" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/js/livevalidation_signin.js"></script>
  </body>
</html>

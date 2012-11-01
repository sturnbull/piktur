<?php
require_once 'global.inc';

# Validate user input from form post
$name = filter_input( INPUT_POST, 'username', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_]{1,64}$/" ) ) );
$email_address = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );
$password = filter_input( INPUT_POST, 'password', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/" ) ) );
if ( $password ) $password_hash =  hash( 'sha512', $password );
$confirm = filter_input( INPUT_GET, 'confirm', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^true$/" ) ) );


if ( DEBUG ) {
    echo "USERNAME: '$name'<br>";
    echo "EMAIL ADDRESS: '$email_address'<br>";
    echo "PASSWORD: '$password'<br>";
    if ( $password ) echo "PASSWORD HASH: '$password_hash'<br>";
}

# Only insert user if valid variabls exist
if ( $name and $email_address and $password ) {
    # Prepare the MySQL insert statement on the server
    if ( !( $stmt = $db->prepare( 'INSERT INTO `piktur`.`users` ( `name`, `email_address`, `password_hash` ) VALUES ( ?, ?, ? )' ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    }
    else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'sss', $name, $email_address, $password_hash ) ) {
            die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        }
        else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
                die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            }

            # Cleanup statement
            $stmt->close();
        }
    }

    # Send email to user to activate account
    $subject = 'PIKTUR account confirmation';
    $message = '
<html>
<head>
  <title>PIKTUR account confirmation</title>
  <link rel="stylesheet" type="text/css" href="http://piktur.poly.edu/css/piktur.css" />
</head>
<body>
  <table>
    <tr>
      <td class="logo"><img src="http://piktur.poly.edu/img/piktur.png" alt="PIKTUR Logo"></td>
    </tr>
    <tr>
      <td class="logo">Your email address was recently used to register at the PIKTUR website. If you did not create an account, please disregard this message. If you wish to activate the account, click on the following link: <a href="http://piktur.poly.edu/activate_account.php?email='.$email_address.'&key='.$password_hash.'">Activate Account</a></td>
    </tr>
  </table>
</body>
</html>
';

    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: webmaster@piktur.poly.edu' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    if ( mail( "$name<$email_address>", $subject, $message, $headers ) ) {
        header ( 'Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?confirm=true' ); 
    }
}



require 'header.php';
?>
<?php if ( $confirm ) { ?>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td class="notice">Thank you for registering! An email has been sent with a link to activate your account.</td>
        </tr>
      </tbody>
    </table>
<?php } else { ?>
    <form id="add_user_form" name="add_user_form" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
      <table border="0" cellpadding="2" cellspacing="2" width="100%">
        <tbody>
          <tr>
            <td colspan="1" rowspan="1" align="center" height="200" valign="top">
              <table border="0" cellpadding="2" cellspacing="4" width="100%">
                <tbody>
                  <tr>
                    <td class="formlabel">Username:</td>
                    <td class="forminput">
                      <input size="18" name="username" id="username" type="text"<?php if ( $name ) echo " value=\"$name\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel">Email:</td>
                    <td class="forminput">
                      <input size="18" name="email" id="email" type="text"<?php if ( $email_address ) echo " value=\"$email_address\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel">Confirm Email:</td>
                    <td class="forminput">
                      <input size="18" name="email2" id="email2" type="text"<?php if ( $email_address ) echo " value=\"$email_address\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel">Password:</td>
                    <td class="forminput">
                      <input size="18" name="password" id="password" type="password"<?php if ( $password ) echo " value=\"$password\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel">Confirm Password:</td>
                    <td class="forminput">
                      <input size="18" name="password2" id="password2" type="password"<?php if ( $password ) echo " value=\"$password\""; ?>>
                    </td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" valign="middle" colspan="1">
              <input type="image" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/signupbutton.png" height="45" width="125" border="0" alt="Signup Button">
            </td>
          </tr>
        </tbody>
      </table>
    </form>
<?php } ?>
<?php require 'footer.php'; ?>
    <script type="text/javascript" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/js/livevalidation_signup.js"></script>
  </body>
</html>

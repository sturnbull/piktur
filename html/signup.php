<?php
require_once 'global.inc';
include_once $_SERVER['DOCUMENT_ROOT'] . '/securimage/securimage.php';


# Validate user input from form post
$name = filter_input( INPUT_POST, 'username', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_]{1,64}$/" ) ) );
$email_address = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );
$password = filter_input( INPUT_POST, 'password', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/" ) ) );
if ( $password ) $password_hash =  hash( 'sha512', $password );
$confirm = filter_input( INPUT_GET, 'confirm', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^true$/" ) ) );
$securimage = new Securimage();
$msg = '';

if ( DEBUG ) {
    echo "USERNAME: '$name'<br>";
    echo "EMAIL ADDRESS: '$email_address'<br>";
    echo "PASSWORD: '$password'<br>";
    if ( $password ) echo "PASSWORD HASH: '$password_hash'<br>";
}

#process the form
if ( !empty( $_POST ) ) {
    if ( $securimage->check( $_POST['captcha_code'] ) == FALSE ) {
    # the code was incorrect
    $msg .= "The security code entered was incorrect.<br>";
    } else { 
    # Only insert user if valid variabls exist
    if ( !$name) {
    # bad or empty username
      $msg .= "Invalid username, your username may only contain characters, numbers, or underscores.<br>";
    }
    if ( !$email_address ) {
      $msg .= "Invalid email address.<br>";
    }
    if ( !$password ) {
      $msg .= "Invalid password, your password must be at least 8 characters consisting of at least 1 lowercase character, 1 upercase character, and 1 special character.<br>";
    }
    # 
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
		  <td class="logo">Your email address was recently used to register at the PIKTUR website. If you did not create an account, please disregard this message. If you wish to activate the account, click on the following link: <a href="https://piktur.poly.edu/activate_account.php?email='.$email_address.'&key='.$password_hash.'">Activate Account</a></td>
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
    <form id="add_user_form" name="add_user_form" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] ?>" method="post">
      <table border="0" cellpadding="2" cellspacing="2" width="100%">
        <tbody>
          <tr>
            <td colspan="1" rowspan="1" height="200" class="center_top">
              <table border="0" cellpadding="2" cellspacing="4" width="100%">
                <tbody>
                  <tr>
                    <td class="formlabel" width="50%">Username:</td>
                    <td class="forminput" width="50%">
                      <input size="18" name="username" id="username" type="text"<?php if ( $name ) echo " value=\"$name\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel" width="50%">Email:</td>
                    <td class="forminput" width="50%">
                      <input size="18" name="email" id="email" type="text"<?php if ( $email_address ) echo " value=\"$email_address\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel" width="50%">Confirm Email:</td>
                    <td class="forminput" width="50%">
                      <input size="18" name="email2" id="email2" type="text"<?php if ( $email_address ) echo " value=\"$email_address\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel" width="50%">Password:</td>
                    <td class="forminput" width="50%">
                      <input size="18" name="password" id="password" type="password"<?php if ( $password ) echo " value=\"$password\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel" width="50%">Confirm Password:</td>
                    <td class="forminput" width="50%">
                      <input size="18" name="password2" id="password2" type="password"<?php if ( $password ) echo " value=\"$password\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel" width="50%"><img id="captcha" src="/securimage/securimage_show.php" alt="CAPTCHA Image" /></td>
                    <td class="left_middle" width="50%">
                      <input type="text" name="captcha_code" size="18" maxlength="6" />
                      <a href="#" onclick="document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random(); return false">[ Different Image ]</a>
                    </td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td colspan="1" class="center_middle">
              <input type="image" src="<?php echo  $protocol . $_SERVER['SERVER_NAME'] ?>/img/signupbutton.png" height="45" width="125" border="0" alt="Signup Button">
            </td>
          </tr>
        </tbody>
      </table>
    </form>
    <table border="1" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td class="notice"><?php echo $msg ?></td>
        </tr>
      </tbody>
    </table>
<?php } ?>
    <script type="text/javascript" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/js/livevalidation_signup.js"></script>
  </body>
  <footer>
    <?php require 'footer.php'; ?>
  </footer>
</html>

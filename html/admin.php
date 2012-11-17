<?php
  require_once 'global.inc';

  # require user to be logged in
  if ( $_SESSION['authenticated'] != 'true' ) {
    header( 'Location: https://' . $_SERVER['SERVER_NAME'] . '/signin.php' );
  }

  $msg = "";

  # grab current values
  $id = $_SESSION['user_id'];
  # Prepare the MySQL update statement on the server
  if ( !( $stmt = $db->prepare( "SELECT `users`.`name`, `users`.`email_address`, `users`.`password_hash` FROM `piktur`.`users` WHERE `users`.`user_id` = ?;" ) ) ) {
    die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
  } else {
    # Bind the variables into the prepared statement
    if ( !$stmt->bind_param( 'i', $id ) ) {
      die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
    } else {
    # Execute the SQL command
      if ( !$stmt->execute() ) {
        die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
      } else {
        # Bind results
        $stmt->bind_result( $old_name, $old_email, $old_pass );
        # Fetch the value
        $stmt->fetch();
        # Cleanup statement
        $stmt->close();
      }
    }
  } # end of grab current values

  # Validate user input from form post
  $name = filter_input( INPUT_POST, 'username', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_]{1,64}$/" ) ) );
  $email_address = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );
  $password = filter_input( INPUT_POST, 'password', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/" ) ) );
  $oldpassword = filter_input( INPUT_POST, 'oldpassword', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/" ) ) );
  if ( $password ) $password_hash =  hash( 'sha512', $password );
  if ( $oldpassword ) $oldpassword_hash =  hash( 'sha512', $oldpassword );

  if ( DEBUG ) {
    echo "USERNAME: '$name'<br>";
    echo "EMAIL ADDRESS: '$email_address'<br>";
    echo "Old PASSWORD: '$oldpassword'<br>";
    if ( $oldpassword ) echo "PASSWORD HASH: '$oldpassword_hash'<br>";
    echo "PASSWORD: '$password'<br>";
    if ( $password ) echo "PASSWORD HASH: '$password_hash'<br>";
    echo "Original PASSWORD: '$old_pass'<br>";
  }

  # check for old password match
  if ( $oldpassword_hash != NULL ) {
    if ( $oldpassword_hash == $old_pass ) {
      # check for valid inputs
      if ( $name and $email_address and $password ) {
            # Prepare the MySQL insert statement on the server
           if ( !( $stmt = $db->prepare( "UPDATE `piktur`.`users` SET `name` = ?, `email_address` = ?, `password_hash` = ? WHERE `users`.`user_id` = ?" ) ) ) {
                die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
            } else {
                # Bind the variables into the prepared statement
                if ( !$stmt->bind_param( 'sssi', $name, $email_address, $password_hash, $id ) ) {
                    die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
                } else {
                    # Execute the SQL command
                    if ( !$stmt->execute() ) {
                        die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
                    }
                    # Cleanup statement
                        $stmt->close();
                }
            
            } 
      }
    } else {
      $msg = 'Old password is incorrect';
      }
  }
  require 'header.php';
?>

<body>
  <form id="update_user_form" name="update_user_form" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . '/' . $_SERVER['PHP_SELF'] ?>" method="post">
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td colspan="1" rowspan="1" height="200" class="center_top">
            <table border="0" cellpadding="2" cellspacing="4" width="100%">
              <tbody>
                <tr>
                  <td class="formlabel">Display Name [<?php echo "$old_name"; ?>]:</td>
                  <td class="forminput">
                    <input size="18" name="username" id="username" type="text"<?php if ( $name ) echo " value=\"$name\""; ?>>
                  </td>
                </tr>
                <tr>
                  <td class="formlabel">New Email [<?php echo "$old_email"; ?>]:</td>
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
                  <td class="formlabel">Old Password:</td>
                  <td class="forminput">
                    <input size="18" name="oldpassword" id="oldpassword" type="password"<?php if ( $oldpassword ) echo " value=\"$oldpassword\""; ?>>
                  </td>
                </tr>
                <tr>
                  <td class="formlabel">New Password:</td>
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
           <td class="notice"><?php echo $msg ?></td>
        </tr>
        <tr>
          <td colspan="1" class="center_middle">
            <input type="image" src="<?php echo  $protocol . $_SERVER['SERVER_NAME'] ?>/img/signupbutton.png" height="45" width="125" border="0" alt="Signup Button">
          </td>
        </tr>
      </tbody>
    </table>
  </form>
  <?php require 'footer.php'; ?>
  <script type="text/javascript" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/js/livevalidation_signup.js"></script>
</body>

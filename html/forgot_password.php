<?php
  require_once 'global.inc';
  include_once $_SERVER['DOCUMENT_ROOT'] . '/securimage/securimage.php';

  # prepare variables
  $msg = '';
  $email = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );
  $securimage = new Securimage();
  $confirm = filter_input( INPUT_GET, 'confirm', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^true$/" ) ) );
  $key = filter_input( INPUT_GET, 'key', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[0-9a-f]{128}$/" ) ) );
  $password = filter_input( INPUT_POST, 'password', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/" ) ) );
  $fh_flag = filter_input( INPUT_GET, 'fh_flag', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^true$/" ) ) );

  if ( $confirm ) {
    $msg = "An email has been sent with a link to change your password.";
  }

  if ( DEBUG ) {
    echo "EMAIL: ".$email.'<br>';
    echo "keyS: $key <br>";
  }

  # Issue #69.  Add code to not automatically lock out user, and lock account, if anyone provides as valid email
  # address.  This new code will send Link to users email, and allow the user to click on the link and change
  # password.  Note:  this is may first attempt at php coding, so there are probably some bugs.
  if ( $key )
  {
      $email = filter_input( INPUT_GET, 'email', FILTER_VALIDATE_EMAIL );
      if ( !$email ) {
          $msg .= "Invalid email address.<br>";
      } else {
            #check for email address
            # Prepare the MySQL select statement on the server
            if ( !( $stmt = $db->prepare( "SELECT `piktur`.`users`.`forgotten_hash` FROM `piktur`.`users` WHERE `piktur`.`users`.`email_address` = ?;" ) ) ) {
              die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
            } else {
                # Bind the variables into the prepared statement
                if ( !$stmt->bind_param( 's', $email ) ) {
                   die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
                } else {
                    # Execute the SQL command
                    if ( !$stmt->execute() ) {
                       die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
                    } else {
                       $stmt->store_result();
                       $results = $stmt->num_rows;
                       $stmt->bind_result( $forgotten_hash );
                       $stmt->fetch();
                       # Cleanup statement
                       $stmt->close();

                       $confirm = 'true';
                       if ( $key == $forgotten_hash ) {
                          if ( $fh_flag  )
                          {
                            $confirm = 'true';
                            if ( $_POST['password'] ) {
                                if ( !$password ) {
                                    $msg .= "Invalid password, your password must be at least 8 characters consisting of at least 1 lowercase character, 1 upercase character, and 1 special character.<br>";
                                 }
                                // then store in db.
                                if ( !( $stmt = $db->prepare( "UPDATE `piktur`.`users` SET `piktur`.`users`.`password_hash` = ? WHERE `piktur`.`users`.`email_address` = ?;" ) ) ) {
                                    die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
                                } else {
                                   # Bind the variables into the prepared statement
                                   $password_hash =  hash( 'sha512', $password );
                                   if ( !$stmt->bind_param( 'ss', $password_hash, $email ) ) {
                                       die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
                                   } else {
                                   # Execute the SQL command
                                       if ( !$stmt->execute() ) {
                                            die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
                                        }
                                        # Cleanup statement
                                        $stmt->close();
                                    }
                                 }  #else prepare
                                 $fh_flag = 'true';
                                 $confim = 'true';
                                 $msg='password change successful!';
                             } else { die( 'passwd ==TRUE' ); }  #if password == TRUE
                           } else { #if fh_flag
                              $fh_flag = 'true';
                           }
                        } else {
                           $msg='password does not match ';
                       }
                  } # if execute
               } # if bind
           } #if prepare
       } #if email
   } #if key


  #process the form
   if (( !empty( $_POST )) && (!$fh_flag)) {
      if ( $securimage->check( $_POST['captcha_code'] ) == FALSE ) {
        $msg .= "The security code entered was incorrect.<br>";
      } else {
        if ( !$email ) {
          $msg .= "Invalid email address.<br>";
        } else {

            #check for email address
            # Prepare the MySQL select statement on the server
            if ( !( $stmt = $db->prepare( "SELECT `piktur`.`users`.`name` FROM `piktur`.`users` WHERE `piktur`.`users`.`email_address` = ?;" ) ) ) {
              die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
            } else {
              # Bind the variables into the prepared statement
              if ( !$stmt->bind_param( 's', $email ) ) {
                die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
              } else {
                  # Execute the SQL command
                  if ( !$stmt->execute() ) {
                    die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
                  } else {
                      $stmt->store_result();
                      $results = $stmt->num_rows;
                      $stmt->bind_result( $name );
                      # Cleanup statement
                      $stmt->close();

                      # if you have anything other than 1 result an error occured.
                      # This error is not disclosed to prevent the disclosure of valid email addresses.

                      if ( $results == '1' ) {
                        # generate new password
                        $allowable = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~!@#$%^&*()_+-={}|[]\:<>?";
                        $valid[0] = "abcdefghijklmnopqrstuvwxyz";
                        $valid[1] = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                        $valid[2] = "~!@#$%^&*()_+-={}[]<>";
                        $valid[3] = "0123456789";
                        $used = '';
                        $maxlength = 20;
                        $password = '';
                        $i = 0;
                        # wasn't able to figure out an easy way to guarantee one of each upper, lower, number, and special so hacked this together
                        $j = 0;
                        $k = 0;
                        while ( $j < 4 ) {
                          while ( $k < 6 ) {
                            $char = substr($valid[$j], mt_rand(0, (strlen($valid[$j]))-1), 1);
                            # don't allow repeats
                            if (!strstr($used, $char)) {
                              $used .= $char;
                              $k++;
                            }
                          }
                          $j++;
                          $k = 0;
                        }
                        while ( $i < $maxlength ) {
                          $char = substr($used, mt_rand(0, (strlen($used))-1), 1);
                          # don't allow repeats
                          if (!strstr($password, $char)) {
                            $password .= $char;
                            $i++;
                          }
                        }
                        #$password_hash =  hash( 'sha512', $password );
                        $forgotten_hash = hash( 'sha512', $password );

                        # Update forgotten_hash in db. 
                        if ( !( $stmt = $db->prepare( "UPDATE `piktur`.`users` SET `piktur`.`users`.`forgotten_hash` = ? WHERE `piktur`.`users`.`email_address` = ?;" ) ) ) {
                          die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
                        } else {
                            # Bind the variables into the prepared statement
                            if ( !$stmt->bind_param( 'ss', $forgotten_hash, $email ) ) {
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

                        # Send email to user to reset password
                        $subject = 'PIKTUR Password Reset';
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
                            <td class="logo">If you have fogotten your password, click on the following link to reset it: <a href="https://piktur.poly.edu/forgot_password.php?email='.$email.'&key='.$forgotten_hash.'">Change Password</a></td>
                          </tr>
                          </table>
                        </body>
                        </html>
                        ';

                        $headers  = 'MIME-Version: 1.0' . "\r\n";
                        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                        $headers .= 'From: webmaster@piktur.poly.edu' . "\r\n";
                        $headers .= 'X-Mailer: PHP/' . phpversion();

                        if ( mail( "$name<$email>", $subject, $message, $headers ) ) {
                            header ( 'Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?confirm=true' );
                        }
                      }
                    }
                }
            }
          }
    }
  }
?>
<html>
  <header>
    <?php require 'header.php'; ?>
  </header
  <body>
    <?php if ( !$confirm ) { ?>
      <div id="header">
        <h1>Forgotten Password</h1>
      </div>
      <div id="content">
        <p>To obtain a new password, please enter your e-mail address and a link will be emailed to you.</p>
        <form id="password_reset_form" name="password_reset_form" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
          <table border="0" cellpadding="2" cellspacing="2" width="100%">
            <tr>
              <td class="formlabel" width="50%">Email:</td>
              <td class="forminput" width="50%">
                <input size="18" name="email" id="email" type="text"<?php if ( $email ) echo " value=\"$email\""; ?>>
              </td>
            </tr>
            <tr>
              <td class="formlabel" width="50%"><img id="captcha" src="<?php echo  $protocol . $_SERVER['SERVER_NAME'] ?>/securimage/securimage_show.php" alt="CAPTCHA Image" /></td>
              <td class="left_middle" width="50%">
                <input type="text" name="captcha_code" size="18" maxlength="6" />
                <a href="#" onclick="document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random(); return false">[ Different Image ]</a>
              </td>
            </tr>
            <tr>
              <td class="center_middle" colspan="2">
                <input type="image" src="<?php echo  $protocol . $_SERVER['SERVER_NAME'] ?>/img/submitbutton.gif" height="45" width="125" border="0" alt="Submit Button">
              </td>
            </tr>
          </table>
        </form>
      <?php } elseif ( $fh_flag == 'true') { ?>
        <form id="add_user_form" name="add_user_form" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?email='.$email.'&key='.$forgotten_hash.'&fh_flag=true' ?>" method="post">
      <table border="0" cellpadding="2" cellspacing="2" width="100%">
        <tbody>
          <tr>
            <td colspan="1" rowspan="1" height="200" class="center_top">
              <table border="0" cellpadding="2" cellspacing="4" width="100%">
                <tbody>
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
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td colspan="1" class="center_middle">
                <input type="image" src="<?php echo  $protocol . $_SERVER['SERVER_NAME'] ?>/img/submitbutton.gif" height="45" width="125" border="0" alt="Submit Button">
            </td>
          </tr>
        </tbody>
      </table>
    </form>
    <?php   } ?>
      <table border="1" cellpadding="2" cellspacing="2" width="100%">
        <tbody>
          <tr>
            <td class="notice"><?php echo $msg ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  <script type="text/javascript" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/js/livevalidation_forgotten_password.js"></script>
  </body>
  <footer>
    <?php require 'footer.php'; ?>
  </footer>
</html>

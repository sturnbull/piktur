<?php
  require_once 'global.inc';
  include_once $_SERVER['DOCUMENT_ROOT'] . '/securimage/securimage.php';
  
  # prepare variables
  $msg = '';
  $email = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );
  $securimage = new Securimage();
  $confirm = filter_input( INPUT_GET, 'confirm', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^true$/" ) ) );
  
  if ( $confirm ) {
    $msg = "An email has been sent with a link to re-activate your account.";
  }
  
  if ( DEBUG ) {
    echo "EMAIL: ".$email.'<br>';
  }
  
  #process the form
  if ( !empty( $_POST ) ) {
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
                        $password_hash =  hash( 'sha512', $password );
                        
                        # Update password hash in database and disable the account
                        if ( !( $stmt = $db->prepare( "UPDATE `piktur`.`users` SET `piktur`.`users`.`password_hash` = ?, `piktur`.`users`.`account_status` = b'0' WHERE `piktur`.`users`.`email_address` = ?;" ) ) ) {
                          die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
                        } else {
                            # Bind the variables into the prepared statement
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
                          }
                        
                        # Send email to user to activate account
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
                            <td class="logo">Your password has been reset at the PIKTUR website. To activate the account, click on the following link to activate the account: <a href="https://piktur.poly.edu/reset_account.php?email='.$email.'&key='.$password_hash.'">Activate Account</a></td>
                          </tr>
                          <tr>
                            <td class="logo">Your new password is '.$password.'.  Please change it once you have activated your account.
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
      <?php } ?>
      <table border="1" cellpadding="2" cellspacing="2" width="100%">
        <tbody>
          <tr>
            <td class="notice"><?php echo $msg ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  <script type="text/javascript" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/js/livevalidation_signup.js"></script>
  </body>
  <footer>
    <?php require 'footer.php'; ?>  
  </footer>
</html>

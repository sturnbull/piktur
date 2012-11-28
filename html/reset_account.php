<?php
  require_once 'global.inc';

  $email_address = filter_input( INPUT_GET, 'email', FILTER_VALIDATE_EMAIL );
  $hash = filter_input( INPUT_GET, 'key', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[0-9a-f]{128}$/" ) ) );
  $message = 'Your account was not activated. Please contact the website administrator for assistance.';
  $name = '';

  if ( $email_address and $hash ) {
    # Prepare the MySQL select statement on the server
    if ( !( $stmt = $db->prepare( 'SELECT `user_id`, `name` FROM `piktur`.`users` WHERE `email_address` = ? AND `password_hash` = ?' ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    } else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'ss', $email_address, $hash ) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        }	else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
              die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            } else {
                # Bind results
                $stmt -> bind_result( $id, $name );
                # Fetch the value
                $stmt -> fetch();
                # Cleanup statement
                $stmt->close();
              }
          }
      }

      # Prepare the MySQL update statement on the server
      if ( !( $stmt = $db->prepare( "UPDATE `piktur`.`users` SET `piktur`.`users`.`account_status` = b'1' WHERE `piktur`.`users`.`user_id` = ?" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
      } else {
          # Bind the variables into the prepared statement
          if ( !$stmt->bind_param( 'i', $id ) ) {
            die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
          } else {
              # Execute the SQL command
              if ( !$stmt->execute() ) {
                die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
              }	else {
                  $message = 'Your account has been activated. You can sign in using the link above.';
                  # Cleanup statement
                  $stmt->close();
              }
            }
        }
  }
?>
<html>
  <header>
    <?php require 'header.php'; ?>
  </header>
  <body>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td class="notice"><?php echo $message ?></td>
        </tr>
      </tbody>
    </table>
  </body>
  <footer>
    <?php require 'footer.php'; ?>
  </footer>
</html>


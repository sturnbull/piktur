<?php
require_once 'global.inc';

$email_address = filter_input( INPUT_GET, 'email', FILTER_VALIDATE_EMAIL );
$hash = $_GET['key'];
$message = 'Your account was not activated. Please contact the website administrator for assistance.';

if ( $email_address and $hash ) {
    # Prepare the MySQL select statement on the server
    if ( !( $stmt = $db->prepare( 'SELECT `user_id` FROM `piktur`.`users` WHERE `email_address` = ?' ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    }
    else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 's', $email_address ) ) {
            die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        }
	else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
                die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            }
            else {
                # Bind results
                $stmt -> bind_result( $result );

                # Fetch the value
                $stmt -> fetch();

                # Cleanup statement
                $stmt->close();
            }
        }
    }

    # Prepare the MySQL update statement on the server
    if ( !( $stmt = $db->prepare( "UPDATE `piktur`.`users` SET `account_status` = b'1' WHERE `users`.`user_id` = ?" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    }
    else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'i', $result ) ) {
            die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        }
        else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
                die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            }	
            else {
                $message = 'Your account has been activated. You can sign in using the link above.';

                # Cleanup statement
                $stmt->close();
            }
	}
    }
}

require 'header.php';
?>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td class="notice"><?php echo $message ?></td>
        </tr>
      </tbody>
    </table>
<?php require 'footer.php'; ?>
  </body>
</html>


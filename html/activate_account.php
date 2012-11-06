<?php
require_once 'global.inc';

$email_address = filter_input( INPUT_GET, 'email', FILTER_VALIDATE_EMAIL );
$hash = $_GET['key'];
$message = 'Your account was not activated. Please contact the website administrator for assistance.';
$album_id = '';
$album_name = 'default';
$album_description = 'Default album for user';
$basedir = '/var/www/html';
$path = '';
$name = '';

if ( $email_address and $hash ) {
    # Prepare the MySQL select statement on the server
    if ( !( $stmt = $db->prepare( 'SELECT `user_id`, `name` FROM `piktur`.`users` WHERE `email_address` = ?' ) ) ) {
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
                $stmt -> bind_result( $id, $name );

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
        if ( !$stmt->bind_param( 'i', $id ) ) {
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

		# Create folder to store user albums
                $path =  'pikturs/'.$name.'/default';
                if (!is_dir( "${basedir}/${path}" ) ) {
                    if ( !mkdir( "${basedir}/${path}", 0770, true ) ) {
                        die('Failed to create folder for user albums.');
                    }
                }
            }
	}
    }

    # Prepare the MySQL update statement on the server
    if ( !( $stmt = $db->prepare( "INSERT INTO `piktur`.`albums` ( `album_name`, `album_description`, `path`, `user_id` ) VALUES ( ?, ?, ?, ? );" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    }
    else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'sssi', $album_name, $album_description, $path, $id ) ) {
            die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        }
        else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
#                die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            }
            else {
                # Cleanup statement
                $stmt->close();
            }
        }
    }

    # Prepare the MySQL update statement on the server
    if ( !( $stmt = $db->prepare( "SELECT `albums`.`album_id` FROM `piktur`.`albums` WHERE `albums`.`album_name` = ? AND `albums`.`album_description` = ? AND `albums`.`user_id` = ?;" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    }
    else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'ssi', $album_name, $album_description, $id ) ) {
            die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        }
        else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
                die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            }
            else {
                # Bind results
                $stmt->bind_result( $album_id  );

                # Fetch the value
                $stmt->fetch();

                # Cleanup statement
                $stmt->close();
            }
        }
    }


    # Prepare the MySQL update statement on the server
    if ( !( $stmt = $db->prepare( "INSERT INTO `piktur`.`permissions` ( `access_type`, `album_id`, `user_id` ) VALUES ( ?, ?, ? );" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    }
    else {
	$perm = 'delete';
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'sii', $perm, $album_id, $id ) ) {
            die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        }
        else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
                die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            }
            else {
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

<?php
require_once 'global.inc';

# user input validation
$name = filter_input( INPUT_POST, 'albumname', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_]{1,64}$/" ) ) );
$description = filter_input( INPUT_POST, 'albumdescription', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_]{1,64}$/" ) ) );
$user = $_SESSION['name'];
$id = $_SESSION['user_id'];
$msg = '';
$path = './pikturs/'. $user . '/' . $name;
$perm = 'delete';

# require user to be logged in
if ( $_SESSION['authenticated'] != 'true' ) {
  header( 'Location: https://' . $_SERVER['SERVER_NAME'] . '/signin.php' );
}

if ($name) {
  # if there is a name and no description, update then display error
  if (!$description) {
    $msg = 'Please enter a valid album description.<br>';
  } else {
    # check for an existing directory
    if (is_dir( $path ) ) {
      $msg = "The album already exists.<br>";
    } else {
      # create folder for the new album
      if ( !mkdir( $path, 0770, true ) ) {
        die('Failed to create folder for user albums.');
      }
      echo "This would create ${path}.<br>";
      
      # Insert New Album into Album Table
      # Prepare the MySQL update statement on the server
      if ( !( $stmt = $db->prepare( "INSERT INTO `piktur`.`albums` ( `album_name`, `album_description`, `path`, `user_id` ) VALUES ( ?, ?, ?, ? );" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
      } else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'sssi', $name, $description, $path, $id ) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        } else {
          # Execute the SQL command
          if ( !$stmt->execute() ) {
            die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
          } else {
            # Cleanup statement
            $stmt->close();
            }
          }
      } # end of Album Table update
      
      # Get the newly created album id
      # Prepare the MySQL update statement on the server
      if ( !( $stmt = $db->prepare( "SELECT `albums`.`album_id` FROM `piktur`.`albums` WHERE `albums`.`album_name` = ? AND `albums`.`album_description` = ? AND `albums`.`user_id` = ?;" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
      } else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'ssi', $name, $description, $id ) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        } else {
        # Execute the SQL command
          if ( !$stmt->execute() ) {
            die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
          } else {
            # Bind results
            $stmt->bind_result( $album_id  );
            # Fetch the value
            $stmt->fetch();
            # Cleanup statement
            $stmt->close();
          }
        }
      } # end of album id get
      
      # Add permissions to album
      # Prepare the MySQL update statement on the server
      if ( !( $stmt = $db->prepare( "INSERT INTO `piktur`.`permissions` ( `access_type`, `album_id`, `user_id` ) VALUES ( ?, ?, ? );" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
      } else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'sii', $perm, $album_id, $id ) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        } else {
          # Execute the SQL command
          if ( !$stmt->execute() ) {
            die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
          } else {
            # Cleanup statement
            $stmt->close();
          }
        }
      } # end of permissions update 
    $msg = "The album was successfully created.<br>";
    } # end of new album creation    
  } # end of valid description
} # end of valid name

# debug block
if ( DEBUG == TRUE ) {
    echo DEBUG;
    echo "Album Name: '$name'<br>";
    echo "Album Description: '$description'<br>";
    echo "User: '$user'<br>";
    echo "Path: '$path'<br>";
    echo "USER_ID: ${id}<br>";
    echo "ALBUM_ID[$i]: $album_id<br>";
    echo "ALBUM_NAME[$i]: $album_name<br>";
    echo "ALBUM_DESCRIPTION[$i]: $album_description<br>";
}

require 'header.php';
?>

<html>
<?php if ( $confirm ) { ?>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td class="notice">The account has been created.</td>
        </tr>
      </tbody>
    </table>
<?php } else { ?>
    <form id="create_album_form" name="create_album_form" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . '/' . $_SERVER['PHP_SELF'] ?>" method="post">
      <table border="0" cellpadding="2" cellspacing="2" width="100%">
        <tbody>
          <tr>
            <td colspan="1" rowspan="1" height="200" class="center_middle">
              <table border="0" cellpadding="2" cellspacing="4" width="100%">
                <tbody>
                  <tr>
                    <td class="formlabel">Album Name:</td>
                    <td class="forminput">
                      <input size="18" name="albumname" id="albumname" type="text"<?php if ( $name ) echo " value=\"$name\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel">Album Description:</td>
                    <td class="forminput">
                      <input size="18" name="albumdescription" id="albumdescription" type="text"<?php if ( $name ) echo " value=\"$description\""; ?>>
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
              <input type="image" src="<?php echo  $protocol . $_SERVER['SERVER_NAME'] ?>/img/newbutton.png" height="45" width="125" border="0" alt="Create Album Button">
            </td>
          </tr>
        </tbody>
      </table>
    </form>
<?php } ?>
<?php require 'footer.php'; ?>
    <script type="text/javascript" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/js/livevalidation_createalbum.js"></script>
  </body>
</html>

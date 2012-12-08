<?php
  require_once '/etc/piktur/global.inc';

  # require user to be logged in
  if ( $_SESSION['authenticated'] != 'true' ) {
    header( 'Location: https://' . $_SERVER['SERVER_NAME'] . '/signin.php' );
  }

  # user input validation
  $name = filter_input( INPUT_POST, 'albumname', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_]{1,64}$/" ) ) );
  $description = filter_input( INPUT_POST, 'albumdescription', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_]{1,64}$/" ) ) );
  $user = $_SESSION['name'];
  $id = $_SESSION['user_id'];
  $msg = '';
  $path = '/pikturs/'. $user . '/' . $name;
  $thumbpath = '/THUMB_pikturs/'. $user . '/' . $name;
  $perm = 'delete';
  $album_array = array(); 
  $albumID_array = array(); 

  if ( isset( $_SESSION['user_id'] ) ) {
    # Prepare the MySQL select statement on the server
    if ( !( $stmt = $db->prepare( "SELECT `albums`.`album_id`, `albums`.`album_name` FROM `albums` JOIN `permissions` ON `albums`.`album_id`=`permissions`.`album_id` WHERE `permissions`.`user_id` = ? AND `permissions`.`access_type`='delete';" ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    } else {
        # Bind the variables into the prepared statement
        $start = $page * $albums_per_page;
        if ( !$stmt->bind_param( 'i', $_SESSION['user_id'] ) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        } else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
              die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            } else {
                # Get total of records in the result set
                $stmt->store_result();
                $results = $stmt->num_rows;
                # Bind results
                $stmt->bind_result( $album_id, $album_name );
                # Fetch the values into arrays
                $i = 0;
                while ( $stmt->fetch() ) {
                  $album_array[] = $album_name; 
                  $albumID_array[] = $album_id;             
                  # Review results
                  if ( DEBUG ) {
                    echo "USER_ID: ".$_SESSION['user_id'].'<br />';
                    echo "ALBUM_ID[$i]: $album_id<br />";
                    echo "ALBUM_NAME[$i]: $album_name<br />";
                  }
                  $album_names[$i] = $album_name;
                  $album_id[$i] = $album_id;
                  $i++;             
                }
                unset( $i );
                # Cleanup statement
                $stmt->close();
              }
          }
      }
  }
  # debug block
  if ( DEBUG ) {
    echo "Album Name: '$name'<br>";
    echo "Album Description: '$description'<br>";
    echo "User: '$user'<br>";
    echo "Path: '$path'<br>";
    echo "USER_ID: ${id}<br>";
    echo "ALBUM_ID[$i]: $album_id<br>";
    echo "ALBUM_NAME[$i]: $album_name<br>";
    echo "ALBUM_DESCRIPTION[$i]: $album_description<br>";
  }  
  $confirm ="";
  $confirm=$_POST['confirm'];
  $toDelete=$_POST['delete'];

  if ( $confirm =="TRUE" ) { 
    if ( !( $stmt = $db->prepare( "SELECT `albums`.`album_name` FROM `piktur`.`albums` WHERE `albums`.`album_id`=?;" ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    } else {		 
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 's', $toDelete) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        } else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
              die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            } else {
                # Bind results
                $stmt->bind_result($album_name );
                $stmt->fetch();            
                $path = "$path$album_name" ;
                $thumbpath = "$thumbpath$album_name" ;
                $path = getcwd() . "$path"."/";
                $thumbpath = getcwd() . "$thumbpath"."/";
                if (is_dir($path)) {              
                  system('/bin/rm -rf ' . escapeshellarg($path));
                  system('/bin/rm -rf ' . escapeshellarg($thumbpath));
                }
              }
              # Cleanup statement
              $stmt->close();
          }
      }
    #album delete
    if ( !( $stmt = $db->prepare( "DELETE FROM `piktur`.`albums` WHERE `albums`.`album_id`=?;" ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    } else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 's', $toDelete) ) {
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
    #album_images delete
    if ( !( $stmt = $db->prepare( "DELETE FROM `piktur`.`album_images` WHERE `album_images`.`album_id`=?;" ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    } else {		 
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 's', $toDelete) ) {
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
    #permissions delete
    if ( !( $stmt = $db->prepare( "DELETE FROM `piktur`.`permissions` WHERE `permissions`.`album_id`=?;" ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    } else {		 
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 's', $toDelete) ) {
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
?>
<html>
  <?php require 'header.php'; ?>
  <body>
    <?php if ( $confirm ) { ?>
      <table border="0" cellpadding="2" cellspacing="2" width="100%">
        <tbody>
          <tr>
            <td class="notice">The album has been deleted.</td>
          </tr>
        </tbody>
      </table>
    <?php } else { ?>
      <form id="delete_album_form" name="delete_album_form" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] ?>" method="post">
        <table border="0" cellpadding="2" cellspacing="2" width="100%">
          <tbody>
            <tr>                
              <td class="formlabel" width="50%">Albums</td>
              <td class="forminput" width="50%">
                <select name="delete">
                  <?php
                    $x = count($albumID_array);
                    for($i = 0; $i < $x; $i++) {
                  ?>   
                  <option value=<?php echo "\"$albumID_array[$i]\">$album_array[$i]" ?></option>
                  <?php } ?>       
                </select>
              <input class="forminput" width="50%" type="hidden" name="confirm" value="TRUE" />
              </td>
            </tr>
            <?php if ($msg) { ?>
              <tr>
                <td class="notice"><?php echo $msg ?></td>
              </tr>
            <?php } ?>
            <tr>
              <td colspan="2" class="center_middle">
                <input type="image" src="<?php echo  $protocol . $_SERVER['SERVER_NAME'] ?>/img/deletebutton.png" height="45" width="125" border="0" alt="Delete Album Button"  />
              </td>
            </tr>
          </tbody>
        </table>
      </form>
    <?php } ?>   
  </body>
  <script type="text/javascript" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/js/livevalidation_createalbum.js"></script>
  <?php require 'footer.php'; ?>
</html>

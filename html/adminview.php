<?php
  require_once '/etc/piktur/global.inc';

  # Define page layout - reset by PRH 11/11
  $albums_per_page = 12; # reset by PRH 11/11
  $albums_per_row = 4; # reset by PRH 11/11
  $rows_per_page = 3; # reset by PRH 11/11
  $i = 0;
  $j = 0;
  $k = 0;
  $results = 0; #holds number of user albums
  $page = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT, array( array("min_range"=>0) ) );
  if ( !isset ( $page ) ) { $page = 0; };
  $ids = array(); 
  $names = array();
  $descriptions = array();
  $album_id = '';
  $album_name = '';
  $album_description = '';

  # require user to be logged in and an admin
  if ( $_SESSION['authenticated'] != 'true' ) {
      header( 'Location: https://' . $_SERVER['SERVER_NAME'] . '/signin.php' );
  }
  if( $_SESSION['admin_flag'] != 'true' ) {
      header( 'Location: https://' . $_SERVER['SERVER_NAME'] . '/albumview.php' );
  }

  # Only search if valid user_id variable exists
  if ( isset( $_SESSION['user_id'] ) ) {
    # Prepare the MySQL select statement on the server
    if ( !( $stmt = $db->prepare( "SELECT `permissions`.`user_id` , `users`.`name`, `albums`.`album_id`, `albums`.`album_name`, `images`.`image_id`, CONCAT( `albums`.`path`, '/', `images`.`file_name` ) AS file, `images`.`file_name`, `images`.`description` FROM `albums` JOIN `permissions` ON `albums`.`album_id`=`permissions`.`album_id` JOIN `users` ON `permissions`.`user_id`=`users`.`user_id` JOIN `album_images` ON `album_images`.`album_id`=`albums`.`album_id` JOIN `images` ON `images`.`image_id`=`album_images`.`image_id` ORDER BY `name`, `album_name`, `file_name`;" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    } else {
        # Execute the SQL command
        if ( !$stmt->execute() ) {
          die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
        } else {
            # Get total of records in the result set
            $stmt->store_result();
            $results = $stmt->num_rows;
            # Bind results
            $stmt->bind_result( $owner_id,$owner,$album_id, $album_name, $image_id, $file, $image_name, $image_description);
            # Fetch the values into arrays
            while ( $stmt->fetch() ) {
              # Review results
              if ( DEBUG ) {
                echo "USER_ID: ".$_SESSION['user_id'].'<br>';
                echo "OWNER_ID[$i]: ".$owner_id.'<br>';
                echo "OWNER[$i]: ".$owner.'<br>';
                echo "ALBUM_ID[$i]: $album_id<br>";
                echo "ALBUM_NAME[$i]: $album_name<br>";
                echo "ALBUM_DESCRIPTION[$i]: $album_description<br>";
              }
              $owner_ids[$i] = $owner_id;
              $owners[$i] = $owner;
              $ids[$i] = $album_id;
              $names[$i] = $album_name;
              $descriptions[$i] = $album_description;
              $image_ids[$i] = $image_id;
              $files[$i] = $file;
              $image_names[$i] = $image_name;
              $image_descriptions[$i] = $image_description;
              $i++;
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
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tr>
        <td colspan="3" rowspan="1" align="center" height="200" valign="top" width="100%">
          <table border="0" cellpadding="2" cellspacing="10" width="100%">
            <tr>
               <td>Image Thumbnail</td>
               <td>Owner Name</td>
               <td>Album Name</td>
               <td>Image Name</td>
               <td>Image Description</td>
            <tr>
              <?php 
                $i=0; # set the inital  number
                while ( $i < $results) { # display users album ?>
                  <tr>
                    <td><img src="<?php echo getDataURI(  preg_replace( "/\/var\/www\/pikturs\//", "/var/www/THUMB_pikturs/",$files[$i] ) ) ?>" ></td>
                    <td><a href=""><?php echo $owners[$i]?></a></td>
                    <td><a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/delete_album.php?album='.$ids[$i] ?>"><?php echo $names[$i]?></a></td>
                    <td><a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/delete_image.php?image='.$image_ids[$i] ?>" ><?php echo $image_names[$i] ?></a></td>
                    <td><?php echo $image_descriptions[$i] ?></td>
                  </tr>
              <?php
                    $i++; # increment album number
                    } ?>
            </tr>
            <tr>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td colspan="3"><hr size="3" width="100%"></td>
      </tr>
      <tr>
        <td class="center_middle" width = "30%">
        </td>
        <td class="center_middle">
        </td>
        <td class="center_middle" width = "30%">
        </td>
      </tr>
      <tr>
        <td colspan="3"><hr size="3" width="100%"></td>
      </tr>
    </table>    
  </body>
  <?php require 'footer.php'; ?>
</html>

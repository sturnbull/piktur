<?php
require_once 'global.inc';

$preview = 0;

# Require user to be authenticated to use this page
if ( $_SESSION['authenticated'] != 'true' ) {
  header( 'Location: https://' . $_SERVER['SERVER_NAME'] . '/signin.php' );
}

# ALL SECTIONS: Ensure album has sane default
if ( !isset( $_SESSION['album_id'] ) ) {
  $_SESSION['album'] = 'default';
}
else {
  # Prepare the MySQL select statement on the server
  if ( !( $stmt = $db->prepare( "SELECT `album_name` FROM `piktur`.`albums` WHERE `album_id` = ? LIMIT 1;" ) ) ) {

      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
  }
  else {
      # Bind the variables into the prepared statement
      if ( !$stmt->bind_param( 'i', $_SESSION['album_id'] ) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
      }
      else {
          # Execute the SQL command
          if ( !$stmt->execute() ) {
              die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
          }
          else {
              # Bind results
              $stmt->bind_result( $album );

              # Fetch the value
              $stmt->fetch();

              # Cleanup statement
              $stmt->close();
          }
      }
      $_SESSION['album'] = $album;
  }
}

# SECOND PASS: Check if upload file submit button exists and was clicked
if ( isset($_POST['xsubmit'] ) ) {
	 
  # Define temp and new path to store image in
  $upload_dir = './uploads/';
  $uploadfile = $upload_dir . basename( $_FILES['upload']['name'] );
  $info = pathinfo( $uploadfile );

  # Build path to destination
  $folder = './pikturs/' . $_SESSION['name'] . '/' . $_SESSION['album'];
  $new_file = $folder . '/' . basename( $uploadfile, '.'.$info['extension']) . '.jpg';

  # ensure uploaded with no errors
  if ( $_FILES['upload']['error'] > 0 ) {
    echo 'Error uploading: ' . $_FILES['file']['error'] . '<br>';
  }
  else {
    # set allowed file types
    $allowedExts = array( 'jpg', 'jpeg', 'bmp', 'png' );

    # ensure file mime type and extension are correct
    $extension = end( explode( '.', $_FILES['upload']['name'] ) );
    if ( ( ( $_FILES['upload']['type'] == 'image/bmp' || ( $_FILES['upload']['type'] == 'image/jpeg' )
	  || ( $_FILES['upload']['type'] == 'image/png' ) ) && in_array( $extension, $allowedExts ) )
	  && ( $_FILES['upload']['size'] < 1999000 ) ) {

      # Move file to location accessible by apache for display back to user
      if ( ! move_uploaded_file( $_FILES['upload']['tmp_name'], $uploadfile ) ) {
        die( 'Possible file upload attack!' );
      }

      # Code to convert all images to max size of 800x600 72dpi and JPG format
      $img = new Imagick( $uploadfile );
      $img->setImageResolution( 72,72 ); 
      $img->resampleImage( 72, 72, imagick::FILTER_UNDEFINED, 1 );
      $img->scaleImage( 1024, 0 );
      $d = $img->getImageGeometry(); 
      $h = $d['height']; 
      if( $h > 768 ) {
        $img->scaleImage( 0, 768 );
      }
      $img->setImageFormat( 'jpeg' );
      $img->setImageCompression( imagick::COMPRESSION_JPEG ); 
      $img->setImageCompressionQuality( 80 ); 
      $img->stripImage(); 
      $img->writeImage( $uploadfile  );
      $img->destroy();  

      # Move standardized file to file album directory
      if ( ! rename( $uploadfile, $new_file ) ) {
        die( 'File rename failed.' );
      }
      $_SESSION['image_name'] =  basename( $new_file );
      $_SESSION['image_checksum'] = hash_file( 'md5', $new_file );

      # If File is valid set preview to 1 - second pass
      $preview = 1;

      if ( DEBUG ) {
        echo 'Upload: ' . $_FILES['upload']['name'] . '<br />';
        echo 'Type: ' . $_FILES['upload']['type'] . '<br />';
        echo 'Size: ' . ( $_FILES['upload']['size'] / 1024 ) . ' Kb<br />';
        echo 'Stored in: ' . $_FILES['upload']['tmp_name'];
        echo print_array( $_SESSION );
      }
    }
  }
}
# THIRD PASS: check if save file submit button was clicked
elseif ( isset( $_POST['zsubmit'] ) ) { 
  # If File is valid set preview to 2 - third pass
  $image_id = '';

  # Validate user input from form post
  $description = filter_input( INPUT_POST, 'description', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_ ]{1,255}$/" ) ) );
  $tags = filter_input( INPUT_POST, 'tags', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_, ]{1,255}$/" ) ) );
  $tag_list = preg_split( "/,/", $tags );
  $public = filter_input( INPUT_POST, 'public', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_ ]{1,255}$/" ) ) );

  if ( DEBUG ) {
      echo "DESCRIPTION: '$description'<br />";
      echo "TAGS: '$tags'<br />";
      echo "PUBLIC: '$public'<br />";
  }

  # Insert image data into image table
  # Prepare the MySQL insert statement on the server
  if ( !( $stmt = $db->prepare( 'INSERT INTO `piktur`.`images` ( `file_name`, `description`, `image_checksum`, `public` ) VALUES ( ?, ?, ?, ?)' ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
  }
  else {
      # Bind the variables into the prepared statement
      if ( !$stmt->bind_param( 'sssd', $_SESSION['image_name'], $description, $_SESSION['image_checksum'], $public ) ) {
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

  # Select image_id from image table
  # Prepare the MySQL select statement on the server
  if ( !( $stmt = $db->prepare( "SELECT `image_id` FROM `piktur`.`images` WHERE `file_name` = ? AND `description` = ? AND `image_checksum` = ? LIMIT 1;" ) ) ) {

      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
  }
  else {
      # Bind the variables into the prepared statement
      if ( !$stmt->bind_param( 'sss', $_SESSION['image_name'], $description, $_SESSION['image_checksum'] ) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
      }
      else {
          # Execute the SQL command
          if ( !$stmt->execute() ) {
              die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
          }
          else {
              # Bind results
              $stmt->bind_result( $image_id );

              # Fetch the value
              $stmt->fetch();

              # Cleanup statement
              $stmt->close();
          }
      }
  }

  # Insert entry into image_albums table
  # Prepare the MySQL insert statement on the server
  if ( !( $stmt = $db->prepare( 'INSERT INTO `piktur`.`album_images` ( `album_id`, `image_id` ) VALUES ( ?, ? )' ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
  }
  else {
      # Bind the variables into the prepared statement
      if ( !$stmt->bind_param( 'ii', $_SESSION['album_id'], $image_id ) ) {
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

  # Insert entries into tags table
  foreach ($tag_list as &$tag) {
    if ( !( $stmt = $db->prepare( 'INSERT INTO `piktur`.`tags` ( `image_id`, `tag_description` ) VALUES ( ?, ? )' ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    }
    else {
      # Bind the variables into the prepared statement
      if ( !$stmt->bind_param( 'is', $image_id, $tag ) ) {
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
  }

  # Cleanup unneeded session variables
  unset( $_SESSION['image_name'] );
  unset( $_SESSION['image_checksum'] );

  # Redirect back to album page
  header ( 'Location: '.$protocol.$_SERVER['SERVER_NAME'].'/imageview.php?album='.$_SESSION['album_id'] );
}

require 'header.php';
?>
      <table border="0" cellpadding="2" cellspacing="2" width="100%">
        <tbody>
<?php if ( $preview == 1 ) { ?>
          <tr>
            <td class="center_top">
              <img src="<?php echo $protocol . $_SERVER['SERVER_NAME'] . '/' . $new_file; ?>">
            </td>
          </tr>
    	  <form id="image_save_form" name="image_save_form" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data">
          <tr>
            <td class="center_top">
              <table border="0" cellpadding="2" cellspacing="2">
                <tr>
                  <td class="formlabel">Description:</td>
                  <td class="forminput">
                    <input size="18" name="description" id="description" type="text">
                  </td>
                </tr>
                <tr>
                  <td class="formlabel">Tags:</td>
                  <td class="forminput">
                    <input size="18" name="tags" id="tags" type="text">
                  </td>
                </tr>
                <tr>
                  <td class="formlabel">Public image:</td>
                  <td class="forminput">
                    <input type="checkbox" name="public" id="public" value="public">
                  </td>
                </tr>
        	<tr>
                  <td class="center_top">
                    <input type="submit" name="zsubmit" value="Save" >
                  </td>
  	        </tr>
              </table>
            </td>
	  </tr>
    	  </form>
<?php } elseif ( $preview == 0 ) { ?>
    	  <form id="image_upload_form" name="image_upload_form" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] ?>" method="post" enctype="multipart/form-data">
            <td class="center_top">
              <input type="file" name="upload" /><br><br>
              <input type="submit" name="xsubmit" value="Upload" /><br>
            </td>
          </tr>
    	  </form>
<?php } ?>
         </tbody>
      </table>
<?php require 'footer.php';?>
    <script type="text/javascript" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/js/livevalidation_image_upload.js"></script>
  </body>
</html>

<?php
  require_once '/etc/piktur/global.inc';

  # require user to be logged in
  if ( $_SESSION['authenticated'] != 'true' ) {
    header( 'Location: https://' . $_SERVER['SERVER_NAME'] . '/signin.php' );
  }  
  # prepare variables
  $msg = '';
  # Grab keyword
  $image_id = $_GET['image'];
  if ( !isset( $_POST['confirm_delete'] ) ) {
    $_SESSION['image_id'] = $image_id;
  } elseif ( $_POST['confirm_delete'] == 'Yes' ) {
      $confirm = TRUE;
    }
  # Review results
  if ( DEBUG ) {
    echo "IMAGE_ID: ".$_SESSION['image_id'].'<br>';
    echo "Session IMAGE_ID: ".$image_id.'<br>';
    echo "USER_ID: ".$_SESSION['user_id'].'<br>';
    echo "Confirm_Delete: ".$_POST['confirm_delete'].'<br>';
  }  
  #query database for image and check permissions
  if ( isset( $_SESSION['user_id'] ) and isset ( $_SESSION['image_id'] ) ) {
    #prepare query
    $sql = "SELECT CONCAT( `piktur`.`albums`.`path`, '/', `piktur`.`images`.`file_name` ) AS file, `piktur`.`images`.`description`, `piktur`.`images`.`image_checksum` FROM `piktur`.`images` ";
    $sql .= "JOIN ( `piktur`.`albums`, `piktur`.`album_images` ) ON ( `piktur`.`images`.`image_id` = `piktur`.`album_images`.`image_id` ";
    $sql .= "AND `piktur`.`album_images`.`album_id` = `piktur`.`albums`.`album_id` ) ";
    $sql .= "JOIN `piktur`.`permissions` ON `piktur`.`albums`.`album_id`=`piktur`.`permissions`.`album_id` "; 
    $sql .= "WHERE `piktur`.`images`.`image_id` = ? ";
    $sql .= "AND `piktur`.`albums`.`user_id` = ? ";
    $sql .= "AND `piktur`.`permissions`.`access_type` IN ( 'delete' )";
    
    if ( !( $stmt = $db->prepare( $sql ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    } else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'ii', $_SESSION['image_id'], $_SESSION['user_id'] ) ) {
          die( 'Binding parameters failed: (' . $tag_stmt->errno . ') ' . $tag_stmt->error );
        } else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
            die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            } else {
                # Bind results
                $stmt->store_result();
                $results = $stmt->num_rows;
                if ( $results != '1' ) {
                  $msg = 'You are unable to delete this image';
                }
                $stmt->bind_result( $file, $image_description, $image_checksum );
                
                # Fetch the values
                $stmt->fetch();
                
                if ( DEBUG ) {
                  echo "FILE: $file<br>";
                  echo "IMAGE_CHECKSUM: $image_checksum<br>";
                  echo "IMAGE_DESCRIPTION: $image_description<br>";
                  echo "RESULTS: $results<br>";
                  echo "PROTOCOL: $protocol<br>";
                }   
              }
          }
      }
      # delete image code
      if ( $confirm AND $results == '1') {
        # delete file
        if ( !is_dir( "$file" ) ) {
          unlink( $file );  
          $thumb_parts = pathinfo($file);
          unlink( $thumb_parts['dirname'].'/THUMB_'.$thumbparts['basename'] );  
        }
        # remove tags
        if ( !( $stmt = $db->prepare( "DELETE FROM `piktur`.`tags` WHERE `piktur`.`tags`.`image_id`=?;" ) ) ) {
          die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
        } else {
          if ( !$stmt->bind_param( 'i', $_SESSION['image_id'] ) ) {
            die( 'Binding parameters failed: (' . $tag_stmt->errno . ') ' . $tag_stmt->error );
          } else {
            if ( !$stmt->execute() ) {
              die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            } else {
              $stmt->close();
            }
          }
        }
        # update image table
        if ( !( $stmt = $db->prepare( "DELETE FROM `piktur`.`images` WHERE `piktur`.`images`.`image_id`=?;" ) ) ) {
          die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
        } else {
          if ( !$stmt->bind_param( 'i', $_SESSION['image_id'] ) ) {
            die( 'Binding parameters failed: (' . $tag_stmt->errno . ') ' . $tag_stmt->error );
          } else {
            if ( !$stmt->execute() ) {
              die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            } else {
              $stmt->close();
            }
          }
        }
        # update album_images table
        if ( !( $stmt = $db->prepare( "DELETE FROM `piktur`.`album_images` WHERE `piktur`.`album_images`.`image_id`=?;" ) ) ) {
          die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
        } else {
          if ( !$stmt->bind_param( 'i', $_SESSION['image_id'] ) ) {
            die( 'Binding parameters failed: (' . $tag_stmt->errno . ') ' . $tag_stmt->error );
          } else {
            if ( !$stmt->execute() ) {
              die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            } else {
              $stmt->close();
            }
          }
        }
        $msg = 'The image has been deleted';
      } #end of delete image code
  }
?>

<html>
  <?php require 'header.php'; ?>
  <body>   
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <?php if ( !$confirm AND $results == '1' ) { ?>
          <tr>            
            <td colspan="2" rowspan="1" class="notice"><?php echo $image_description; ?></td>
          </tr>
          <tr>
            <td colspan="3" rowspan="1" height="200" width="650" class="center_middle">
              <img alt="<?php echo $image_description ?>" src="<?php echo getDataURI( $file ) ?>" height="449" width="600"><br>
            </td>
          </tr>        
          <tr>
            <form id="confirm_delete_form" name="confirm_delete_form" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . '?image=' . $_SESSION['image_id'] ?>" method="post">
              <td class="formlabel">Confirm Delete:</td>
              <td class="forminput">
                <input size="18" name="confirm_delete" id="confirm_delete" type="checkbox" value="Yes">            
                <input type="image" src="<?php echo  $protocol . $_SERVER['SERVER_NAME'] ?>/img/deletebutton.png" border="0" alt="Confirm Delete Button">
              </td>            
            </form>
          </tr>
        <?php } elseif ( $msg ) { ?>
          <tr>
            <td class="notice"><?php echo $msg ?></td>
          </tr>          
        <?php } ?> 
      </tbody>
    </table>       
  </body>
  <?php require 'footer.php'; ?>  
</html>

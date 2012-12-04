<?php
require_once 'global.inc';

$image = filter_input( INPUT_GET, 'image', FILTER_VALIDATE_INT, array( array("min_range"=>0) ) );

# If there is a POST Value for image, then display that one
if ($image) {


  if ( !( $stmt = $db->prepare( "SELECT CONCAT( `albums`.`path`, '/', `images`.`file_name` ) AS file, `images`.`image_checksum`, `images`.`rating_cnt`, `images`.`rating_total`, `images`.`image_id` FROM `images` JOIN ( `albums`, `album_images` ) ON ( `images`.`image_id` = `album_images`.`image_id` AND `album_images`.`album_id` = `albums`.`album_id` ) WHERE `images`.`public` = '1' AND `images`.`image_id` = ?"  ) ) ) { 
    die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
  }
  # Bind the variables into the prepared statement
  if ( !$stmt->bind_param( 'i', $image ) ) {
    die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
  }

  if ( !$stmt->execute() ) {
    die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
  }

  # Bind results
  $stmt->bind_result( $file, $image_checksum , $rating_cnt, $rating_total, $image_id);
  
  # Fetch results
  $stmt->fetch();

  # Close the statement
  $stmt->close();
# Prepare the MySQL query statement to select publicly accessible images
} else {
  if ( !( $stmt = $db->prepare( "SELECT CONCAT( `albums`.`path`, '/', `images`.`file_name` ) AS file, `images`.`image_checksum`, `images`.`rating_cnt`, `images`.`rating_total`, `images`.`image_id` FROM `images` JOIN ( `albums`, `album_images` ) ON ( `images`.`image_id` = `album_images`.`image_id` AND `album_images`.`album_id` = `albums`.`album_id` ) WHERE `images`.`public` = '1' ORDER BY RAND() LIMIT 1" ) ) ) {
    die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
  } elseif ( !$stmt->execute() ) {
    die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
  } 
  # Bind results
  $stmt->bind_result( $file, $image_checksum , $rating_cnt, $rating_total, $image_id);
  
  # Fetch results
  $stmt->fetch();

  # Close the statement
  $stmt->close();
}
# Ensure image checksum is correct before allowing it to be displayed
if ( $image_checksum != hash_file( 'md5', '/var/www/html/'.$file ) ) {
    die( "Image failed checksum verification: $file" );
}

# Calculate the current rating
$ratings = $rating_total / $rating_cnt;

# Add new ratings
if ( isset( $_POST['rating_submit'] ) ) {
  $rating = filter_input( INPUT_POST, 'rating', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[0-9]{1}$/" ) ) );
  $rating_cnt++;
  $rating_total= $rating_total+$rating;
  if ( !( $stmt = $db->prepare( 'UPDATE `images` SET `rating_cnt`= ? ,`rating_total`= ? WHERE image_id= ?' ) ) ) {
    die( 'Prepare failed for rating with rating = '.$rating.' rating_cnt='.$rating_cnt.' rating_total='.$rating_total.': (' . $db->errno . ') ' . $db->error );
  } else {
    # Bind the variables into the prepared statement
    if ( !$stmt->bind_param( 'iii', $rating_cnt, $rating_total ,$image_id ) ) {
      die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
    } else {
      # Execute the SQL command
      if ( !$stmt->execute() ) {
        die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
      }
      # Cleanup statement
      $stmt->close();

      # Redirect back to album page
      header ( 'Location: '.$protocol.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'] );
    }
  }
}

require 'header.php'; ?>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tr>
      <td width="10%" class="left_top">
<?php 
      if ( !( $stmt = $db->prepare( "SELECT `images`.`image_id` FROM `images` JOIN ( `albums`, `album_images` ) ON ( `images`.`image_id` = `album_images`.`image_id` AND `album_images`.`album_id` = `albums`.`album_id` ) WHERE `images`.`public` = '1' AND `images`.`image_id`<? ORDER BY image_id desc LIMIT 1" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
      } else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'i',$image_id ) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        } elseif ( !$stmt->execute() ) {
          die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
        }

        # Bind results
        $stmt->bind_result( $prev_image);

        # Fetch results
        $stmt->fetch();

        # Close the statement
        $stmt->close();
      }
      if ($prev_image) { print '<a href="http://' . $_SERVER['SERVER_NAME'] . '/' . $_SERVER['PHP_SELF'] . '?image=' . $prev_image . '"><div>PREVIOUS</div></a>'; } 
?>
      </td>
        <td height="500" class="center_middle">
          <img alt="image" src="http://<?php echo $_SERVER['SERVER_NAME'] . '/' . $file ?>" height="auto" width="600"><br>
        </td>
      <td width="10%" class="right_top">
<?php 
      if ( !( $stmt = $db->prepare( "SELECT `images`.`image_id` FROM `images` JOIN ( `albums`, `album_images` ) ON ( `images`.`image_id` = `album_images`.`image_id` AND `album_images`.`album_id` = `albums`.`album_id` ) WHERE `images`.`public` = '1' AND `images`.`image_id`>? ORDER BY image_id asc LIMIT 1" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
      } else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'i',$image_id ) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        } elseif ( !$stmt->execute() ) {
          die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
        }

        # Bind results
        $stmt->bind_result( $next_image);

        # Fetch results
        $stmt->fetch();

        # Close the statement
        $stmt->close();
      }
      if ($next_image) { print '<a href="http://' . $_SERVER['SERVER_NAME'] . '/' . $_SERVER['PHP_SELF'] . '?image=' . $next_image . '"> <div>NEXT</div></a>'; } 
?>
    </td>
      </tr>
      <tr>
       <td width="10%" class="left_top"></td> 
        <form id="add_rating" name="add_rating" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] .'?image='.$image_id ?>" method="post" enctype="multipart/form-data">
          <td class="center_middle"><div>Current Rating = <?php if ( isset( $ratings ) ) { printf( "%5.2f", $ratings ); } else { echo '0'; } ?><br>
          0=Poor, 9=Great &nbsp<select name="rating"><option>0</option><option>1</option><option>2</option><option>3</option><option>4</option><option selected="selected">5</option><option>6</option><option>7</option><option>8</option><option>9</option>
          <input type="submit" name="rating_submit" value="RATE"><br></div></td>
        </form>
       <td width="10%" class="right_top"></td> 
</tr>
      <tr>
       <td width="10%" class="left_top"></td> 
        <td class="center_middle">
          <a href="http://<?php echo $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] ?>"><img alt="Random Image" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/randomimagebutton.png" border="0" height="50" width="256"></a>
        </td>
       <td width="10%" class="right_top"></td> 
      </tr>
    </table>
<?php require 'footer.php'; ?>
  </body>
</html>

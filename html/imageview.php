<?php
require_once 'global.inc';

# require user to be logged in
if ( $_SESSION['authenticated'] != 'true' ) {
  header( 'Location: https://' . $_SERVER['SERVER_NAME'] . '/signin.php' );
}

# Get album_ids user has access to
if ( isset( $_SESSION['user_id'] ) ) {
  # Prepare the MySQL select statement on the server
  if ( !( $stmt = $db->prepare( "SELECT `albums`.`album_id`, `albums`.`album_name` FROM `albums` JOIN `permissions` ON `albums`.`album_id`=`permissions`.`album_id` WHERE `permissions`.`user_id` = ? AND `permissions`.`access_type` IN ( 'view', 'edit', 'delete' );" ) ) ) {
    die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
  }
  else {
    # Bind the variables into the prepared statement
    $start = $page * $albums_per_page;
    if ( !$stmt->bind_param( 'i', $_SESSION['user_id'] ) ) {
      die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
    }
    else {
      # Execute the SQL command
      if ( !$stmt->execute() ) {
        die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
      }
      else {
        # Get total of records in the result set
        $stmt->store_result();
        $results = $stmt->num_rows;

        # Bind results
        $stmt->bind_result( $album_id, $album_name );

        # Fetch the values into arrays
        $i = 0;
        while ( $stmt->fetch() ) {
          # Review results
          if ( DEBUG ) {
            echo "USER_ID: ".$_SESSION['user_id'].'<br />';
            echo "ALBUM_ID[$i]: $album_id<br />";
            echo "ALBUM_NAME[$i]: $album_name<br />";
          }

          $album_ids[$i] = $album_id;
          $album_names[$i] = $album_name;
          $i++;
        }
        unset( $i );

        # Cleanup statement
        $stmt->close();
      }
    }
  }
}

# Grab album_id
$album_id = filter_input( INPUT_GET, 'album', FILTER_VALIDATE_INT, array( array("min_range"=>0) ) );
$_SESSION['album_id'] = $album_id;

# If user does not have access to requested album, die
if ( ! in_array( $album_id, $album_ids, TRUE ) ) { header ( 'Location: '.$protocol.$_SERVER['SERVER_NAME'].'/albumview.php' ); }

# Grab album offset
$offset = filter_input( INPUT_GET, 'offset', FILTER_VALIDATE_INT, array( array("min_range"=>0) ) );
if ( !isset( $offset ) ) { $offset = 0; };

# Prepare the MySQL query statement to select album images
$sql = "SELECT `images`.`image_id`, CONCAT( `albums`.`path`, '/', `images`.`file_name` ) AS file, `images`.`description`, `images`.`image_checksum` FROM `images` ";
$sql .= "JOIN `album_images` ON `album_images`.`image_id` = `images`.`image_id` ";
$sql .= "JOIN `albums` ON `albums`.`album_id` = `album_images`.`album_id` WHERE 1";
#$sql .= " AND `images`.`public` = 'public'";
if ( $_SESSION['authenticated'] == 'true' ) { $sql .= " AND `albums`.`album_id` = $album_id;"; }

if ( DEBUG ) {
  echo "<br />ALBUM_ID: $album_id<br />";
  echo "SQL: $sql<br />";
}

if ( !( $stmt = $db->prepare( $sql ) ) ) {
  die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
}
elseif ( !$stmt->execute() ) {
  die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
}

# Bind results
$stmt->bind_result( $image_id, $file, $image_description, $image_checksum );

# Get total of records in the result set
$stmt->store_result();
$results = $stmt->num_rows;
$prev = max( ($offset - 1), 0 );
$next = min( ($offset + 1), ( $results - 1 ) );
if ( DEBUG ) {
  echo "<br />IMAGES_FOUND: ".$results.'<br />';
  echo "OFFSET: $offset<br />";
  echo "PREVIOUS: $prev<br />";
  echo "NEXT: $next<br />";
}


# Fetch the values into arrays
$i = 0;
while ( $stmt->fetch() ) {
  # If suplied offset equals current offset
  if ( $offset == $i ) {
    # Add new tags
    if ( isset($_POST['tag_submit'] ) ) {
      $tags = filter_input( INPUT_POST, 'tags', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_, ]{1,255}$/" ) ) );
      $tag_list = preg_split( "/,/", $tags );

      # Insert entries into tags table
      foreach ($tag_list as &$tag) {
        if ( !( $stmt = $db->prepare( 'INSERT INTO `piktur`.`tags` ( `image_id`, `tag_description` ) VALUES ( ?, ? )' ) ) ) {
          die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
        }
        else {
          # Bind the variables into the prepared statement
          if ( !$stmt->bind_param( 'is', $image_id, $tag ) ) {
            die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error ); }
          else {
            # Execute the SQL command
            if ( !$stmt->execute() ) {
              die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            }
            # Cleanup statement
            $stmt->close();

            # Redirect back to album page
            header ( 'Location: '.$protocol.$_SERVER['SERVER_NAME'].'/imageview.php?album='.$_SESSION['album_id'].'&offset='.$offset );
          }
        }
      }
    }
  }

  # Prepare the MySQL select statement on the server
  if ( !( $tag_stmt = $db->prepare( "SELECT `tags`.`tag_description` FROM `tags` WHERE `image_id` = ?" ) ) ) {
    die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
  }
  else {
    # Bind the variables into the prepared statement
    if ( !$tag_stmt->bind_param( 'i', $image_id ) ) {
      die( 'Binding parameters failed: (' . $tag_stmt->errno . ') ' . $tag_stmt->error );
    }
    else {
      # Execute the SQL command
      if ( !$tag_stmt->execute() ) {
        die( 'Execute failed: (' . $tag_stmt->errno . ') ' . $tag_stmt->error );
      }
      else {
        # Bind results
        $tag_stmt->bind_result( $tag );

        # Fetch the values into array
        $j=0;
        $tag_list = array();
        while ( $tag_stmt->fetch() ) {
          $tag_list[$j] = $tag;
          $j++;
        }
        unset( $j );

        # Cleanup statement
        $tag_stmt->close();
      }
    }
  }

  # Review results
  if ( DEBUG ) {
    echo "IMAGE_ID[$i]: ".$image_id.'<br>';
    echo "FILE[$i]: $file<br>";
    echo "IMAGE_CHECKSUM[$i]: $image_checksum<br>";
    echo "IMAGE_DESCRIPTION[$i]: $image_description<br>";
  }
  $ids[$i] = $image_id;
  $files[$i] = $file;
  $checksums[$i] = $image_checksum;
  $descriptions[$i] = $image_description;
  $tags[$i] = implode( ', ', $tag_list );
  $i++;
}
unset( $i );

require 'header.php';
?>
    <table border="1" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td valign="top"><br></td>
          <td colspan="3" rowspan="1" class="notice">
            <form action="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'] ?>" method="GET">
              Album: <select name="album" id="album" onchange="this.form.submit();">
<?php for ( $i=0; $i < sizeof( $album_ids ); $i++ ) { ?>
                <option <?php if ( $album_ids[$i] == $album_id ) { echo 'selected="selected"'; } ?>value="<?php echo $album_ids[$i]; ?>"><?php echo $album_names[$i]; ?></option>
<?php } ?>
              </select>
            </form>
          </td>
          <td><br /></td>
        </tr>
        <tr>
          <td><br /></td>
          <td colspan="3" rowspan="1" class="notice"><?php echo $descriptions[$offset]; ?></td>
          <td><br /></td>
        </tr>
        <tr>
          <td align="right" valign="top" width="10%">
<?php if ( $offset > 0 ) { ?>
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$prev ?>">
              <img src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/'.$files[$prev] ?>" alt="Previous Image" height="75" width="100"><br>
              <div class="copyight">Previous in Album</div>
            </a>
<?php } ?>
          </td>
          <td colspan="3" rowspan="1" align="center" height="200" valign="top" width="650">
            <img alt="<?php echo $descriptions[$offset] ?>" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/'.$files[$offset] ?>" height="449" width="600"><br>
          </td>
          <td align="left" valign="top" width="10%">
<?php if ( $offset < sizeof( $ids ) - 1 ) { ?>
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$next ?>">
              <img src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/'.$files[$next] ?>" alt="Next Image" height="75" width="100"><br>
              <div class="copyight">Next in Album</div>
            </a>
<?php } ?>
          </td>
        </tr>
        <tr>
          <td align="right" valign="top">
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/image_upload.php' ?>"><img src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/newbutton.png' ?>" height="40" width="87" alt="newimage"></a><br />
          </td>
          <td colspan="3" rowspan="1" class="tag_list"><?php echo $tags[$offset]; ?></td>
          <td valign="top">
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/delete_image.php?image='.$ids[$offset] ?>"><img alt="delete" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/deletebutton.png' ?>" height="40" width="115"></a><br />
          </td>
        </tr>
        <tr>
          <td colspan="5" rowspan="1" valign="top"><hr size="3" width="100%"></td>
        </tr>
        <tr>
          <form id="add_tags" name="add_tags" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$offset ?>" method="post" enctype="multipart/form-data">
            <td class="center_middle"></td>
            <td class="right_middle"><div>NEW TAGS: </div></td>
            <td class="center_middle"><input name="tags" type="text"><br /></td>
            <td class="left_middle"><input type="submit" name="tag_submit" value="Add"><br /></td>
            <td class="center_middle"></td>
          </form>
        </tr>
        <tr>
          <td colspan="5" rowspan="1" valign="top">
            <hr size="3" width="100%"></td>
        </tr>
        <tr>
          <td valign="top"><br>
          </td>
          <td align="right" valign="middle" width="20%">
<?php if ( $offset > 0 ) { ?>
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$prev ?>"><img alt="next" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/prevbutton.png' ?>" height="40" width="120"></a>
<?php } ?>
          </td>
          <td width="10%" class="center_middle">
            <div>Image <?php echo ( $offset + 1 ); ?> of <?php echo $results; ?></div>
          </td>
          <td width="20%" class="center_middle">
<?php if ( $offset < sizeof( $ids ) - 1 ) { ?>
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$next ?>"><img alt="next" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/nextbutton.png' ?>" height="40" width="120"></a>
<?php } ?>
          </td>
          <td valign="top"><br>
          </td>
        </tr>
        <tr>
          <td colspan="5" rowspan="1" valign="top"><hr size="3" width="100%"></td>
        </tr>
      </tbody>
    </table>
  </body>
</html>


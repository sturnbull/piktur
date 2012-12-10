<?php
require_once '/etc/piktur/global.inc';

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

# Prepare the MySQL query statement to select album images - PRH added images.filename to be able to display just image name
$sql = "SELECT `images`.`image_id`, CONCAT( `albums`.`path`, '/', `images`.`file_name` ) AS file, `images`.`file_name`, `images`.`description`, `images`.`image_checksum` ,`images`.`rating_cnt`,`images`.`rating_total`, `images`.`public` FROM `images` ";
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

# Bind results - PRH added image_name to be able to display just image name
$stmt->bind_result( $image_id, $file, $image_name, $image_description, $image_checksum , $rating_cnt, $rating_total , $public );

# Get total of records in the result set
$stmt->store_result();
$results = $stmt->num_rows;
if ( $offset >= $results ) { $offset = 0; };
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
    if ( isset( $_POST['tag_submit'] ) ) {
      $tags = filter_input( INPUT_POST, 'tags', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-zA-Z0-9_, ]{1,255}$/" ) ) );
      $tag_list = preg_split( "/,/", $tags );

      if ( sizeof( $tag_list ) > 0 ) {
        # Insert entries into tags table
        foreach ( $tag_list as &$tag ) {
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
              header ( 'Location: '.$protocol.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$_SESSION['album_id'].'&offset='.$offset );
            }
          }
        }
      }
    }

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
          header ( 'Location: '.$protocol.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$_SESSION['album_id'].'&offset='.$offset );
        }
      }
    }

    # Change Public setting 
    if ( isset( $_POST['public_submit'] ) ) {

      $public = filter_input( INPUT_POST, 'public', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[01]{1}$/" ) ) );
      if ( !( $stmt = $db->prepare( 'UPDATE `images` SET `public`= ? WHERE image_id= ?' ) ) ) {
        die( 'Prepare failed for public : (' . $db->errno . ') ' . $db->error );
      } else {
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'ii', $public, $image_id ) ) {
          die( 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error );
        } else {
          # Execute the SQL command
          if ( !$stmt->execute() ) {
            die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
          }
          # Cleanup statement
          $stmt->close();
                            
          # Redirect back to album page
          header ( 'Location: '.$protocol.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$_SESSION['album_id'].'&offset='.$offset );
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
    echo "PUBLIC: $public<br>";
  }
  $ids[$i] = $image_id;
  $files[$i] = $file;
  $file_name[$i] = $image_name;
  $checksums[$i] = $image_checksum;
  $descriptions[$i] = $image_description;
  $publics[$i] = $public;
  $tags[$i] = implode( ', ', $tag_list );
  $ratings[$i] = $rating_total / $rating_cnt;
  $i++;
}

unset( $i );

require 'header.php';
?>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td class="center_top"><br></td>
          <td colspan="3" rowspan="1" class="notice">
            <form action="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'] ?>" method="GET">
              Album: <select name="album" id="album" onchange="this.form.submit();">
<?php for ( $i=0; $i < sizeof( $album_ids ); $i++ ) { ?>
                <option <?php if ( $album_ids[$i] == $album_id ) { echo 'selected="selected"'; } ?>value="<?php echo $album_ids[$i]; ?>"><?php echo $album_names[$i]; ?></option>
<?php } ?>
              </select>
            </form>
          </td>
          <td class="center_top"><br></td>
        </tr>
        <tr>
          <td class="center_top"><br></td>
          <td class="title_right">Image Name: <br>Image Description: </td>
          <td colspan="2" rowspan="1" class="left_top"><div>
            <?php echo $file_name[$offset] ?><br>
            <?php echo $descriptions[$offset] ?></div></td>
          <td class="center_top"><br></td>
        </tr>
        <tr>
          <td width="10%" class="title_right">
<?php if ( $offset > 0 ) { ?>
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$prev ?>">
              Previous in Album<br>
              <img src="<?php echo getDataURI(  preg_replace( "/\/var\/www\/pikturs\//", "/var/www/THUMB_pikturs/",$files[$prev] ) )?>" alt="Previous Image">
            </a>
<?php } ?>
          </td>
          <td colspan="3" rowspan="1" height="200" width="650" class="center_middle">
<?php if ( $results > 0 ) { ?>
            <img alt="<?php echo $descriptions[$offset] ?>" src="<?php echo getDataURI( $files[$offset] ) ?>" height="auto" width="600"><br>
<?php }
else {?>
            <div>You have not yet uploaded any pictures.<br />Please click <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/image_upload.php' ?>">here</a> or the [New] button below.</div>
<?php } ?>
          </td>
          <td width="10%" class="title_left">
<?php if ( $offset < sizeof( $ids ) - 1 ) { ?>
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$next ?>">
              Next in Album<br>
              <img src="<?php echo getDataURI(  preg_replace( "/\/var\/www\/pikturs\//", "/var/www/THUMB_pikturs/",$files[$next] ) ) ?>" alt="Next Image">
            </a>
<?php } ?>
          </td>
        </tr>
        <tr>
          <td class="right_top">
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/image_upload.php' ?>"><img src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/newbutton.png' ?>" height="40" width="87" alt="newimage"></a><br />
          </td>
          <td colspan="3" rowspan="1" class="tag_list"><?php echo $tags[$offset]; ?></td>
          <td valign="left_top">
<?php if ( $results > 0 ) { ?>
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/delete_image.php?image='.$ids[$offset] ?>"><img alt="delete" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/deletebutton.png' ?>" height="40" width="115"></a><br />
<?php } ?>
          </td>
        </tr>
        <tr>
          <td colspan="5" rowspan="1" class="center_top"><hr size="3" width="100%"></td>
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
          <form id="add_rating" name="add_rating" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$offset ?>" method="post" enctype="multipart/form-data">
          <td class="center_middle"></td>
          <td class="right_middle"><div>Current Rating = <?php if ( isset( $ratings[$offset] ) ) { printf( "%5.2f", $ratings[$offset] ); } else { echo '0'; } ?></div></td>
          <td class="center_middle"><div>0=Poor, 9=Great</div><select name="rating"><option>0</option><option>1</option><option>2</option><option>3</option><option>4</option><option selected="selected">5</option><option>6</option><option>7</option><option>8</option><option>9</option><br /></td>
          <td class="left_middle"><input type="submit" name="rating_submit" value="Rate"><br /></td>
          <td class="center_middle"></td>
          </form>
        </tr>


        <tr>
          <form id="set_public" name="set_public" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$offset ?>" method="post" enctype="multipart/form-data">
          <td class="center_middle"></td>
          <td class="right_middle"></td>
          <td class="center_middle"><div>Visibility: </div><select name="public"><option value="0"<?php if ($publics[$offset] == 1) { print ">Private</option><option value=\"1\" selected=\"selected\">Public</option>"; }else{ print " selected=\"selected\">Private</option><option value=\"1\" >Public</option>";} ?><br /></td>
          <td class="left_middle"><input type="submit" name="public_submit" value="Set"><br /></td>
          <td class="center_middle"></td>
          </form>
        </tr>


        <tr>
          <td colspan="5" rowspan="1" class="center_top"><hr size="3" width="100%"></td>
        </tr>
        <tr>
          <td class="center_top"><br /></td>
          <td width="20%" class="middle_right">
<?php if ( $offset > 0 ) { ?>
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$prev ?>"><img alt="next" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/prevbutton.png' ?>" height="40" width="120"></a>
<?php } ?>
          </td>
          <td width="10%" class="center_middle">
<?php if ( $results > 0 ) { ?>
            <div>Image <?php echo ( $offset + 1 ); ?> of <?php echo $results; ?></div>
<?php } ?>
          </td>
          <td width="20%" class="center_middle">
<?php if ( $offset < sizeof( $ids ) - 1 ) { ?>
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?album='.$album_id.'&offset='.$next ?>"><img alt="next" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/nextbutton.png' ?>" height="40" width="120"></a>
<?php } ?>
          </td>
          <td class="center_top"><br /></td>
        </tr>
        <tr>
          <td colspan="5" rowspan="1" class="center_top"><hr size="3" width="100%"></td>
        </tr>
      </tbody>
    </table>
  </body>
</html>

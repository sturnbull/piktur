<?php
require_once 'global.inc';
if ( !isset ( $_SESSION['album_id'] ) ) {
    $album_id = filter_input( INPUT_GET, 'album', FILTER_VALIDATE_INT, array( array("min_range"=>0) ) );
    if ( isset( $album_id ) ) { $_SESSION['album_id'] = $album_id; };
}
else {
    $album_id = $_SESSION['album_id'];
}
$offset = filter_input( INPUT_GET, 'offset', FILTER_VALIDATE_INT, array( array("min_range"=>0) ) );
if ( !isset( $offset ) ) { $offset = 0; };
$i = 0;

# Prepare the MySQL query statement to select publicly accessible images
$sql = "SELECT `images`.`image_id`, CONCAT( `albums`.`path`, '/', `images`.`file_name` ) AS file, `images`.`description`, `images`.`image_checksum` FROM `images` ";
$sql .= "JOIN `album_images` ON `album_images`.`image_id` = `images`.`image_id` ";
$sql .= "JOIN `albums` ON `albums`.`album_id` = `album_images`.`album_id` WHERE 1";
#$sql .= " AND `images`.`public` = 'public'";
if ( $_SESSION['authenticated'] == 'true' ) { $sql .= " AND `albums`.`album_id` = $album_id;"; }

if ( DEBUG ) {
  echo "SQL: ".$sql.'<br>';
}

if ( !( $stmt = $db->prepare($sql) ) ) {
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
  echo "RESULTS: ".$results.'<br>';
  echo "OFFSET: $offset<br>";
  echo "PREVIOUS: $prev<br>";
  echo "NEXT: $next<br>";
}

# Fetch the values into arrays
while ( $stmt->fetch() ) {
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
  $tags[$i] = implode( ', ', $tag_list );
  $checksums[$i] = $image_checksum;
  $descriptions[$i] = $image_description;
  $i++;
}

require 'header.php';
?>
    <table border="1" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr >
          <td colspan="5"></td>
        </tr>
        <tr>
          <td valign="top"><br></td>
          <td colspan="3" rowspan="1" class="notice"><?php echo basename( dirname( $file )  ); ?></td>
          <td valign="top"><br></td>
        </tr>
        <tr>
          <td valign="top"><br></td>
          <td colspan="3" rowspan="1" class="notice"><?php echo basename( $file ); ?></td>
          <td valign="top"><br></td>
        </tr>
        <tr>
          <td align="right" valign="top" width="10%">
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?offset='.$prev ?>">
              <img src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/'.$files[$prev] ?>" alt="Previous Image" height="75" width="100"><br>
              <div class="copyight">Previous in Album</div>
            </a>
          </td>
          <td colspan="3" rowspan="1" align="center" height="200" valign="top" width="650">
            <img alt="<?php echo $descriptions[$offset] ?>" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/'.$files[$offset] ?>" height="449" width="600"><br>
          </td>
          <td align="left" valign="top" width="10%">
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?offset='.$next ?>">
              <img src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/'.$files[$next] ?>" alt="Next Image" height="75" width="100"><br>
              <div class="copyight">Next in Album</div>
            </a>
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
          <td colspan="5" rowspan="1" valign="top">
            <hr size="3" width="100%"></td>
        </tr>
        <tr>
          <td valign="top"><br>
          </td>
          <td align="right" valign="top">
<!---- ---- ---- ---- ---- ---- ---- ---- ---- ---- ---- ---- ---- ---->
            <!-- New tag - add new tag -->
<!---- ---- ---- ---- ---- ---- ---- ---- ---- ---- ---- ---- ---- ---->
<font
              color="#ffffff" face="Helvetica, Arial, sans-serif">NEW
              TAG: </font></td>
          <td colspan="2" rowspan="1" valign="top"> <input
              name="newtag" type="text"><br>
          </td>
          <td valign="top"> <br>
          </td>
        </tr>
        <tr>
          <td colspan="5" rowspan="1" valign="top">
            <hr size="3" width="100%"></td>
        </tr>
        <tr>
          <td valign="top"><br>
          </td>
          <td align="right" valign="middle" width="20%">
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?offset='.$prev ?>"><img alt="next" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/prevbutton.png' ?>" height="40" width="120"></a>
          </td>
          <td align="center" valign="middle" width="10%"> <font
              face="Helvetica, Arial, sans-serif"><b><big><font
                    color="#ffffff">Image <?php echo ( $offset + 1 ); ?> of <?php echo $results; ?></font></big></b></font><br>
          </td>
          <td valign="middle" width="20%">
            <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?offset='.$next ?>"><img alt="next" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/nextbutton.png' ?>" height="40" width="120"></a>
          </td>
          <td valign="top"><br>
          </td>
        </tr>
        <tr>
          <td colspan="5" rowspan="1" valign="top">
            <hr size="3" width="100%"></td>
        </tr>
      </tbody>
    </table>
  </body>
</html>


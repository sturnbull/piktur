<?php
require_once 'global.inc';

# Define page layout
$albums_per_page = 12;
$albums_per_row = 3;
$i = 0;
$results = 0;
$page = filter_input( INPUT_GET, 'page', FILTER_VALIDATE_INT, array( array("min_range"=>0) ) );
if ( !isset ( $page ) ) { $page = 0; };
$ids = array(); 
$names = array();
$descriptions = array();
$album_id = '';
$album_name = '';
$album_description = '';

# Only search if valid user_id variable exists
if ( isset( $_SESSION['user_id'] ) ) {
    # Prepare the MySQL select statement on the server
    if ( !( $stmt = $db->prepare( "SELECT `albums`.`album_id`, `albums`.`album_name`, `albums`.`album_description` FROM `albums` JOIN `permissions` ON `albums`.`album_id`=`permissions`.`album_id` WHERE `permissions`.`user_id` = ? AND `permissions`.`access_type` IN ( 'view', 'edit', 'delete' ) ORDER BY `album_name` ASC LIMIT ?, ?;" ) ) ) {
        die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    }
    else {
        # Bind the variables into the prepared statement
	$start = $page * $albums_per_page;
        if ( !$stmt->bind_param( 'iii', $_SESSION['user_id'], $start, $albums_per_page ) ) {
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
                $stmt->bind_result( $album_id, $album_name, $album_description );

                # Fetch the values into arrays
                while ( $stmt->fetch() ) {
                    # Review results
                    if ( DEBUG ) {
                        echo "USER_ID[$i]: ".$_SESSION['user_id'].'<br>';
                        echo "ALBUM_ID[$i]: $album_id<br>";
                        echo "ALBUM_NAME[$i]: $album_name<br>";
                        echo "ALBUM_DESCRIPTION[$i]: $album_description<br>";
                    }

                    $ids[$i] = $album_id;
                    $names[$i] = $album_name;
                    $descriptions[$i] = $album_description;
                    $i++;
		}

                # Cleanup statement
                $stmt->close();
            }
        }
    }
}
require 'header.php';
?>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tr>
        <td colspan="3" rowspan="1" align="center" height="200" valign="top" width="100%">
          <table border="0" cellpadding="2" cellspacing="10" width="100%">
            <tr>
<?php for ( $i = 0; $i < $results; $i++ ) { ?>
              <td  class="notice"><img src="pikturs/DSCN0058.JPG" alt="" height="75" width="100"><br><?php echo $names[$i] ?></td>
<?php if ( ( $i / $albums_per_row ) == 1 ) { ?>
            <tr>
            </tr>
<?php }
} ?>
            </tr>
          </table>
        </td>
      </tr>
      <tr><td><hr size="3" width="100%"></td></tr>
      <tr>
        <td class="center_middle">
          <a href="http://<?php echo $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."?page=".max( ($page - 1), 0 ) ?>"><img alt="Previous Page" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/prevbutton.png"></a>
          <a href="http://<?php echo $_SERVER['SERVER_NAME'].'/image_upload.php' ?>"><img alt="New Albumn" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/newbutton.png"></a>
          <a href="http://<?php echo $_SERVER['SERVER_NAME'].'/delete_album.php' ?>"><img alt="Delete Album" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/deletebutton.png"></a>
          <a href="http://<?php echo $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."?page=".($page + 1) ?>"><img alt="Next Page" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/nextbutton.png"></a>
          <div>Page # of #</div>
        </td>
      </tr>
      <tr><td><hr size="3" width="100%"></td></tr>
    </table>
  </body>
</html>

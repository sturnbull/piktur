<?php
require_once 'global.inc';

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
    <table border="1" cellpadding="2" cellspacing="2" width="100%">
      <tr>
        <td colspan="3" rowspan="1" align="center" height="200" valign="top" width="100%">
          <table border="0" cellpadding="2" cellspacing="10" width="100%">
            <tr>
<?php $i=0; # set the album number
    for ( $j = 0; $j < $rows_per_page; $j++ ) { #display each row of albums
      for ( $k = 0; $k < $albums_per_row; $k++ ) { # display 1 album per column
	  if ( $i < $results) {
	  # display user album ?>
	  <td  class="notice"><a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/imageview.php?album='.$ids[$i] ?>"><img src="pikturs/DSCN0058.JPG" alt="" height="75" width="100"><br><?php echo $names[$i] ?></a></td>
	  <?php
	  $i++; # increment album number
	  } else { 
	  # display empty album ?>
	  <td  class="notice"><a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/new_album.php'?>"><img src="img/emptyfolder.png" alt="" height="75" width="100"><br>Create New</a></td>
	  <?php	  
	  } # end if else
      } # end of a row - close the row
      ?>
      </tr>
      <tr>
      <?php } # done generating table  ?>
            </tr>
          </table>
        </td>
      </tr>
      <tr><td colspan="3"><hr size="3" width="100%"></td></tr>
      <tr>
        <td class="center_middle" width = "30%">
        <?php if ($page > 0) { ?>	
          <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."?page=".max( ($page - 1), 0 ) ?>"><img alt="Previous Page" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/img/prevbutton.png"></a>
	<?php } ?>
        </td>
        <td class="center_middle">
           <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/new_album.php' ?>"><img alt="New Albumn" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/img/newbutton.png"></a>
           <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/delete_album.php' ?>"><img alt="Delete Album" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/img/deletebutton.png"></a>
        </td>
        <td class="center_middle" width = "30%">
        <?php if ($results > $albums_per_page) { ?>
        <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."?page=".($page + 1) ?>"><img alt="Next Page" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/img/nextbutton.png"></a>
	<?php } ?>
	</td>
      </tr>
      <tr><td colspan="3"><hr size="3" width="100%"></td></tr>
    </table>
  </body>
</html>

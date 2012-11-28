<?php
  require_once 'global.inc';

  # require user to be logged in
  if ( $_SESSION['authenticated'] != 'true' ) {
    header( 'Location: https://' . $_SERVER['SERVER_NAME'] . '/signin.php' );
  }
  
  # prepare variables
  $msg = '';
  
  # Grab keyword
  $keyword = filter_input( INPUT_POST, 'keyword', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_]{1,64}$/" ) ) );
  if ( $keyword  AND  $keyword != $_SESSION['key']  ) {
    $_SESSION['key'] = "%".$keyword."%";
  }
  
  # Grab viewing offset
  $offset = filter_input( INPUT_GET, 'offset', FILTER_VALIDATE_INT, array( array("min_range"=>0) ) );
  if ( !isset( $offset ) ) { $offset = 0; };
  
  # Grab the images
  if ( isset( $_SESSION['user_id'] ) and isset ( $_SESSION['key'] ) ) {  
    # Prepare the MySQL query statement to select publicly accessible images
    # it works but it is ugly
    $sql = "SELECT DISTINCT `piktur`.`images`.`image_id`, CONCAT( `piktur`.`albums`.`path`, '/', `piktur`.`images`.`file_name` ) AS file, `piktur`.`images`.`description`, `piktur`.`images`.`image_checksum` FROM `piktur`.`images` ";
    $sql .= "JOIN ( `piktur`.`albums`, `piktur`.`album_images` ) ON ( `piktur`.`images`.`image_id` = `piktur`.`album_images`.`image_id` ";
    $sql .= "AND `piktur`.`album_images`.`album_id` = `piktur`.`albums`.`album_id` ) ";
    $sql .= "JOIN `piktur`.`permissions` ON `piktur`.`albums`.`album_id`=`piktur`.`permissions`.`album_id` "; 
    $sql .= "JOIN `piktur`.`tags` ON ( `piktur`.`images`.`image_id` = `piktur`.`tags`.`image_id`)";
    $sql .= "WHERE ( `piktur`.`images`.`public` = '1' ";     
    $sql .= "OR `piktur`.`albums`.`album_id` IN ( ";
    $sql .= "SELECT `piktur`.`albums`.`album_id` FROM `piktur`.`albums` ";
    $sql .= "JOIN `piktur`.`permissions` ON `piktur`.`albums`.`album_id`=`piktur`.`permissions`.`album_id` ";
    $sql .= "WHERE `piktur`.`permissions`.`user_id` = ? ";
    $sql .= "AND `piktur`.`permissions`.`access_type` IN ( 'view', 'edit', 'delete' ) ) )";
    $sql .= "AND `piktur`.`tags`.`tag_description` LIKE ?";
     
    if ( !( $stmt = $db->prepare( $sql ) ) ) {
      die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
    } else {
        # Execute the SQL command
        # Bind the variables into the prepared statement
        if ( !$stmt->bind_param( 'is',  $_SESSION['user_id'], $_SESSION['key'] ) ) {
          die( 'Binding parameters failed: (' . $tag_stmt->errno . ') ' . $tag_stmt->error );
        } else {  
            if ( !$stmt->execute() ) {
            die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
            } else {
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
              # Bind results
              $stmt->bind_result( $image_id, $file, $image_description, $image_checksum );

              # Fetch the values into arrays
              $i = 0;
              while ( $stmt->fetch() ) {
                # Prepare the MySQL select statement on the server
                if ( !( $tag_stmt = $db->prepare( "SELECT `piktur`.`tags`.`tag_description` FROM `piktur`.`tags` WHERE `piktur`.`tags`.`image_id` = ?" ) ) ) {
                  die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
                } else {
                  # Bind the variables into the prepared statement
                  if ( !$tag_stmt->bind_param( 'i', $image_id ) ) {
                    die( 'Binding parameters failed: (' . $tag_stmt->errno . ') ' . $tag_stmt->error );
                  } else {
                      # Execute the SQL command
                      if ( !$tag_stmt->execute() ) {
                        die( 'Execute failed: (' . $tag_stmt->errno . ') ' . $tag_stmt->error );
                      } else {
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

                $ids[$i] = $image_id;
                $files[$i] = $file;
                $checksums[$i] = $image_checksum;
                $descriptions[$i] = $image_description;
                $tags[$i] = implode( ', ', $tag_list );
                $i++;
              
                # Review results
                if ( DEBUG ) {
                  echo "IMAGE_ID[$i]: ".$image_id.'<br>';
                  echo "FILE[$i]: $file<br>";
                  echo "IMAGE_CHECKSUM[$i]: $image_checksum<br>";
                  echo "IMAGE_DESCRIPTION[$i]: $image_description<br>";
                  echo "TAGS[$i]: $tags[$i]<br>";
                }
}          
}
          unset( $i );
      } #bind worked
    } #valid session and a keyword submitted
  } 
  
  if ( DEBUG ) {
    echo "START: ".$start.'<br />';
    echo "PAGE: ".$page.'<br />';
    echo "Per Page: ".$albums_per_page.'<br />';
    echo "Submitted Keyword: ".$keyword.'<br />';    
    echo "Stored Key: ".$_SESSION['key'].'<br />';
    echo "USER ID: ".$_SESSION['user_id'].'<br />';
  }
?>
<html>
  <header>
    <?php require 'header.php'; ?>
  </header
  <body>
    <form id="search_form" name="search_form" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] ?>" method="post">
      <table border="0" cellpadding="2" cellspacing="2" width="100%">
        <tbody>
          <tr>
            <td class="formlabel" width="50%">Keyword:</td>
            <td class="forminput" width="50%">
              <input size="18" name="keyword" id="keyword" type="text"<?php if ( $name ) echo " value=\"$keyword\""; ?>>
              <input type="image" src="<?php echo  $protocol . $_SERVER['SERVER_NAME'] ?>/img/search.png" border="0" alt="Search" height="24" width="24">
            </td>
          </tr>
          <tr>
            <td class="notice"><?php echo $msg ?></td>
          </tr>
        </tbody>
      </table>
    </form>
    <table border="1" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td class="center_top"><br></td>
          <td colspan="3" rowspan="1" class="notice"><?php echo $descriptions[$offset]; ?></td>
          <td class="center_top"><br></td>
        </tr>
        <tr>
          <td width="10%" class="right_top">
            <?php if ( $offset > 0 ) { ?>
              <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?offset='.$prev ?>">
                <img src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/'.$files[$prev] ?>" alt="Previous Image" height="75" width="100"><br>
                <div class="copyight">Previous Image</div>
              </a>
             <?php } ?>
          </td>
          <td colspan="3" rowspan="1" height="200" width="650" class="center_middle">
            <?php if ( $results > 0 ) { ?>
              <img alt="<?php echo $descriptions[$offset] ?>" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/'.$files[$offset] ?>" height="449" width="600"><br>
            <?php } else {?>
              <div>No images were returned by this search.</div>
            <?php } ?>
          </td>
          <td width="10%" class="left_top">
            <?php if ( $offset < sizeof( $ids ) - 1 ) { ?>
              <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?offset='.$next ?>">
                <img src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/'.$files[$next] ?>" alt="Next Image" height="75" width="100"><br>
                <div class="copyight">Next Image</div>
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
          <td colspan="5" rowspan="1" class="center_top"><hr size="3" width="100%"></td>
        </tr>
        <tr>
          <td class="center_top"><br /></td>
          <td width="20%" class="middle_right">
            <?php if ( $offset > 0 ) { ?>
              <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?offset='.$prev ?>"><img alt="next" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/prevbutton.png' ?>" height="40" width="120"></a>
            <?php } ?>
          </td>
          <td width="10%" class="center_middle">
            <?php if ( $results > 0 ) { ?>
              <div>Image <?php echo ( $offset + 1 ); ?> of <?php echo $results; ?></div>
            <?php } ?>
          </td>
          <td width="20%" class="center_middle">
            <?php if ( $offset < sizeof( $ids ) - 1 ) { ?>
              <a href="<?php echo $protocol . $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?offset='.$next ?>"><img alt="next" src="<?php echo $protocol . $_SERVER['SERVER_NAME'].'/img/nextbutton.png' ?>" height="40" width="120"></a>
            <?php } ?>
          </td>
          <td class="center_top"><br /></td>
        </tr>
        <tr>
          <td colspan="5" rowspan="1" class="center_top"><hr size="3" width="100%"></td>
        </tr>      
      </tbody>
    </table>
    <table border="1" cellpadding="2" cellspacing="2" width="100%">
      <tbody>
        <tr>
          <td class="notice"><?php echo $msg ?></td>
        </tr>
      </tbody>
    </table>
  </body>
  <footer>
    <?php require 'footer.php'; ?>  
  </footer>
</html>

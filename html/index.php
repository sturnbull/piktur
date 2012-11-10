<?php
require_once 'global.inc';

# Prepare the MySQL query statement to select publicly accessible images
if ( !( $stmt = $db->prepare( "SELECT CONCAT( `albums`.`path`, '/', `images`.`file_name` ) AS file, `images`.`image_checksum` FROM `images` JOIN ( `albums`, `album_images` ) ON ( `images`.`image_id` = `album_images`.`image_id` AND `album_images`.`album_id` = `albums`.`album_id` ) WHERE `images`.`public` = 'public' ORDER BY RAND() LIMIT 1" ) ) ) {
    die( 'Prepare failed: (' . $db->errno . ') ' . $db->error );
}
elseif ( !$stmt->execute() ) {
    die( 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error );
}

# Bind results
$stmt->bind_result( $file, $image_checksum );

# Fetch results
$stmt->fetch();

# Close the statement
$stmt->close();

# Ensure image checksum is correct before allowing it to be displayed
if ( $image_checksum != hash_file( 'md5', '/var/www/html/'.$file ) ) {
    die( "Image failed checksum verification: $file" );
}

require 'header.php'; ?>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tr>
        <td height="500" class="center_middle">
          <img alt="image" src="http://<?php echo $_SERVER['SERVER_NAME'] . '/' . $file ?>" height="449" width="600"><br>
        </td>
      </tr>
      <tr>
        <td class="center_middle">
          <a href="http://<?php echo $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] ?>"><img alt="Random Image" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/randomimagebutton.png" border="0" height="50" width="256"></a>
        </td>
      </tr>
    </table>
<?php require 'footer.php'; ?>
  </body>
</html>

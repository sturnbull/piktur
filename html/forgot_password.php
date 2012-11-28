<?php
  require_once 'global.inc';
 
  # prepare variables
  $msg = '';
  
  if ( DEBUG ) {
  }
?>
<html>
  <header>
    <?php require 'header.php'; ?>
  </header
  <body>
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

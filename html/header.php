<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<?php
  if ( $_SERVER['PHP_SELF'] != '/search.php' ) {
    unset( $_SESSION['key'] );
  }
?>
<html>
  <head>
    <meta content="text/html; charset=ISO-8859-1" http-equiv="Content-Type">
    <title>PIKTUR: <?php echo basename( $_SERVER['PHP_SELF'] ) ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/css/livevalidation.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/css/piktur.css" />
    <script type="text/javascript" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/js/livevalidation_standalone.compressed.js"></script>
  </head>
  <body>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tr>
        <td class="left_top"><a href="<?php echo $protocol . $_SERVER['SERVER_NAME'] .'/'; if ( $_SESSION['authenticated'] == 'true' ) { echo 'albumview.php'; }?>"><img src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/img/piktur.png" alt="PIKTUR Logo"></a></td>
<?php if ( isset( $_SESSION['authenticated'] ) ) { ?>
<?php } else { ?>
        <td class="middle_center"><a href="<?php echo 'https://' . $_SERVER['SERVER_NAME'] ?>/signin.php"><img alt="Sign In" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/img/signinbutton.png" border="0" height="45" width="125"></a></td>
        <td class="middle_center"><a href="<?php echo 'https://' . $_SERVER['SERVER_NAME'] ?>/signup.php"><img alt="Sign Up" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/img/signupbutton.png" border="0" height="45" width="136"></a></td>
<?php } ?>
        <?php if ( $_SERVER['PHP_SELF'] != '/search.php' AND  isset( $_SESSION['authenticated'] ) ) { ?>
        <td class="searchlabel">Search</td>
        <td class="searchinput">
        <form id="search_form" name="search_form" action="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/search.php" method="post">            
          <input type="image" src="<?php echo $protocol . $_SERVER['SERVER_NAME'] ?>/img/search.png" alt="Search Button" height="24" width="24">
        </form>    
        </td>
        <?php } ?>
      </tr>
    </table>
    <hr size="3" width="100%">
<?php if ( $_SESSION['authenticated'] == 'true' ) { ?>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tr>
        <td class="left_middle">
          <a href="<?php echo 'https://' . $_SERVER['SERVER_NAME'] ?>">[ Random Image ]</a>
          <a href="<?php echo 'https://' . $_SERVER['SERVER_NAME'] ?>/albumview.php">[ Album View ]</a>
        </td>
        <td class="right_middle">
          <a href="<?php echo 'https://' . $_SERVER['SERVER_NAME'] ?>/admin.php">[ Manage My Account ]</a>
          <a href="<?php echo 'https://' . $_SERVER['SERVER_NAME'] ?>/signout.php">[ Signout ]</a>
        </td>
      </tr>
    </table>
    <hr size="3" width="100%">
<?php } ?>

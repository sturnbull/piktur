<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta content="text/html; charset=ISO-8859-1" http-equiv="Content-Type">
    <title>PIKTUR: <?php echo basename( $_SERVER['PHP_SELF'] ) ?></title>
    <link rel="stylesheet" type="text/css" href="http://<?php echo $_SERVER['SERVER_NAME'] ?>/css/livevalidation.css" />
    <link rel="stylesheet" type="text/css" href="http://<?php echo $_SERVER['SERVER_NAME'] ?>/css/piktur.css" />
    <script type="text/javascript" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/js/livevalidation_standalone.compressed.js"></script>
  </head>
  <body>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tr>
        <td class="left_top"><a href="http://<?php echo $_SERVER['SERVER_NAME'] ?>/"><img src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/piktur.png" alt="PIKTUR Logo"></a></td>
<?php if ( isset( $_SESSION['authenticated'] ) ) { ?>
        <td class="middle_center"><a href="http://<?php echo $_SERVER['SERVER_NAME'] ?>/signout.php"><img alt="Sign In" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/signoutbutton.png" border="0" height="45" width="125"></a></td>
<?php } else { ?>
        <td class="middle_center"><a href="http://<?php echo $_SERVER['SERVER_NAME'] ?>/signin.php"><img alt="Sign In" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/signinbutton.png" border="0" height="45" width="125"></a></td>
        <td class="middle_center"><a href="http://<?php echo $_SERVER['SERVER_NAME'] ?>/signup.php"><img alt="Sign Up" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/signupbutton.png" border="0" height="45" width="136"></a></td>
<?php } ?>
        <td class="searchlabel">Search:<input name="searchterm" id="searchterm"></td>
        <td class="searchinput">
          <form id="search_form" name="add_user_form" action="http://<?php echo $_SERVER['SERVER_NAME'] ?>/search.php" method="post">
            <input type="image" src="http://<?php echo $_SERVER['SERVER_NAME'] ?>/img/search.png" alt="Search Button" height="24" width="24">
          </form>    
        </td>
      </tr>
    </table>
    <hr size="3" width="100%">

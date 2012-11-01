<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<?php


# Validate user input from form post
$name = filter_input( INPUT_POST, 'username', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/^[a-z0-9_]{1,64}$/" ) ) );
$email_address = filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );
$password = filter_input( INPUT_POST, 'password', FILTER_VALIDATE_REGEXP, array( "options"=>array( "regexp"=>"/(?=^[!-~]{8,64}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9])(?=^.*[^\s].*$)(?=.*[\d]).*$/" ) ) );
if ( $password ) $password_hash =  hash( 'sha512', $password );

$debug=1;

if ( $debug ) {
	echo "USERNAME: '$name'<br>";
	echo "EMAIL ADDRESS: '$email_address'<br>";
	echo "PASSWORD: '$password'<br>";
	if ( $password ) echo "PASSWORD HASH: '$password_hash'<br>";
}
?>
<html>
  <head>
    <meta content="text/html; charset=ISO-8859-1" http-equiv="Content-Type">
    <title>sign up</title>
    <link rel="stylesheet" type="text/css" href="http://piktur/css/livevalidation.css" />
    <link rel="stylesheet" type="text/css" href="http://piktur/css/piktur.css" />
    <script type="text/javascript" src="http://piktur/js/livevalidation_standalone.compressed.js"></script>
  </head>
  <body alink="#EE0000" bgcolor="#666666" link="#0000EE" text="#000000" vlink="#551A8B">
    <?php #include( "header.php" ); ?>
    <table border="0" cellpadding="2" cellspacing="2" width="100%">
      <tr>
        <td valign="middle" colspan="3" align="left"><img src="http://piktur/img/piktur.png" alt="PIKTUR Logo"></td>
        <td align="right" valign="middle" class="searchlabel">
          <form id="search_form" name="search_form" action="http://piktur/search.php" method="post">Search:<input name= "searchterm" id="searchterm"</form>
        </td>
        <td align="left" valign="middle" class="searchinput">
          <input type="image" src="http://www.veryicon.com/icon/png/System/Must%20Have/Search.png" alt="Search Button" height="41" width="41">
        </td>
      </tr>
    </table>
    <hr size="3" width="100%">
    <form id="add_user_form" name="add_user_form" action="http://piktur/signup.php" method="post">
      <table border="0" cellpadding="2" cellspacing="2" width="100%">
        <tbody>
          <tr>
            <td colspan="1" rowspan="1" align="center" height="200" valign="top">
              <table border="0" cellpadding="2" cellspacing="4" width="100%">
                <tbody>
                  <tr>
                    <td class="formlabel">Username:</td>
                    <td class"forminput">
                      <input size="18" name="username" id="username" type="text"<?php if ( $name ) echo " value=\"$name\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel">Email:</td>
                    <td class"forminput">
                      <input size="18" name="email" id="email" type="text"<?php if ( $email_address ) echo " value=\"$email_address\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel">Confirm Email:</td>
                    <td class"forminput">
                      <input size="18" name="email2" id="email2" type="text"<?php if ( $email_address ) echo " value=\"$email_address\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel">Password:</td>
                    <td class"forminput">
                      <input size="18" name="password" id="password" type="password"<?php if ( $password ) echo " value=\"$password\""; ?>>
                    </td>
                  </tr>
                  <tr>
                    <td class="formlabel">Confirm Password:</td>
                    <td class"forminput">
                      <input size="18" name="password2" id="password2" type="password"<?php if ( $password ) echo " value=\"$password\""; ?>>
                    </td>
                  </tr>
                </tbody>
              </table>
            </td>
          </tr>
          <tr>
            <td align="center" valign="middle" colspan="1">
              <input type="image" src="http://piktur/img/signupbutton.png" height="45" width="125" border="0" alt="Signup Button">
            </td>
          </tr>
        </tbody>
      </table>
    </form>
    <br>
    <hr size="3" width="100%">
    <br>
    <br>
    <script type="text/javascript" src="http://piktur/js/livevalidation_signup.js"></script>
  </body>
</html>

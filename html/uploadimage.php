<?php
require 'header.php';
$uname = "testuser"."/";
$album = "default"."/";
?>
<!-- code referenced from http://www.tizag.com/phpT/fileupload.php
to understand how to get the file uploaded
also pull info from 
http://www.w3schools.com/php/php_file_upload.asp
-->
  
<!--web form to identify local file
<form action="uploadimage.php" method="post" enctype="multipart/form-data">
<label for="file">Filename:</label>
<input type="file" name="file" id="file" />
<br />
<br />
<input type="submit" name="submit" value="Get the Image" />
</form>
<hr><br>
</body>
</html>
-->
<!-- php code to deal with the file -->
<?php
if ($_FILES["file"]["error"] > 0)
  {
  echo "Error: " . $_FILES["file"]["error"] . "<br />";
  }
else
  {
  echo "Upload: " . $_FILES["file"]["name"] . "<br />";
  echo "Type: " . $_FILES["file"]["type"] . "<br />";
  echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
  echo "Stored in: " . $_FILES["file"]["tmp_name"] . "<br />";
  // Where the file is going to be placed
  //tried uptmp/ - does nto work
  //now try full path - /var/www/piktur/html/
$final_path = "pikturs/"."$uname"."$album"; //final destination folder in html directory 
$temp_path = "uptmp/";//temp folder to validate file
// Add the original filename to our target path.
$temp_path = $temp_path . basename( $_FILES['file']['name']);
echo "Temp path: " . $temp_path . "<br />"; //print the path for troubleshooting
echo "Final path: " . $final_path . "<br />"; //print the path for troubleshooting
//validate file
//
//put code here

//conver to 800x600 jpg  
if(move_uploaded_file($_FILES['file']['tmp_name'], $temp_path)) {
    echo "The file ".  basename( $_FILES['file']['name']). 
    " has been uploaded";
} else{
    echo "There was an error uploading the file, please try again!";
}

 //end result - display the file
echo "<img src=\"./$temp_path\" border='0'>\n"; 
  }
?>

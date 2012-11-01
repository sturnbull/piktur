<html>
<!-- code referenced from http://www.tizag.com/phpT/fileupload.php
to understand how to get the file uploaded
also pull info from 
http://www.w3schools.com/php/php_file_upload.asp
-->
  
  
  
<body>
<br>
  <hr>
<!--web form to identify local file -->
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
$target_path = "uptmp/"; //folder in html directory

/* Add the original filename to our target path.  
Result is "uploads/filename.extension" */
$target_path = $target_path . basename( $_FILES['file']['name']);
echo "Target: " . $target_path . "<br />"; //print the path for troubleshooting
if(move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
    echo "The file ".  basename( $_FILES['file']['name']). 
    " has been uploaded";
} else{
    echo "There was an error uploading the file, please try again!";
}

 //end result - display the file
echo "<img src=\"./$target_path\" border='0'>\n"; 
  }
?>

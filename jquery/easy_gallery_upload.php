<?php

	# Include a configuration file
	require('easy_config.php');

	// Connecting, selecting database
	$link = mysql_connect($db_host, $db_username, $db_password);
	$database_link = mysql_select_db($db_database);

	# Check Sessions
	$sql_query = 'SELECT * FROM exp_sessions WHERE session_id="'.$php_session_id.'"';
	$result = mysql_query($sql_query) or die('Query failed: ' . mysql_error());

	if ($php_session_id != 'true' && mysql_num_rows($result) == 0) {
		header("HTTP/1.0 500 Internal Server Error");
		echo "Could not find session";
		exit(0);
	}







	# Safe File Name
	function safe_filename($filename) {
	  $filename = strtolower($filename);
	  $filename = str_replace("#","_",$filename);
	  $filename = str_replace(" ","_",$filename);
	  $filename = str_replace("'","",$filename);
	  $filename = str_replace('"',"",$filename);
	  $filename = str_replace("__","_",$filename);
	  $filename = str_replace("&","and",$filename);
	  $filename = str_replace("/","_",$filename);
	  $filename = str_replace("\\","_",$filename);
	  $filename = str_replace("?","",$filename);
	  return $filename;
	}

	//function to return the extension of a filename in lowercase without a "." (DOT)
	function get_file_extension($filename)
	{
		$file_extension = strtolower(str_replace('.', '', strrchr($filename, '.')));
		$filename = str_replace('.'.$file_extension, '', $filename);
		return array($filename,$file_extension);
	}

	#########################
    #  Easy_gallery Internal Options
	#########################

	function _recreate_image($source_file,$target_file,$target_width='100',$target_height='100',$target_quality='85',$target_scale='proportion',$file_extension='jpeg')
	{

		# Other file extensions
		if ($file_extension == 'jpg') $file_extension = 'jpeg';

		# Set Defaults
			if ($target_width == '') {
				$target_width = '100';
			} else if ($target_quality == '') {
				$target_quality = '85';
			} else if ($target_scale == '') {
				$target_scale = 'proportion';
			}

		// Get new dimensions
		list($source_width, $source_height) = getimagesize($source_file);

		if ($target_scale == 'crop') {

			if (!empty($target_width)) {
				$target_height = $target_width;
			} else {
				$target_width = $target_height;
			}

			# RESIZE

			if ($source_width > $source_height) {
				$new_height = $target_height;
				$new_width = $target_height * $source_width / $source_height;
			} else {
				$new_height = $target_width * $source_height / $source_width;
				$new_width = $target_width;
			}

			// Resample Image
			$target_image = imagecreatetruecolor($new_width, $new_height);

			if ($file_extension == 'jpeg') {
				$image = @imagecreatefromjpeg($source_file);
			} else if ($file_extension == 'gif') {
				$image = @imagecreatefromgif($source_file);
			}

			if (!@imagecopyresampled($target_image, $image, 0, 0, 0, 0, $new_width, $new_height, $source_width, $source_height)) {
				echo 'alert("Could not resize image")';
				exit(0);
			}

			if ($file_extension == 'jpeg') {
				if (!imagejpeg($target_image,$target_file,$target_quality)) {
					echo 'alert("Can not create file ('.$target_file.')")';
					exit;
				}
			} else if ($file_extension == 'gif') {
				if (!imagegif($target_image,$target_file)) {
					echo 'alert("Can not create file ('.$target_file.')")';
					exit;
				}
			}

			imagedestroy($target_image);
			imagedestroy($image);

			# CROPPING

			// cut out a rectangle from the resized image and store in thumbnail
			$thumbx = (($new_width / 2) - ($target_width / 2));
			$thumby = (($new_height / 2) - ($target_height / 2));

			// Resample Image
			$target_image = imagecreatetruecolor($target_width, $target_height);

			if ($file_extension == 'jpeg') {
				$image = @imagecreatefromjpeg($target_file);
			} else if ($file_extension == 'gif') {
				$image = @imagecreatefromgif($target_file);
			}

			//this is the thumbnail image, where the above is cropped.
			imagecopyresized($target_image, $image, 0, 0, $thumbx, $thumby, $target_width, $target_height, $target_width, $target_height);

			if ($file_extension == 'jpeg') {
				if (!imagejpeg($target_image,$target_file,$target_quality)) {
					echo 'alert("Can not create file ('.$target_file.')")';
					exit;
				}
			} else if ($file_extension == 'gif') {
				if (!imagegif($target_image,$target_file)) {
					echo 'alert("Can not create file ('.$target_file.')")';
					exit;
				}
			}
		} else {

			if (!empty($target_width)) {
				$target_height = '';
			} else {
				$target_width = '';
			}

			# RESIZE PROPORTION
			if ($target_height > $target_width) {
				$new_height = $target_height;
				$new_width = $target_height * $source_width / $source_height;
			} else {
				$new_height = $target_width * $source_height / $source_width;
				$new_width = $target_width;
			}

			// Resample Image
			$target_image = imagecreatetruecolor($new_width, $new_height);

			if ($file_extension == 'jpeg') {
				$image = @imagecreatefromjpeg($source_file);
			} else if ($file_extension == 'gif') {
				$image = @imagecreatefromgif($source_file);
			}

			if (!@imagecopyresampled($target_image, $image, 0, 0, 0, 0, $new_width, $new_height, $source_width, $source_height)) {
				echo 'alert("Could not resize image")';
				exit(0);
			}

			if ($file_extension == 'jpeg') {
				if (!imagejpeg($target_image,$target_file,$target_quality)) {
					echo 'alert("Can not create file ('.$target_file.')")';
					exit;
				}
			} else if ($file_extension == 'gif') {
				if (!imagegif($target_image,$target_file)) {
					echo 'alert("Can not create file ('.$target_file.')")';
					exit;
				}
			}

		}
	}



	# Extra Variables
	$entry_id = $_POST['entry_id'];
	$filename = safe_filename($_FILES['Filedata']['name']);

	# Break apart filename
//	list($filename,$file_extension) = explode('.',$filename);
	list($filename,$file_extension) = get_file_extension($filename);

	// Move Thumbnail to desired Location
	$image_path = $easy_gallery_path.$_POST['entry_id'].'/'.$filename.'.'.$file_extension;
	$image_path_filename = $easy_gallery_path.$_POST['entry_id'].'/'.$filename;
	$thumbnail_path = $easy_gallery_path.$_POST['entry_id'].'/'.$filename.'_thumb.'.$file_extension;
	$thumbnail_url = $easy_gallery_url.$_POST['entry_id'].'/'.$filename.'_thumb.'.$file_extension;

	if (empty($_REQUEST['test'])) $_REQUEST['test'] = '';

	# Test Condition
	if ($_REQUEST['test'] == 'true') {

		# TEST UPLOAD SCRIPT
		echo '<strong>Entry ID:</strong> '.$_POST['entry_id'].'<br />';
		echo '<strong>Full Filename:</strong> '.$filename.'.'.$file_extension.'<br />';
		echo '<strong>Full Path:</strong> '.$image_path.'<br />';

		if (!$link) {
			echo '<br /><strong style="color:#FF0000">Database Connection: </strong> FAILED<br />';
			echo '<br />Please check your database settings. Could not connect: ' . mysql_error().'<br />';
		} else {
			echo '<br /><strong style="color:#00FF00">Database Connection: </strong> SUCCESSFUL<br />';
		}

		if (!$database_link) {
			echo '<br /><strong style="color:#FF0000">Database Name: </strong> DOES NOT EXIST<br />';
		} else {
			echo '<br /><strong style="color:#00FF00">Database Name: </strong> DOES EXISTS<br />';
		}
		echo '<strong>Database Database: </strong> '.$db_database.'<br />';
	}


	# Add or Modify Database
	$sql_query = 'SELECT * FROM exp_easy_gallery_images WHERE filename="'.$filename.'.'.$file_extension.'" AND entry_id="'.$entry_id.'"';
	$result = mysql_query($sql_query) or die('Query failed: ' . mysql_error());
	$mysql_num_rows = mysql_num_rows($result);

/*
	echo '<BR>sql_query: '.$sql_query.'<BR>';
	echo '<BR>num_rows: '.$mysql_num_rows.'<BR>';
*/

	if ($mysql_num_rows < 1) {
		$sql_query = 'INSERT INTO exp_easy_gallery_images (entry_id,filename,date_stamp,date_modified) VALUES ("'.$entry_id.'","'.$filename.'.'.$file_extension.'",NOW(),NOW())';
		$result = mysql_query($sql_query) or die('Query failed: ' . mysql_error());
	}

	# Get the image and create a thumbnail
	if ($file_extension == 'jpg') {
		$img = @imagecreatefromjpeg($_FILES["Filedata"]["tmp_name"]);
	} else if ($file_extension == 'gif') {
		$img = @imagecreatefromgif($_FILES["Filedata"]["tmp_name"]);
	}

	if (!$img) {
		header("HTTP/1.0 500 Internal Server Error");
		echo "could not create image handle";
		exit(0);
	}

	$width = imageSX($img);
	$height = imageSY($img);

	if (!$width || !$height) {
		header("HTTP/1.0 500 Internal Server Error");
		echo "Invalid width or height";
		exit(0);
	}

	// Build the thumbnail
	$target_width = 100;
	$target_height = 100;
	$target_ratio = $target_width / $target_height;

	$img_ratio = $width / $height;

	if ($target_ratio > $img_ratio) {
		$new_height = $target_height;
		$new_width = $img_ratio * $target_height;
	} else {
		$new_height = $target_width / $img_ratio;
		$new_width = $target_width;
	}

	if ($new_height > $target_height) {
		$new_height = $target_height;
	}
	if ($new_width > $target_width) {
		$new_height = $target_width;
	}

	$new_img = ImageCreateTrueColor(100, 100);
	if (!@imagefilledrectangle($new_img, 0, 0, $target_width-1, $target_height-1, 0)) {	// Fill the image black
		header("HTTP/1.0 500 Internal Server Error");
		echo "Could not fill new image";
		exit(0);
	}

	if (!@imagecopyresampled($new_img, $img, ($target_width-$new_width)/2, ($target_height-$new_height)/2, 0, 0, $new_width, $new_height, $width, $height)) {
		header("HTTP/1.0 500 Internal Server Error");
		echo "Could not resize image";
		exit(0);
	}

	if (!isset($_SESSION["file_info"])) {
		$_SESSION["file_info"] = array();
	}

	# Create Entry Gallery
	$directory = $easy_gallery_path.$_POST['entry_id'].'/';
	if (!file_exists($directory)) {
		mkdir($directory,0777);
	}

	# Create Thumbnail Folder
	$directory = $easy_gallery_path.$_POST['entry_id'].'/';
	if (!file_exists($directory)) {
		mkdir($directory,0777);
	}

	// Use a output buffering to load the image into a variable
	if ($file_extension == 'jpg') {
		imagejpeg($new_img,$thumbnail_path);
	} else if ($file_extension == 'gif') {
		imagegif($new_img,$thumbnail_path);
	}

	// Move image to desired Location
	if (!move_uploaded_file($_FILES['Filedata']['tmp_name'], $image_path)) {
		header("HTTP/1.0 500 Internal Server Error");
		echo "Could not fill new image";
		exit(0);
	}

	########################################  CREATE IMAGES
	# Get Settings Path
	$files_path = $easy_gallery_path.$_POST['entry_id'].'/';

	# Get Settings
	$sql_query = 'SELECT * FROM exp_easy_gallery WHERE entry_id="'.$entry_id.'"';
	$result = mysql_query($sql_query) or die('Query failed: ' . mysql_error());
	while ($row = mysql_fetch_assoc($result)) {
		foreach ($row as $key => $val) {
			$settings[$key] = $val;
		}
	}

/*
	echo 'sql_query: '.$sql_query.'<br>';
	echo 'SETTINGS: <br>';
	print_r($settings);
	echo '<br>';
*/

	# Recreate Images
	$sizes = array('small' => 's','medium' => 'm','large' => 'l');
	foreach($sizes as $size => $prefix) {
		$target_path = $image_path_filename.'_'.$prefix.'.'.$file_extension;
#		echo 'file 1 = image_path: '.$image_path.' > target_path: '.$target_path.'<BR>';
		_recreate_image($image_path,$target_path,$settings[$size.'_width'],$settings[$size.'_height'],$settings[$size.'_quality'],$settings[$size.'_scale'],$file_extension);
	}

	# Test file exists
	if ($_REQUEST['test'] == 'true') {

		# TEST FILE EXISTS
		if (file_exists($image_path)) {
			echo '<br /><strong>File Exists</strong>'.'<br />';
			echo '<img src="'.$thumbnail_url.'">'.'<br /><br />';
		} else {
			echo '<br /><strong>File Does Not Exists</strong>'.'<br /><br />';
		}
	}

	# NOT A TEST
	echo $thumbnail_url;	// Return File Path of thumbnail

?>
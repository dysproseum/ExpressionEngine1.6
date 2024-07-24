<?php

	############ START EDITABLE VARIABLES

	$db_host = 'localhost';
	$db_database = 'domain_com_exp';
	$db_username = 'root';
	$db_password = '';

	# Variables
	$easy_gallery_path = $_SERVER["DOCUMENT_ROOT"].'/images/easy_gallery/';
	$easy_gallery_url = '/images/easy_gallery/';

	# Session Id
	$php_session_id = isset($_POST["S"]) ? $_POST["S"] : false;
	session_id($php_session_id);
	session_start();

	############ END EDITABLE VARIABLES

?>
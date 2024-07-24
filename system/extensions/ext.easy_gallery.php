<?php

if ( ! defined('EXT')) {
    exit('Invalid file request'); }

$field_translator = array();


class Easy_gallery
{
    var $settings        = array();
    
    var $name            = 'Easy Gallery';
    var $version         = '1.0.0';
    var $description     = 'Adds a new "gallery" tab when adding or editing a new entry.';
    var $settings_exist  = 'y';
    var $docs_url        = 'http://www.laxa.ca/ee_home.html';//'http://expressionengine.com';

	var $session_id		= '';
	var $base_url		= '';

    // -------------------------------
    //   Constructor - Extensions use this for settings
    // -------------------------------
    
    function Easy_gallery($settings='')
    {
		global $DB;

		$this->settings = $settings;

		# Fetch the easy shop settings
		$query = $DB->query("SELECT * FROM exp_easy_gallery_settings");
		foreach($query->result as $row) {
			$this->settings[$row['name']] = $row['value'];
		}
	}
    // END


	// --------------------------------
	//  Settings
	// --------------------------------  

	function settings()
	{
		global $DB;

		$settings = array();

		$results = $DB->query('SELECT * FROM exp_weblogs');
		foreach($results->result as $fields) {
			$weblog_ids[$fields['weblog_id']] = $fields['blog_title'];
		}

		#$settings['extension_weblogs']    = '';
		$settings['extension_weblogs']   = array('ms', $weblog_ids, '1');
		
		// Complex:
		// [variable_name] => array(type, values, default value)
		// variable_name => short name for setting and used as the key for language file variable
		// type:  t - textarea, r - radio buttons, s - select, ms - multiselect, f - function calls
		// values:  can be array (r, s, ms), string (t), function name (f)
		// default:  name of array member, string, nothing
		//
		// Simple:
		// [variable_name] => 'Butter'
		// Text input, with 'Butter' as the default.
		
		return $settings;
	}
	// END


	// --------------------------------
	//  Activate Extension
	// --------------------------------

	function activate_extension()
	{
		global $DB;
		
		$DB->query($DB->insert_string('exp_extensions',
									  array(
											'extension_id' => '',
											'class'        => "Easy_gallery",
											'method'       => "publish_form_headers",
											'hook'         => "publish_form_headers",
											'settings'     => "",
											'priority'     => 9,
											'version'      => $this->version,
											'enabled'      => "y"
										  )
									 )
				  );
		$DB->query($DB->insert_string('exp_extensions',
									  array(
											'extension_id' => '',
											'class'        => "Easy_gallery",
											'method'       => "publish_form_new_tabs",
											'hook'         => "publish_form_new_tabs",
											'settings'     => "",
											'priority'     => 9,
											'version'      => $this->version,
											'enabled'      => "y"
										  )
									 )
				  );
		$DB->query($DB->insert_string('exp_extensions',
									  array(
											'extension_id' => '',
											'class'        => "Easy_gallery",
											'method'       => "publish_form_new_tabs_block",
											'hook'         => "publish_form_new_tabs_block",
											'settings'     => "",
											'priority'     => 9,
											'version'      => $this->version,
											'enabled'      => "y"
										  )
									 )
				  );
	}
	// END


	function publish_form_headers($which, $submission_error, $entry_id, $weblog_id, $hidden) {

		global $SESS, $EXT;

		if(!in_array($weblog_id, $this->settings['extension_weblogs'])){

			if($EXT->last_call){
				return $headers . $EXT->last_call;
			} else {
				return;
			}
		}

		# Other Settings
		$session_id = $SESS->userdata['session_id'];
		$base_url = $this->settings['system_url'].'index.php?S='.$session_id.'&C=modules&M=easy_gallery&P=ext_gallery_upload_files&weblog_id='.$weblog_id.'&entry_id='.$entry_id;
		$base_url = $this->settings['system_url'].'index.php?S='.$session_id.'&C=modules&M=easy_gallery';
		$jquery_url = $this->settings['jquery_url'];

		/*
		$arg_list = func_get_args();
        echo "Argument which is: " . $arg_list[0] . "<br />\n";
        echo "Argument submission_error is: " . $arg_list[1] . "<br />\n";
        echo "Argument entry_id is: " . $arg_list[2] . "<br />\n";
        echo "Argument weblog_id is: " . $arg_list[3] . "<br />\n";
        echo "Argument hidden is: " . $arg_list[4] . "<br />\n";

		$session_id = $this->session_id;
		$base_url = $this->base_url;
		*/

		# Javascript Calls
$headers = <<<EOT

		<style type="text/css" src="{$jquery_url}SWFUpload.css">	</style>  
		<style type="text/css">
			.sortable_item
			{
				float: left;
				width: 215px;
			}		
			.sort_helper
			{
				background-color: #f00;
				float: left;
			}
			.sortable_active
			{
			}
			.sortable_hover
			{
			}
		</style>

		<script language="javascript" src="{$jquery_url}jquery.js"></script>
		<script language="javascript" src="{$jquery_url}SWFUpload.js"></script>
		<script language="javascript" src="{$jquery_url}handlers.js"></script>
		<script language="javascript" src="{$jquery_url}interface.js"></script>

		<script language="javascript">
		var swfu;

		function initUpload() {
			swfu = new SWFUpload({

				// Backend Settings
				upload_target_url: "/jquery/easy_gallery_upload.php",	// Relative to the SWF file
				post_params: {"S": "{$session_id}", "M": "module", "P": "ext_gallery_upload_files", "weblog_id": "$weblog_id", "entry_id": "$entry_id"},

				// File Upload Settings
				file_size_limit : "2048",	// 2MB
				file_types : "*.jpg;*.gif",
				file_types_description : "JPG Images",
				file_upload_limit : "0",
				begin_upload_on_queue : true,
				use_server_data_event : true,
				validate_files : false,

				// Event Handler Settings
				file_queued_handler : fileQueued,
				file_progress_handler : fileProgress,
				file_cancelled_handler : fileCancelled,
				file_complete_handler : fileComplete,
				queue_complete_handler : queueComplete,
				//queue_stopped_handler : queueStopped,
				//dialog_cancelled_handler : fileDialogCancelled,
				error_handler : uploadError,

				// Flash Settings
				flash_url : "{$jquery_url}SWFUpload.swf",	// Relative to this file

				// UI Settings
				ui_container_id : "swfu_container",
				degraded_container_id : "degraded_container",

				// Debug Settings
				debug: true
			});
			swfu.addSetting("upload_target", "divFileProgressContainer");
		}
//		document.write("{$base_url}");
		</script>






		<script type="text/javascript">
        <!--

		// prepare the form when the DOM is ready 
		$(document).ready(function() { 

			// GET Request
			$.ajax({
				type: "GET",
				url: "{$base_url}",
				data: "P=ext_gallery_images&weblog_id={$weblog_id}&entry_id={$entry_id}",
				dataType: "html",
				success: 
				   function(data){
					 $('#extension_gallery').html(data);
				   }
			})
		}); 

		// AJAX HTML UPLOAD
		function ext_gallery_settings() {
			// GET Request

			$.ajax({
				type: "GET",
				url: "{$base_url}",
				data: "P=ext_gallery_settings&weblog_id={$weblog_id}&entry_id={$entry_id}",
				dataType: "html",
				success: 
				   function(data){
					 $('#extension_gallery').html(data);
				   }
			})
		}  // end ajax_test

		// AJAX HTML UPLOAD
		function ext_gallery_upload_html() {
			// GET Request

			$.ajax({
				type: "GET",
				url: "{$base_url}",
				data: "P=ext_gallery_upload_html&weblog_id={$weblog_id}&entry_id={$entry_id}",
				dataType: "html",
				success: 
				   function(data){
					 $('#extension_gallery').html(data);
					 initUpload();
				   }
			})
		}  // end ajax_test


		// AJAX FLASH UPLOAD
		function ext_gallery_upload_flash() {
			// GET Request

			$.ajax({
				type: "GET",
				url: "{$base_url}",
				data: "P=ext_gallery_upload_flash&weblog_id={$weblog_id}&entry_id={$entry_id}",
				dataType: "html",
				success: 
				   function(data){
					 $('#extension_gallery').html(data);
					 initUpload();
				   }
			})
		}  // end ajax_test

		// AJAX TEST
		function ext_gallery_images(entry_id) {
			// GET Request

			$.ajax({
				type: "GET",
				url: "{$base_url}",
				data: "P=ext_gallery_images&weblog_id={$weblog_id}&entry_id="+entry_id,
				dataType: "html",
				success: 
				   function(data){
					 $('#extension_gallery').html(data);
				   }
			})
		}  // end ajax_test

		// AJAX TEST
		function ext_gallery_order() {
			// GET Request

			$.ajax({
				type: "GET",
				url: "{$base_url}",
				data: "P=ext_gallery_order&weblog_id={$weblog_id}&entry_id={$entry_id}",
				dataType: "html",
				success: 
				   function(data){
					 $('#extension_gallery').html(data);
				   }
			})
		}  // end ajax_test

/*
		$('#items').Sortable(
			{
				accept: 'sortable_item',
				tolerance: 'pointer',
				floats:	true,
				opacity: 0.5,
				fit: true,

				helperclass: 'sort_helper',
				activeclass : 'sortable_active',
				hoverclass : 'sortable_hover',
			}
		);
*/
		function mod_gallery_settings(entry_id) {

			var answer = confirm('Are you sure you want to update entry_id "'+entry_id+'" settings?');
			if (answer){
					var status = $("input[@name=status][@checked]").val();
					var small_width = $("input[@name=small_width]").val();
					var small_height = $("input[@name=small_height]").val();
					var small_quality = $("input[@name=small_quality]").val();
					var small_scale = $("input[@name=small_scale][@checked]").val();
					var medium_width = $("input[@name=medium_width]").val();
					var medium_height = $("input[@name=medium_height]").val();
					var medium_quality = $("input[@name=medium_quality]").val();
					var medium_scale = $("input[@name=medium_scale][@checked]").val();
					var large_width = $("input[@name=large_width]").val();
					var large_height = $("input[@name=large_height]").val();
					var large_quality = $("input[@name=large_quality]").val();
					var large_scale = $("input[@name=large_scale][@checked]").val();

//					alert("P=ext_gallery_images_action&A=entry_recreate&which="+which+"&image_width="+image_width+"&image_height="+image_height+"&image_quality="+image_quality+"&image_scale="+image_scale+"&entry_id="+entry_id);
//					alert("&status="+status+"&small_width="+small_width+"&small_height="+small_height+"&small_quality="+small_quality+"&small_scale="+small_scale+"&medium_width="+medium_width+"&medium_height="+medium_height+"&medium_quality="+medium_quality+"&medium_scale="+medium_scale+"&large_width="+large_width+"&large_height="+large_height+"&large_quality="+large_quality+"&large_scale="+large_scale+"");


				$.ajax({
					type: "GET",
					url: "{$base_url}",
					data: "P=ext_gallery_settings_action&status="+status+"&small_width="+small_width+"&small_height="+small_height+"&small_quality="+small_quality+"&small_scale="+small_scale+"&medium_width="+medium_width+"&medium_height="+medium_height+"&medium_quality="+medium_quality+"&medium_scale="+medium_scale+"&large_width="+large_width+"&large_height="+large_height+"&large_quality="+large_quality+"&large_scale="+large_scale+"&entry_id="+entry_id,
					dataType: "script",
					success: 
					   function(data){
						ext_gallery_upload_flash();
					   }
/*
					   function(data){
						 $('#extension_gallery').html(data);
					   }
*/
				})
			}
		}

		function mod_gallery_image_order(entry_id,image_id,order_id) {

//			alert('entry_id: '+entry_id+' image_id: '+image_id+' order_id: '+order_id);

			$.ajax({
				type: "GET",
				url: "{$base_url}",
				data: "P=ext_gallery_images_action&A=image_order&entry_id="+entry_id+"&image_id="+image_id+"&order_id="+order_id,
				dataType: "script",
				success: 
					function(msg){
						ext_gallery_images(entry_id);
					}
			})
		}

		function mod_gallery_image_caption(entry_id,image_id,caption) {
//			var caption = prompt("Enter your caption", "")

			$.ajax({
				type: "GET",
				url: "{$base_url}",
				data: "P=ext_gallery_images_action&A=image_caption&entry_id="+entry_id+"&image_id="+image_id+"&caption="+caption,
				dataType: "script",
				success: 
					function(msg){
						ext_gallery_images(entry_id);
	//				 $('#extension_gallery').html(msg);
					}
			})
		}

		function mod_gallery_image_delete(entry_id,image_id) {
			$.ajax({
				type: "GET",
				url: "{$base_url}",
				data: "P=ext_gallery_images_action&A=image_delete&entry_id="+entry_id+"&image_id="+image_id,
				dataType: "script",
				success: 
					function(msg){
						ext_gallery_images(entry_id);
					}
			})
		}

		function mod_gallery_image_cover(entry_id,image_id) {
			$.ajax({
				type: "GET",
				url: "{$base_url}",
				data: "P=ext_gallery_images_action&A=image_cover&entry_id="+entry_id+"&image_id="+image_id,
				dataType: "script",
				success: 
					function(msg){
						ext_gallery_images(entry_id);
					}
			})
		}

		function mod_gallery_entry_recreate(which,entry_id) {

			var answer = confirm('Are you sure you want to reacreate the '+which+' images?');
			if (answer){
				if (which == 'small') {
					var image_width = $("input[@name=small_width]").val();
					var image_height = $("input[@name=small_height]").val();
					var image_quality = $("input[@name=small_quality]").val();
					var image_scale = $("input[@name=small_scale][@checked]").val();
				} else if (which == 'medium') {
					var image_width = $("input[@name=medium_width]").val();
					var image_height = $("input[@name=medium_height]").val();
					var image_quality = $("input[@name=medium_quality]").val();
					var image_scale = $("input[@name=medium_scale][@checked]").val();
				} else if (which == 'large') {
					var image_width = $("input[@name=large_width]").val();
					var image_height = $("input[@name=large_height]").val();
					var image_quality = $("input[@name=large_quality]").val();
					var image_scale = $("input[@name=large_scale][@checked]").val();
				}
//					alert("P=ext_gallery_images_action&A=entry_recreate&which="+which+"&image_width="+image_width+"&image_height="+image_height+"&image_quality="+image_quality+"&image_scale="+image_scale+"&entry_id="+entry_id);
//					alert("which="+which+"&image_width="+image_width+"&image_height="+image_height+"&image_quality="+image_quality+"&image_scale="+image_scale);
//					alert("&image_scale="+image_scale);


//				$("body").css('cursor','wait');
				$.ajax({
					type: "GET",
					url: "{$base_url}",
					data: "P=ext_gallery_images_action&A=entry_recreate&entry_id="+entry_id+"&which="+which+"&image_width="+image_width+"&image_height="+image_height+"&image_quality="+image_quality+"&image_scale="+image_scale,
					dataType: "script",
					success: 
						function(msg){
							ext_gallery_images(entry_id);
//							$("body").css('cursor','default');
						}
				})
			}
		}

		function mod_gallery_update_settings(entry_id) {

			var answer = confirm('Are you sure you want to update entry_id "'+entry_id+'" settings?');
			if (answer){
					var small_width = $("input[@name=small_width]").val();
					var small_height = $("input[@name=small_height]").val();
					var small_quality = $("input[@name=small_quality]").val();
					var small_scale = $("input[@name=small_scale][@checked]").val();
					var medium_width = $("input[@name=medium_width]").val();
					var medium_height = $("input[@name=medium_height]").val();
					var medium_quality = $("input[@name=medium_quality]").val();
					var medium_scale = $("input[@name=medium_scale][@checked]").val();
					var large_width = $("input[@name=large_width]").val();
					var large_height = $("input[@name=large_height]").val();
					var large_quality = $("input[@name=large_quality]").val();
					var large_scale = $("input[@name=large_scale][@checked]").val();

//					alert("P=ext_gallery_images_action&A=entry_recreate&which="+which+"&image_width="+image_width+"&image_height="+image_height+"&image_quality="+image_quality+"&image_scale="+image_scale+"&entry_id="+entry_id);
					alert("&small_width="+small_width+"&small_height="+small_height+"&small_quality="+small_quality+"&small_scale="+small_scale+"&medium_width="+medium_width+"&medium_height="+medium_height+"&medium_quality="+medium_quality+"&medium_scale="+medium_scale+"&large_width="+large_width+"&large_height="+large_height+"&large_quality="+large_quality+"&large_scale="+large_scale+"");


//				$("body").css('cursor','wait');
				$.ajax({
					type: "GET",
					url: "{$base_url}",
					data: "P=update_settings&entry_id="+entry_id+"&small_width="+small_width+"&small_height="+small_height+"&small_quality="+small_quality+"&small_scale="+small_scale+"&medium_width="+medium_width+"&medium_height="+medium_height+"&medium_quality="+medium_quality+"&medium_scale="+medium_scale+"&large_width="+large_width+"&large_height="+large_height+"&large_quality="+large_quality+"&large_scale="+large_scale,
					dataType: "script",
					success: 
					   function(data){
						ext_gallery_upload_flash();
//						 $("body").css('cursor','default');
						}
				})
			}
		}


		function mod_gallery_entry_delete(which,entry_id) {

//			document.write("http://localhost'.$this->settings['system_url'].'index.php?S=20880686c2a9b4c95fd8b5b412cb6920d9644aff&C=modules&M=easy_gallery&P=ext_gallery_images_action&A=entry_delete&entry_id="+entry_id);

			var answer = confirm('Are you sure you want to delete '+which+' images?');

			if (answer){
				$.ajax({
					type: "GET",
					url: "{$base_url}",
					data: "P=ext_gallery_images_action&A=entry_delete&entry_id="+entry_id+"&which="+which,
					dataType: "script",
					success: 
						function(msg){
							ext_gallery_images(entry_id);
						}
				})
			}
		}

		-->
		</script>

EOT;

		if($EXT->last_call){
//		die('now: '.$headers);
			return $headers . $EXT->last_call;
		} else {
			return $headers;
		}

	}

	function publish_form_new_tabs($publish_tabs, $weblog_id, $entry_id, $hidden) {

		global $EXT;

		if(!in_array($weblog_id, $this->settings['extension_weblogs'])){
			if($EXT->last_call){
			  return $EXT->last_call;
			} else {
			  return $publish_tabs;      
			}
		}

		if($EXT->last_call){
		  $EXT->last_call['gallery'] = 'Gallery';
		  return $EXT->last_call;
		} else {
		  $publish_tabs['gallery'] = 'Gallery';
		  return $publish_tabs;      
		}

	}

	function publish_form_new_tabs_block($weblog_id) {

		global $DSP, $LANG, $EXT;

		$r = '';

		$menu_status = 'y';
		$menu_author = 'y';
		$menu_options = 'y';
		$menu_weblog = 'y';

        /** ---------------------------------------------
        /**  OPTIONS BLOCK
        /** ---------------------------------------------*/
        
		$r .= '<div id="blockgallery" style="display: none; padding:0; margin:0;">';
		$r .= NL.'<div class="publishTabWrapper">';	
		$r .= NL.'<div class="publishBox">';

$r .= <<<EOT

<div id="extension_gallery" style="HEIGHT:360px; WIDTH:100%; OVERFLOW:auto; padding:5px;">
</div>


EOT;

		$r .= NL.'<div class="publishInnerPad">';
/*
		$r .= '<input id="test1" type="button" name="test1" value="test1">';
		$r .= '<input id="test2" type="button" name="test2" value="test2">';
*/

		$r .= $DSP->div_c();
		$r .= $DSP->div_c();  
		$r .= $DSP->div_c();  
		$r .= $DSP->div_c(); 

		if($EXT->last_call){
			return $r . $EXT->last_call;
		} else {
			return $r;
		}

		return $r;
	}


	// --------------------------------
	//  Update Extension
	// --------------------------------  

	function update_extension($current='')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		if ($current < '1.0.1')
		{
			// Update to next version 1.0.1
		}
		
		if ($current < '1.0.2')
		{
			// Update to next version 1.0.2
		}
		
		$DB->query("UPDATE exp_extensions 
					SET version = '".$DB->escape_str($this->version)."' 
					WHERE class = 'Example_extension'");
	}
	// END



	// --------------------------------
	//  Disable Extension
	// --------------------------------

	function disable_extension()
	{
		global $DB;
		
		$DB->query("DELETE FROM exp_extensions WHERE class = 'Easy_gallery'");
	}
	// END
}
// END CLASS

?>
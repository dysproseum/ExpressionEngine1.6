<?php

/*
=====================================================
Multi Relationship
-----------------------------------------------------
http://docs.markhuot.com/
-----------------------------------------------------
Copyright (c) 2007 - today Mark Huot
=====================================================
THIS MODULE IS PROVIDED "AS IS" WITHOUT WARRANTY OF
ANY KIND OR NATURE, EITHER EXPRESSED OR IMPLIED,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE,
OR NON-INFRINGEMENT.
=====================================================
File: ext.mh_multi_relationship_ext.php
-----------------------------------------------------
Purpose: Multiple Relationship Drop Down - EXT
=====================================================
*/

if (!defined('EXT'))
{
    exit('Invalid file request');
}


class Mh_file_EXT {
	var $settings		= array();
	
	var $name			= 'File, by Mark Huot';
	var $short_name		= 'File';
	var $type			= 'file';
	var $version		= '3.1.1';
	var $description	= 'Adds a "File" field type';
	var $settings_exist	= 'n';
	var $docs_url		= 'http://docs.markhuot.com';
	
	//  =============================================
	//  CP VARIABLES
	//  =============================================
	var $allow_multiple = FALSE;
	var $show_thumbnails = FALSE;
	
	//  =============================================
	//  UPLOAD VARIABLES
	//  =============================================
	var $server_path = FALSE;
	var $max_file_size = FALSE;
	var $rewrite_filenames = FALSE;
	var $clean_filenames = TRUE;
	var $resize_images = FALSE;
	var $keep_originals = TRUE;
	var $delete_from_server = TRUE;
	var $thumb_suffix = '_thumb';
	var $icon_path = '/images';
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param array
	 * @return NULL
	 */
	function Mh_file_EXT($settings='')
	{
		$this->settings = $settings;
	}
	
	/**
	 * Activate Extension
	 *
	 * @access public
	 * @param NULL
	 * @return NULL
	 */
	function activate_extension()
	{
		global $DB;
		
		$DB->query($DB->insert_string('exp_extensions', array('extension_id'=>'', 'class'=>get_class($this), 'method'=>'edit_field_groups', 'hook'=>'show_full_control_panel_end', 'settings'=>'', 'priority'=>10, 'version'=>$this->version, 'enabled'=>'y')));
		$DB->query($DB->insert_string('exp_extensions', array('extension_id'=>'', 'class'=>get_class($this), 'method'=>'edit_upload_prefs', 'hook'=>'show_full_control_panel_end', 'settings'=>'', 'priority'=>9, 'version'=>$this->version, 'enabled'=>'y')));
		$DB->query($DB->insert_string('exp_extensions', array('extension_id'=>'', 'class'=>get_class($this), 'method'=>'edit_custom_field', 'hook'=>'publish_admin_edit_field_extra_row', 'settings'=>'', 'priority'=> 10, 'version'=>$this->version, 'enabled'=>'y')));
		$DB->query($DB->insert_string('exp_extensions', array('extension_id'=>'', 'class'=>get_class($this), 'method'=>'edit_publish_ad_post', 'hook'=>'sessions_start', 'settings'=>'', 'priority'=>10, 'version'=>$this->version, 'enabled'=>'y')));
		$DB->query($DB->insert_string('exp_extensions', array('extension_id'=>'', 'class'=>get_class($this), 'method'=>'edit_prefs_post', 'hook'=>'sessions_start', 'settings'=>'', 'priority'=>9, 'version'=>$this->version, 'enabled'=>'y')));
		$DB->query($DB->insert_string('exp_extensions', array('extension_id'=>'', 'class'=>get_class($this), 'method'=>'publish', 'hook'=>'publish_form_field_unique', 'settings'=>'', 'priority'=>10, 'version'=>$this->version, 'enabled'=>'y')));
		$DB->query($DB->insert_string('exp_extensions', array('extension_id'=>'', 'class'=>get_class($this), 'method'=>'modify_post', 'hook'=>'submit_new_entry_start', 'settings'=>'', 'priority'=>10, 'version'=>$this->version, 'enabled'=>'y')));
		$DB->query($DB->insert_string('exp_extensions', array('extension_id'=>'', 'class'=>get_class($this), 'method'=>'modify_template', 'hook'=>'weblog_entries_tagdata', 'settings'=>'', 'priority'=>10, 'version'=>$this->version, 'enabled'=>'y')));
		
		$DB->query('CREATE TABLE `exp_mh_file` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `upload_id` INT NOT NULL, `key` VARCHAR( 255 ) NOT NULL, `value` VARCHAR( 255 ) NOT NULL)');
		$DB->query('ALTER TABLE `exp_mh_file` ADD UNIQUE  `UPLOAD_KEY` (  `upload_id` ,  `key` )');
	}
	
	/**
	 * Settings
	 *
	 * @access public
	 * @param array
	 * @return array
	 */
	function settings()
	{
		return array();
	}
	
	/**
	 * Update
	 *
	 * @access public
	 * @param string
	 * @return NULL
	 */
	function update_extension($current = '')
	{
		global $DB;
		
		//	=============================================
		//	Is Current?
		//	=============================================
		if($current == '' || $current == $this->version) return FALSE;
		
		//	=============================================
		//	Update?
		//	=============================================
		$table_exists = $DB->query("SHOW TABLES LIKE 'exp_mh_file'");
		if($current < '3.1.0' && $table_exists->num_rows == 0)
		{
			$DB->query($DB->insert_string('exp_extensions', array('extension_id'=>'', 'class'=>get_class($this), 'method'=>'edit_upload_prefs', 'hook'=>'show_full_control_panel_end', 'settings'=>'', 'priority'=>9, 'version'=>$this->version, 'enabled'=>'y')));
			$DB->query($DB->insert_string('exp_extensions', array('extension_id'=>'', 'class'=>get_class($this), 'method'=>'edit_prefs_post', 'hook'=>'sessions_start', 'settings'=>'', 'priority'=>9, 'version'=>$this->version, 'enabled'=>'y')));
			
			$DB->query('CREATE TABLE `exp_mh_file` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `upload_id` INT NOT NULL, `key` VARCHAR( 255 ) NOT NULL, `value` VARCHAR( 255 ) NOT NULL)');
			$DB->query('ALTER TABLE `exp_mh_file` ADD UNIQUE  `UPLOAD_KEY` (  `upload_id` ,  `key` )');
			
			$upload_locations = $DB->query('SELECT id, properties FROM exp_upload_prefs');
			foreach($upload_locations as $p)
			{
				parse_str($p['properties'], $settings);
				foreach($settings as $key=>$value)
				{
					if(!isset($this->$key)) continue;
					
					$id = $p['id'];
					$value = ($value === 'yes')?TRUE:$value;
					$value = ($value === 'no')?FALSE:$value;
					
					$DB->query("INSERT INTO exp_mh_file VALUES('', {$id}, '{$key}', '{$value}') ON DUPLICATE KEY UPDATE id=values(id), `key`=values(`key`), value=values(value)");
				}
				
				if(count($settings) > 0)
				{
					$DB->query('UPDATE exp_upload_prefs SET properties="" WHERE id='.$p['id']);
				}
			}
		}
		
		$DB->query("UPDATE exp_extensions SET version = '".$DB->escape_str($this->version)."' WHERE class='".get_class($this)."'");
	}
	
	/**
	 * Disable Extension
	 *
	 * @access public
	 * @param NULL
	 * @return NULL
	 */
	function disable_extension()
	{
		global $DB;
		
		$DB->query("DELETE FROM exp_extensions WHERE class='Mh_file_EXT'");
		
		$DB->query('DROP TABLE `exp_mh_file`');
	}
	
	/**
	 * Add 'File' to 'Edit Field Groups Page'
	 *
	 * @access public
	 * @param string
	 * @return string
	 */
	function edit_field_groups( $out )
	{
		global $DB, $EXT, $LANG;
		
		if($EXT->last_call !== false)
		{
			$out = $EXT->last_call;
		}
		
		//	=============================================
		//	Find Empty Table Cell
		//	=============================================
		if(preg_match_all("/C=admin&amp;M=blog_admin&amp;P=edit_field&amp;field_id=(\d*).*?<\/td>.*?<td.*?".">.*?<\/td>.*?<\/td>/is", $out, $matches))
		{
			foreach($matches[1] as $key=>$field_id)
			{
				$query = $DB->query("SELECT field_type FROM exp_weblog_fields WHERE field_id='".$field_id."' LIMIT 1");
				if($query->row["field_type"] == $this->type)
				{
					$replace = preg_replace("/(<td.*?<td.*?".">).*?<\/td>/si", "$1".$this->short_name."</td>", $matches[0][$key]);
					$out = str_replace($matches[0][$key], $replace, $out);
				}
			}
		}
		
		//	=============================================
		//	Make Publish Table multipart/form-data
		//	=============================================
		if(preg_match("/name=.entryform./", $out, $matches))
		{
			$out = str_replace($matches[0], $matches[0]." enctype=\"multipart/form-data\" onsubmit=\"return uploadFiles();\"", $out);
		}
		
		return $out;
	}
	
	/**
	 * Add 'File' to 'New Field' page
	 *
	 * @access public
	 * @param NULL
	 * @return NULL
	 */
	function edit_custom_field( $data, $r )
	{
		global $DB, $EXT, $LANG;
		$LANG->fetch_language_file('mh_file_ext');
		
		if($EXT->last_call !== false)
		{
			$r = $EXT->last_call;
		}
		
		//	=============================================
		//	Add to <select /> list
		//	=============================================
		$selected = ($data["field_type"] == $this->type)?' selected="selected"':'';
		$r = preg_replace("/(<select.*?name=.field_type.*?value=.select.*?[\r\n])/is", "$1<option value=\"".$this->type."\"$selected>".$this->short_name."</option>\r", $r);
		
		//	=============================================
		//	Add to JS
		//	=============================================
		$r = preg_replace("/(id\s*==.*?)\}/s", "$1\t\tdocument.getElementById('file_block').style.display = \"none\";
		}", $r);
		
		$r = preg_replace("/(id\s*==\s*.rel.*?})/is", "$1\r\t\telse if (id == '".$this->type."')
\t\t{
\t\t\tdocument.getElementById('rel_block').style.display = \"none\";
\t\t\tdocument.getElementById('select_block').style.display = \"none\";
\t\t\tdocument.getElementById('pre_populate').style.display = \"none\";
\t\t\tdocument.getElementById('text_block').style.display = \"none\";
\t\t\tdocument.getElementById('textarea_block').style.display = \"none\";
\t\t\tdocument.getElementById('date_block').style.display = \"none\";
\t\t\tdocument.getElementById('relationship_type').style.display = \"none\";	
\t\t\tdocument.getElementById('formatting_block').style.display = \"none\";
\t\t\tdocument.getElementById('formatting_unavailable').style.display = \"block\";
\t\t\tdocument.getElementById('file_block').style.display = \"block\";
\t\t}", $r);
		
		//	=============================================
		//	Select Proper Blocks
		//	=============================================
		if(isset($data["field_type"]) && $data["field_type"] == $this->type)
		{
			preg_match("/(id=.formatting_block.*?display:\s*)block/", $r, $formatting_unavailable);
			if(count($formatting_unavailable) > 0)
			{
				$r = str_replace($formatting_unavailable[0], $formatting_unavailable[1] .= "none", $r);
			}
			preg_match("/(id=.formatting_unavailable.*?display:\s*)none/", $r, $formatting_unavailable);
			if(count($formatting_unavailable) > 0)
			{
				$r = str_replace($formatting_unavailable[0], $formatting_unavailable[1] .= "block", $r);
			}
		}
		
		//	=============================================
		//	Add custom drop down
		//	=============================================
		$display = "none";
		if(isset($data["field_type"]) && $data["field_type"] == $this->type) $display = "block";
		$block = "<div id=\"file_block\" style=\"display:$display; padding:0; margin:0;\">
		<div class='defaultBold' >".$LANG->line('select_location')."</div>
		<div class='itemWrapper'><select name=\"field_download_directory\"><option value=\"\"></option>";
		$dls = $DB->query("SELECT id, name FROM exp_upload_prefs ORDER BY name ASC");
		foreach($dls->result as $dl)
		{
			$selected = ($dl['id'] == $data["field_list_items"]) ? " selected=\"true\"" : "";
			$block .= "<option value=\"{$dl['id']}\"$selected>{$dl['name']}</option>";
		}
		$block .= "</select></div></div>";
		$r = preg_replace("/(<div\s*id=.date_block.)/", "$block$1", $r);
		
		return $r;
	}
	
	/**
	 * Process Custom 'New Field' Data
	 *
	 * @access public
	 * @param NULL
	 * @return NULL
	 */
	function edit_publish_ad_post()
	{
		if(isset($_POST['field_type']) && $_POST['field_type'] == 'file' && isset($_POST['field_download_directory']))
		{
			$_POST['field_list_items'] = $_POST['field_download_directory'];
			unset($_POST['field_download_directory']);
		}
		else if(isset($_POST['field_download_directory']))
		{
			unset($_POST['field_download_directory']);
		}
	}
	
	/**
	 * Edit Upload Prefs With New Fields
	 *
	 * @access public
	 * @param NULL
	 * @return NULL
	 */
	function edit_upload_prefs($out)
	{
		global $DB, $EXT, $IN;
		
		if($EXT->last_call !== false)
		{
			$out = $EXT->last_call;
		}
		
		//	=============================================
		//	Only Alter Upload Prefs
		//	=============================================
		if($IN->GBL('M') != 'blog_admin' || $IN->GBL('P') != 'edit_upload_pref')
		{
			return $out;
		}
		
		$id = $IN->GBL('id');
		if(!is_numeric($id)) $id = false;
		
		$this->_set_prefs($id);
		
		//	=============================================
		//	Find Table
		//	=============================================
		preg_match('/name=[\'"]name[\'"].*?<\/table>/si', $out, $table);
		
		//	=============================================
		//	Create Fields
		//	=============================================
		$r = <<<EOT
<style type="text/css" media="screen">
	.mh_edit_upload_pref .label { width:37%; padding-right:20px; }
</style>
EOT;
		$r.= '<br /><table class="tableBorder mh_edit_upload_pref" width="100%" cellspacing="0">';
		$r.= '<tr>';
		$r.= '<th class="tableHeadingAlt" colspan="2" align="left">File Settings</th>';
		$r.= '</tr>';
		
		$r.= '<tr>';
		$r.= '<td class="tableCellOne label"><span class="defaultBold"><label for="mh_file_allow_multiple">Allow Multiple Uploads?</label></span></td>';
		$r.= '<td class="tableCellOne">';
		$r.= '<input type="radio" name="mh_file_allow_multiple" value="yes"'.(($this->allow_multiple)?' checked="checked"':'').' />Yes&nbsp;&nbsp;&nbsp;';
		$r.= '<input type="radio" name="mh_file_allow_multiple" value="no"'.((!$this->allow_multiple)?' checked="checked"':'').' />No</td>';
		$r.= '</tr>';
		
		$r.= '<tr>';
		$r.= '<td class="tableCellTwo label"><span class="defaultBold"><label for="mh_file_show_thumbnails">Show Thumbnails?</label></span></td>';
		$r.= '<td class="tableCellTwo">';
		$r.= '<input type="radio" name="mh_file_show_thumbnails" value="yes"'.(($this->show_thumbnails)?' checked="checked"':'').' />Yes&nbsp;&nbsp;&nbsp;';
		$r.= '<input type="radio" name="mh_file_show_thumbnails" value="no"'.(!($this->show_thumbnails)?' checked="checked"':'').' />No</td>';
		$r.= '</tr>';
		
		$r.= '<tr>';
		$r.= '<td class="tableCellOne label"><span class="defaultBold"><label for="mh_file_clean_filenames">Clean Filenames?</label></span></td>';
		$r.= '<td class="tableCellOne">';
		$r.= '<input type="radio" name="mh_file_clean_filenames" value="yes"'.(($this->clean_filenames)?' checked="checked"':'').' />Yes&nbsp;&nbsp;&nbsp;';
		$r.= '<input type="radio" name="mh_file_clean_filenames" value="no"'.(!($this->clean_filenames)?' checked="checked"':'').' />No</td>';
		$r.= '</tr>';
		
		$r.= '<tr>';
		$r.= '<td class="tableCellTwo label"><span class="defaultBold"><label for="mh_file_rewrite_filenames">Rewrite Filenames?</label></span></td>';
		$r.= '<td class="tableCellTwo">';
		$r.= '<input type="radio" name="mh_file_rewrite_filenames" value="yes"'.(($this->rewrite_filenames)?' checked="checked"':'').' />Yes&nbsp;&nbsp;&nbsp;';
		$r.= '<input type="radio" name="mh_file_rewrite_filenames" value="no"'.(!($this->rewrite_filenames)?' checked="checked"':'').' />No</td>';
		$r.= '</tr>';
		
		$r.= '<tr>';
		$r.= '<td class="tableCellOne label"><span class="defaultBold"><label for="mh_file_resize_images">Resize Images</label></span></td>';
		$r.= '<td class="tableCellOne">';
		$r.= '<select name="mh_file_resize_images">';
		$r.= '<option'.(($this->resize_images=='auto')?' selected="selected"':'').' value="auto">Auto</option>';
		$r.= '<option'.(($this->resize_images=='crop')?' selected="selected"':'').' value="crop">Crop</option>';
		$r.= '<option'.(($this->resize_images=='anchor_width')?' selected="selected"':'').' value="anchor_width">Anchor Width</option>';
		$r.= '<option'.(($this->resize_images=='anchor_height')?' selected="selected"':'').' value="anchor_height">Anchor Height</option>';
		$r.= '<option'.(($this->resize_images=='stretch')?' selected="selected"':'').' value="stretch">Stretch</option>';
		$r.= '<option'.(($this->resize_images=='no')?' selected="selected"':'').' value="no">Do not resize</option>';
		$r.= '</select>';
		$r.= '</tr>';
		
		$r.= '<tr>';
		$r.= '<td class="tableCellTwo label"><span class="defaultBold"><label for="mh_file_keep_originals">Keep Originals?</label></span></td>';
		$r.= '<td class="tableCellTwo">';
		$r.= '<input type="radio" name="mh_file_keep_originals" value="yes"'.(($this->keep_originals)?' checked="checked"':'').' />Yes&nbsp;&nbsp;&nbsp;';
		$r.= '<input type="radio" name="mh_file_keep_originals" value="no"'.(!($this->keep_originals)?' checked="checked"':'').' />No</td>';
		$r.= '</tr>';
		
		$r.= '<tr>';
		$r.= '<td class="tableCellOne label"><span class="defaultBold"><label for="mh_file_delete_from_server">Delete From Server?</label></span></td>';
		$r.= '<td class="tableCellOne">';
		$r.= '<input type="radio" name="mh_file_delete_from_server" value="yes"'.(($this->delete_from_server)?' checked="checked"':'').' />Yes&nbsp;&nbsp;&nbsp;';
		$r.= '<input type="radio" name="mh_file_delete_from_server" value="no"'.(!($this->delete_from_server)?' checked="checked"':'').' />No</td>';
		$r.= '</tr>';
		
		$r.= '</table>';
		
		//	=============================================
		//	Add Fields
		//	=============================================
		$out = str_replace($table[0], $table[0].$r, $out);
		
		return $out;
	}
	
	/**
	 * Set the Class Preferences
	 *
	 * @access public
	 * @param NULL
	 * @return NULL
	 */
	function _set_prefs($id)
	{
		global $DB;
		
		$prefs = $DB->query("SELECT * FROM exp_mh_file WHERE upload_id='{$id}'");
		
		$default_settings = get_class_vars(get_class($this));
		foreach($default_settings as $key=>$value)
		{
			$this->$key = $value;
		}
		
		foreach($prefs->result as $pref)
		{
			$key = $pref['key'];
			$value = $pref['value'];
			
			if(!isset($this->$key)) continue;
			
			$value = ($value === 'yes')?TRUE:$value;
			$value = ($value === 'no')?FALSE:$value;
			$this->$key = $value;
		}
	}
	
	/**
	 * Save Prefs to DB
	 *
	 * @access public
	 * @param NULL
	 * @return NULL
	 */
	function edit_prefs_post()
	{
		global $DB;
		
		if(count($_POST)==0) return;
		
		$id = (isset($_POST['id']) && $_POST['id']!='')?$_POST['id']:FALSE;
		
		if($id === FALSE)
		{
			$table = $DB->query('SHOW TABLE STATUS LIKE "exp_upload_prefs"');
			$id = $table->row['Auto_increment'];
		}
		
		foreach($_POST as $key=>$value)
		{
			if(substr($key, 0, 8) != 'mh_file_') continue;
			
			$key = $DB->escape_str(substr($key, 8));
			$value = $DB->escape_str($value);
			
			$DB->query("INSERT INTO exp_mh_file VALUES('', {$id}, '{$key}', '{$value}') ON DUPLICATE KEY UPDATE id=values(id), `key`=values(`key`), value=values(value)");
			
			unset($_POST['mh_file_'.$key]);
		}
	}
	
	/**
	 * Add 'File' to 'Publish' page
	 *
	 * @access public
	 * @param NULL
	 * @return NULL
	 */
	function publish( $row, $field_data )
	{
		global $DB, $DSP, $EXT, $LANG, $PREFS;
		$LANG->fetch_language_file('mh_file_ext');
		$r = "";
		
		if($row["field_type"] == $this->type)
		{
			//  =============================================
			//  Get Settings
			//  =============================================
			$upload_pref = $DB->query('SELECT * FROM exp_upload_prefs WHERE id="'.$row['field_list_items'].'"');
			$file_field = $upload_pref->row;
			
			//  =============================================
			//  Settings Made?
			//  =============================================
			if($file_field['server_path'] == '')
			{
				return $LANG->line('error_no_server_path');
			}
			
			//  =============================================
			//  Get Settings
			//  =============================================
			$this->_set_prefs($file_field['id']);
			
			//  =============================================
			//  This table fixes a Safari bug.  Kill the
			//  table once Safari has fixed it.
			//  =============================================
			$r.= "<table border='0' cellpadding='0' cellspacing='0' width='100%' style='margin-bottom:0; position:relative;'><tr><td>";
			
			//  =============================================
			//  CSS and JS
			//  =============================================
			if(stristr($DSP->extra_header, "function addRow") === false)
			{
				$DSP->extra_header.= $this->_js();
				$DSP->extra_header.= $this->_css();
			}
			
			//  =============================================
			//  Add Field Data to $_POST
			//  =============================================
			$r.= '<input type="hidden" name="field_id_'.$row['field_id'].'" value="'.htmlentities($field_data).'" />';
			
			//  =============================================
			//  Start Table
			//  =============================================
			$r.= '<table class="file_table tableBorder">';
			
			//  =============================================
			//  Table Headings
			//  =============================================
			$r.= '<tr>';
			$r.= '<td class="tableHeadingAlt">';
			$r.= $LANG->line('file');
			$r.= '</td>';
			$r.= '<td class="tableHeadingAlt" align="right">';
			$r.= $LANG->line('remove');
			$r.= '</td>';
			$r.= '</tr>';
			
			//  =============================================
			//  Get Files
			//  =============================================
			$files = array_filter(preg_split("/[\r\n]+/", trim($field_data)));
			
			//  =============================================
			//  Output Files
			//  =============================================
			foreach($files as $count=>$file)
			{
				$img = '<img src="'.$file_field['url'].$file.'" alt="'.$file.'" width="50" valign="middle" class="icon" />';
				
				//  =============================================
				//  Show Icons
				//  =============================================
				if($this->show_thumbnails === FALSE)
				{
					$extension = preg_replace('/^.*\./', '', $file);
					
					if($extension != '' && is_file(PATH.'..'.$this->icon_path.'/icons/'.$extension.'.png'))
					{
						$img = '<img src="'.$PREFS->ini('site_url').$this->icon_path.'/icons/'.$extension.'.png" alt="'.$extension.'" valign="middle" class="icon" />';
					}
					elseif(is_file(PATH.'..'.$this->icon_path.'/icons/unknown.png'))
					{
						$img = '<img src="'.$PREFS->ini('site_url').$this->icon_path.'/icons/unknown.png" alt="Unknown File Type" valign="middle" class="icon" />';
					}
					else
					{
						$img = '';
					}
				}
				
				//  =============================================
				//  Get Class Name
				//  =============================================
				$class_name = $count%2==0?'tableCellOne':'tableCellTwo';
				
				//  =============================================
				//  Print Row
				//  =============================================
				$r.= '<tr>';
				$r.= '<td class="'.$class_name.'">';
				$r.= $img;
				$r.= '<a href="'.$file_field['url'].$file.'" target="_blank">';
				$r.= $file;
				$r.= '</a>';
				$r.= '</td>';
				$r.= '<td class="'.$class_name.'" align="right">';
				$r.= '<input type="checkbox" name="field_id_'.$row['field_id'].'_remove[]" value="'.$file.'" class="delete" onClick="markForRemove(this);" />';
				$r.= "</td></tr>";
			}
			
			//  =============================================
			//  Display Upload Field?
			//  =============================================
			$display = '';
			if(count($files) != 0 && $this->allow_multiple === FALSE)
			{
				$display = ' style="display:none"';
			}
			
			//  =============================================
			//  Add JavaScript?
			//  =============================================
			$clickAction = '';
			if($this->allow_multiple === TRUE)
			{
				$clickAction = ' onChange="return addRow(this);"';
			}
			
			//  =============================================
			//  Print Field
			//  =============================================
			$r.= '<tr'.$display.'>';
			$r.= '<td class="tableCellTwo" colspan="2">';
			$r.= '<input name="field_id_'.$row['field_id'].'_img[]" type="file"'.$clickAction.' class="file" />';
			$r.= '</td>';
			$r.= '</tr>';
			
			//  =============================================
			//  Close Table
			//  =============================================
			$r.= "</table>";
			
			// Safari
			$r .= "</td></tr></table>";
		}
		else if($EXT->last_call !== false)
		{
			return $EXT->last_call;
		}
		
		return $r;
	}
	
	/**
	 * Process Custom 'File' Data
	 *
	 * @access public
	 * @param array, string
	 * @return NULL
	 */
	function modify_post()
	{
		global $DB, $EE, $EXT, $FNS, $LANG;
		$LANG->fetch_language_file('mh_file_ext');
		
		$errors = array();
		
		// =============================================
		// Loop Through File Fields
		// =============================================
		$file_fields = $DB->query('SELECT f.*, u.* FROM exp_weblog_fields f, exp_upload_prefs u WHERE field_type="file" AND f.field_list_items=u.id');
		foreach($file_fields->result as $file_field)
		{
			//  =============================================
			//  Reset then Get Settings
			//  =============================================
			$this->_set_prefs($file_field['field_list_items']);
		
			//  =============================================
			//  Get the Path
			//  =============================================
			$server_path = $file_field['server_path'];
			if($server_path == FALSE)
			{
				$errors[] = $LANG->line('error_no_server_path');
			}
			else
			{
				$server_path = preg_match("/^\//", $server_path)==0?PATH.$server_path:$server_path;
				$server_path = '/'.preg_replace("/^\/|\/$/", '', $server_path).'/';
			}
			
			//  =============================================
			//  Remove Files
			//  =============================================
			if(isset($_POST['field_id_'.$file_field['field_id'].'_remove']))
			{
				foreach($_POST['field_id_'.$file_field['field_id'].'_remove'] as $key=>$file)
				{
					if($this->delete_from_server === TRUE)
					{
						@unlink($server_path.$file);
					}
					$_POST['field_id_'.$file_field['field_id']] = preg_replace("/".preg_quote($file)."[\r\n]*"."/", "", $_POST['field_id_'.$file_field['field_id']]);
				}
			}
			
			//  =============================================
			//  Did we upload anything?
			//  =============================================
			if(!isset($_FILES['field_id_'.$file_field['field_id'].'_img']['name'])) continue;
			
			//  =============================================
			//  Get Array Pieces
			//  =============================================
			$file_names = $_FILES['field_id_'.$file_field['field_id'].'_img']['name'];
			$file_types = $_FILES['field_id_'.$file_field['field_id'].'_img']['type'];
			$file_tmp_names = $_FILES['field_id_'.$file_field['field_id'].'_img']['tmp_name'];
			$file_errors = $_FILES['field_id_'.$file_field['field_id'].'_img']['error'];
			$file_sizes = $_FILES['field_id_'.$file_field['field_id'].'_img']['size'];
			
			//  =============================================
			//  Filename Empty?
			//  =============================================
			foreach($file_names as $file_key=>$file_name)
			{
				if($file_name == '')
				{
					unset($file_names[$file_key]);
					unset($file_types[$file_key]);
					unset($file_tmp_names[$file_key]);
					unset($file_errors[$file_key]);
					unset($file_sizes[$file_key]);
				}
			}
			
			//	=============================================
			//	Clearn File Names?
			//	=============================================
			if($this->clean_filenames === TRUE)
			{
				foreach($file_names as $file_key=>$file_name)
				{
					$file = $this->_pieces($file_name);
					$file_names[$file_key] = preg_replace(array('/(?:^[^a-zA-Z0-9_-]+|[^a-zA-Z0-9_-]+$)/', '/\s+/', '/[^a-zA-Z0-9_-]+/'), array('', '_', ''), $file['name']).$file['ext'];
				}
			}
			
			//  =============================================
			//  Rewrite (or Code) File Names?
			//  =============================================
			if($this->rewrite_filenames === TRUE)
			{
				foreach($file_names as $file_key=>$file_name)
				{
					$file = $this->_pieces($file_name);
					$file_names[$file_key] = $this->_code().$file['ext'];
				}
			}
			
			//  =============================================
			//  Any php.ini File Size Issues?
			//  =============================================
			foreach($file_errors as $file_key=>$file_error)
			{
				if($file_error == 1)
				{
					$errors[] = str_replace(array('%{field}', '%{file}'), array($file_field['field_label'], $file_names[$file_key]), $LANG->line('error_filesize_ini'));
				}
			}
			
			//  =============================================
			//  Are any of the file sizes too big?
			//  =============================================
			if($this->max_file_size !== FALSE)
			{
				foreach($file_sizes as $file_key=>$file_size)
				{
					if($file_size > $this->max_file_size)
					{
						$errors[] = str_replace(array('%{field}', '%{file}'), array($file_field['field_label'], $file_names[$file_key]), $LANG->line('error_filesize'));
					}
				}
			}
			
			//  =============================================
			//  Are any of the file dimensions too big?
			//  =============================================
			if($this->resize_images === FALSE)
			{
				foreach($file_tmp_names as $file_key=>$file_tmp_name)
				{
					$dimensions = @getimagesize($file_tmp_name);
					if($dimensions === FALSE) continue;
					
					if($file_field['max_width'] != '' && $dimensions[0] > $file_field['max_width'])
					{
						$errors[] = str_replace(array('%{field}', '%{file}'), array($file_field['field_label'], $file_names[$file_key]), $LANG->line('error_filesize_width'));
					}
					if($file_field['max_height'] != '' && $dimensions[1] > $file_field['max_height'])
					{
						$errors[] = str_replace(array('%{field}', '%{file}'), array($file_field['field_label'], $file_names[$file_key]), $LANG->line('error_filesize_height'));
					}
				}
			}
			
			
			
			//  =============================================
			//  Any Files Exist?
			//  =============================================
			foreach($file_names as $file_key=>$file_name)
			{
				if(is_file($server_path.$file_name) !== FALSE)
				{
					if(isset($_POST['auto_rename']) && in_array($file_name, $_POST['auto_rename']))
					{
						$count = 0;
						while(is_file($server_path.$file_name) !== FALSE && ++$count < 1000)
						{
							$file_names[$file_key] = $file_name = preg_replace('/(_\d+)*(\.[^.]+)$/', "_{$count}\\2", $file_name);
						}
						
						if($count == 1000)
						{
							$errors[] = str_replace(array('%{field}', '%{file}'), array($file_field['field_label'], $file_name), $LANG->line('error_file_exists'));
						}
					}
					else
					{
						$errors[] = str_replace(array('%{field}', '%{file}'), array($file_field['field_label'], $file_name), $LANG->line('error_file_exists'));
					}
				}
			}
			
			//  =============================================
			//  Any Errors, Before the Upload?
			//  =============================================
			if(count($errors) > 0)
			{
				if (REQ == 'CP')
				{
					$EE->new_entry_form('preview', '<ul><li>'.implode('</li><li>',array_filter($errors)).'</li></ul>');
				}
				else
				{
					$EE->show_user_error('general', '<ul><li>'.implode('</li><li>',array_filter($errors)).'</li></ul>');
				}
				
				$EXT->end_script = TRUE;
				return;
			}
			
			//  =============================================
			//  Do the Upload
			//  =============================================
			foreach($file_tmp_names as $file_key=>$file_tmp_name)
			{
				if(@move_uploaded_file($file_tmp_name, $server_path.$file_names[$file_key]) === FALSE)
				{
					$errors[] = str_replace(array('%{field}', '%{file}'), array($file_field['field_label'], $file_names[$file_key]), $LANG->line('error_transfer'));
				}
			}
			
			//  =============================================
			//  Any Errors, During the Upload?
			//  =============================================
			if(count($errors) > 0)
			{
				if (REQ == 'CP')
				{
					$EE->new_entry_form('preview', '<ul><li>'.implode('</li><li>',array_filter($errors)).'</li></ul>');
				}
				else
				{
					$EE->show_user_error('general', '<ul><li>'.implode('</li><li>',array_filter($errors)).'</li></ul>');
				}
				
				$EXT->end_script = TRUE;
				return;
			}
			
			//  =============================================
			//  Resize Images
			//  =============================================
			if($this->resize_images !== FALSE && $file_field['max_width'] != '' || $file_field['max_height'] != '')
			{
				foreach($file_names as $file_key=>$file_name)
				{
					
					//  =============================================
					//  Make Sure We're an Image
					//  =============================================
					$dimensions = @getimagesize($server_path.$file_name);
					if($dimensions === FALSE) continue;
					
					$src_x = 0;
					$src_y = 0;
					$src_width = $dimensions[0];
					$src_height = $dimensions[1];
					
					//  =============================================
					//  Get SRC Image
					//  =============================================
					if(($src_img = @imagecreatefromgif($server_path.$file_name)) !== FALSE) {}
					else if(($src_img = @imagecreatefromjpeg($server_path.$file_name)) !== FALSE) {}
					else if(($src_img = @imagecreatefrompng($server_path.$file_name)) !== FALSE) {}
					
					//  =============================================
					//  Able to Open?
					//  =============================================
					if($src_img === FALSE)
					{
						$errors[] = str_replace(array('%{field}', '%{file}'), array($file_field['field_label'], $file_types[$file_key]), $LANG->line('error_filetype'));
						continue;
					}
					
					//  =============================================
					//  Get Version Specific Functions
					//  =============================================
					$create	= 'imagecreatetruecolor';
					$copy	= 'imagecopyresampled';
					
					//  =============================================
					//  Calculate Width/Height
					//  =============================================
					$dst_x = 0;
					$dst_y = 0;
					$dst_width = ($file_field['max_width']!='')?$file_field['max_width']:$dimensions[0];
					$dst_height = ($file_field['max_height']!='')?$file_field['max_height']:$dimensions[1];
					
					//  =============================================
					//  Auto Resize
					//  =============================================
					$resize = $this->resize_images;
					if($this->resize_images == 'auto' && $file_field['max_width'] != '' && $file_field['max_height'] != '')
					{
						$src_proportion = $src_width/$src_height;
						$dst_proportion = $dst_width/$dst_height;
						
						if($src_proportion > $dst_proportion)
						{
							$resize = 'anchor_width';
						}
						else
						{
							$resize = 'anchor_height';
						}
					}
					
					//  =============================================
					//  Crop Images
					//  =============================================
					if($resize == 'crop')
					{
						$src_x = ($src_width/2)-($dst_width/2);
						$src_y = ($src_height/2)-($dst_height/2);
						
						$src_width = $dst_width;
						$src_height = $dst_height;
					}
					
					//  =============================================
					//  Resize to Width
					//  =============================================
					else if($resize == 'anchor_width')
					{
						$dst_height = $dst_width*$src_height/$src_width;
					}
					
					//  =============================================
					//  Resize to Height
					//  =============================================
					else if($resize == 'anchor_height')
					{
						$dst_width = $dst_height*$src_width/$src_height;
					}
					
					//  =============================================
					//  Stretch Images, Happens By Default!
					//  =============================================
					else
					{
						
					}
					
					//  =============================================
					//  Create Destination Image
					//  =============================================
					$dst_img = $create($dst_width, $dst_height);
					
					//  =============================================
					//  Copy SRC to DEST
					//  =============================================
					$copy($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_width, $dst_height, $src_width, $src_height);
					
					//	=============================================
					//	Remove Originals
					//	=============================================
					$suffix = $this->thumb_suffix;
					if($this->keep_originals === FALSE)
					{
						$suffix = '';
						unset($file_names[$file_key]);
						@unlink($server_path.$file_name);
					}
					
					//  =============================================
					//  Calculate DST Path
					//  =============================================
					$dst = $server_path.$this->_add_suffix($file_name, $suffix);
					$file_names[] = $this->_add_suffix($file_name, $suffix);
					
					//  =============================================
					//  Write File
					//  =============================================
					@touch($dst);
					if(@imagegif($dst_img, $dst) !== false) {}
					else if(@imagejpeg($dst_img, $dst) !== false) {}
					else if(@imagepng($dst_img, $dst) !== false) {}
					
					//  =============================================
					//  Write Permissions
					//  =============================================
					@chmod($dst, 0777);
				}
			}
			
			//  =============================================
			//  Set the $_POST
			//  =============================================
			if($_POST['field_id_'.$file_field['field_id']] != '')
			{
				$_POST['field_id_'.$file_field['field_id']] .= "\r";
			}
			$_POST['field_id_'.$file_field['field_id']] .= implode("\r", $file_names);
		}
		
		//  =============================================
		//  Clean Up Post
		//  =============================================
		// unset($_POST['field_id_'.$file_field['field_id'].'_remove']);
		foreach($_POST as $k=>$v)
		{
			if(preg_match('/^field_id_\d+_remove/', $k))
			{
				unset($_POST[$k]);
			}
		}
	}
	
	/**
	 * Split a file to name/suffix
	 *
	 * @access private
	 * @param string, string
	 * @return string
	 */
	function _pieces( $file_name )
	{
		$base = preg_replace('/^(.*)\.[^.]+$/', '\\1', $file_name);
		$ext = preg_replace('/^.*(\.[^.]+$)/', '\\1', $file_name);
		
		return array('name'=>$base, 'ext'=>$ext);
	}
	
	/**
	 * Add Suffix
	 *
	 * @access private
	 * @param string, string
	 * @return string
	 */
	function _add_suffix( $file_name, $suffix )
	{
		$file = $this->_pieces($file_name);
		return $file['name'].$suffix.$file['ext'];
	}
	
	/**
	 * Generate Random Code
	 *
	 * @access private
	 * @param NULL
	 * @return string
	 */
	function _code()
	{
		return md5(date('U'));
	}
	
	/**
	 * Add 'File' to Template
	 *
	 * @access public
	 * @param string, array
	 * @return string
	 */
	function modify_template( $tagdata, $row )
	{
		global $DB, $FNS, $TMPL, $EXT, $SESS;
		global $all_fields, $file_fields;
		
		if($EXT->last_call !== false)
		{
			$tagdata = $EXT->last_call;
		}
		
		if(!isset($SESS->cache)) $SESS->cache = array();
		if(!isset($SESS->cache['mh_file_ext_fields']))
		{
			$SESS->cache['mh_file_ext_fields'] = $DB->query('SELECT * FROM exp_weblog_fields f, exp_upload_prefs p WHERE f.field_type="file" AND p.id=f.field_list_items');
		}
		
		//	=============================================
		//	Loop Through Fields
		//	=============================================
		foreach($SESS->cache['mh_file_ext_fields']->result as $file_field)
		{
			//	=============================================
			//	Does the field exist?
			//	=============================================
			if(!isset($row['field_id_'.$file_field['field_id']]))
			{
				$tagdata = $this->clean_tagdata($tagdata, $file_field['field_name']);
				continue;
			}
			
			//	=============================================
			//	Are there files?
			//	=============================================
			if(trim($row['field_id_'.$file_field['field_id']]) == '')
			{
				$tagdata = $this->clean_tagdata($tagdata, $file_field['field_name']);
				continue;
			}
			
			//	=============================================
			//	Split out to array
			//	=============================================
			//	We'll also want to clear our empty rows and
			//	reset our indexes to 0, that's what the three
			//	calls do.
			//	---------------------------------------------
			$files = array();
			$thumbs = array();
			$all_files = array_merge(array_filter(preg_split('/\s*[\r\n]+\s*/', $row['field_id_'.$file_field['field_id']])));
			foreach($all_files as $file)
			{
				if(preg_match('/'.$this->thumb_suffix.'\.[^.]*$/', $file))
				{
					$thumbs[] = $file;
				}
				else
				{
					$files[] = $file;
				}
			}
			
			//	=============================================
			//	Are there still files or thumbs?
			//	=============================================
			if(count($files) === 0 && count($thumbs) === 0)
			{
				$tagdata = $this->clean_tagdata($tagdata, $file_field['field_name']);
				continue;
			}
			
			//  =============================================
			//  Reset then Get Settings
			//  =============================================
			$this->_set_prefs($file_field['field_list_items']);
			
			//	=============================================
			//	Store Field Name
			//	=============================================
			//	This will make later stuff much shorter.
			//	---------------------------------------------
			$field_name = $file_field['field_name'];
			
			//	=============================================
			//	Set Up Conditionals
			//	=============================================
			$variables = array();
			$variables[$field_name] = implode(', ', $files);
			$variables[$field_name.$this->thumb_suffix] = implode(', ', $thumbs);
			$tagdata = $TMPL->array_conditionals($tagdata, $variables);
			
			//	=============================================
			//	Replace Out Multi-Fields
			//	=============================================
			preg_match_all('/'.LD.$field_name.'(.*?)'.RD.'(.*?)'.LD.SLASH.$field_name.RD.'/s', $tagdata, $tagchunk);
			foreach($tagchunk[2] as $chunk_key=>$raw_chunk)
			{
				$return_chunk = '';
				$parameters = $FNS->assign_parameters($tagchunk[1][$chunk_key]);
				$total_results = count($files);
				
				foreach($files as $count=>$file)
				{
					$thumb = $this->_add_suffix($file, $this->thumb_suffix);
					if(in_array($thumb, $thumbs) !== FALSE)
					{
						$thumb_path = $file_field['server_path'].'/'.$thumb;
						$thumb_url = is_file($thumb_path)?$FNS->remove_double_slashes($file_field['url'].'/'.$thumb):'';
					}
					else
					{
						$thumb_path = '';
						$thumb_url = '';
					}
					
					$var = array(
						'count' => $count+1,
						'total_results' => $total_results,
						'file_name' => $file,
						'file_url' => $FNS->remove_double_slashes($file_field['url'].'/'.$file),
						'file'.$this->thumb_suffix.'_name' => $thumb,
						'file'.$this->thumb_suffix.'_url' => $thumb_url
					);
					
					$str = $FNS->prep_conditionals($raw_chunk, $var);
					
					$var = array_flip($var);
					$var = array_map(create_function('$x', 'return LD.$x.RD;'), $var);
					$var = array_flip($var);
					
					$return_chunk.= str_replace(array_keys($var), $var, $str);
				}
				
				if(isset($parameters['backspace']) && is_numeric($parameters['backspace']))
				{
					$return_chunk = preg_replace('/\s+$/s', '', $return_chunk);
					$return_chunk = str_replace(array('&#47;'), array('/'), $return_chunk);
					$return_chunk = substr($return_chunk, 0, strlen($return_chunk)-$parameters['backspace']);
				}
				
				$tagdata = str_replace($tagchunk[0][$chunk_key], $return_chunk, $tagdata);
			}
			
			//	=============================================
			//	Replace Out Single-Fields
			//	=============================================
			if(isset($files[0]))
			{
				$file_path = $FNS->remove_double_slashes($file_field['url'].'/'.$files[0]);
				$tagdata = preg_replace('/'.LD.$field_name.RD.'/', $file_path, $tagdata);
			}
			
			if(isset($thumbs[0]))
			{
				$file_path = $FNS->remove_double_slashes($file_field['url'].'/'.$thumbs[0]);
				$tagdata = preg_replace('/'.LD.$field_name.$this->thumb_suffix.RD.'/', $file_path, $tagdata);
			}
		}
		
		return $tagdata;
	}
	
	function clean_tagdata($tagdata, $field_name)
	{
		$tagdata = preg_replace('/'.LD.$field_name.'(.*?)'.RD.'(.*?)'.LD.SLASH.$field_name.RD.'/s', '', $tagdata);
		$tagdata = preg_replace('/'.LD.$field_name.RD.'/', '', $tagdata);
		$tagdata = preg_replace('/'.LD.$field_name.$this->thumb_suffix.RD.'/', '', $tagdata);
		
		return $tagdata;
	}
	
	/**
	 * JS
	 *
	 * @access private
	 * @param NULL
	 * @return string
	 */
	function _js()
	{
		return '
<script type="text/javascript" charset="utf-8">
function addRow( self )
{
	if(typeof(self) != "object") self = this;
	if(!self.nodeName || self.nodeName != "INPUT") self = this;

	var tr = document.createElement("tr");
	var td = document.createElement("td");
		td.className = "tableCellTwo";
		td.colSpan = 2;
		tr.appendChild(td);

	var input = document.createElement("input");
	if(self.onchange) input.onchange = addRow;
	input.type = "file";
	input.name = self.name;

	td.appendChild(input);
	self.parentNode.parentNode.parentNode.appendChild(tr);

	return false;
}
function markForRemove( self )
{
	if(typeof(self) != "object") self = this;
	if(!self.nodeName || self.nodeName != "INPUT") self = this;

	if(self.checked == true)
	{
		self.parentNode.parentNode.className = "deleted";
	}
	else
	{
		self.parentNode.parentNode.className = "";
	}

	var file_input = false;
	var checkboxes = 0;
	var inputs = self.parentNode.parentNode.parentNode.getElementsByTagName("input");
	for(var i=0; i<inputs.length; i++)
	{
		if(inputs[i].type == "checkbox")
		{
			checkboxes++;
		}
		else if(inputs[i].type == "file")
		{
			file_input = inputs[i];
		}
	}

	if(checkboxes == 1 && file_input != false)
	{
		if(file_input.parentNode.parentNode.style.display == "none")
		{
			file_input.quick_show = true;
			if(document.all) file_input.parentNode.parentNode.style.display = "block";
			else file_input.parentNode.parentNode.style.display = "table-row";
		}
		else if(file_input.parentNode.parentNode.style.display != "none" && file_input.quick_show == true)
		{
			if(document.all) file_input.parentNode.parentNode.style.display = "none";
			else file_input.parentNode.parentNode.style.display = "none";
		}
	}
}

var loader = document.createElement("img");
loader.src = "'.$this->icon_path.'/icons/upload.gif";
loader.alt = "Uploading...";
function uploadFiles()
{
	var inputs = document.forms[0].getElementsByTagName("input");
	for(var index=0; len=inputs.length,index<len; ++index)
	{
		var input = inputs[index];
		if(input.getAttribute("type").toLowerCase() != "file") continue;
		if(input.value == "") continue;
		
		input.parentNode.appendChild(loader.cloneNode());
		
		input.style.position = "absolute";
		input.style.left = "-9999px";
	}
	
	return true;
}
</script>
';
	}
	
	/**
	 * CSS
	 *
	 * @access private
	 * @param NULL
	 * @return string
	 */
	function _css()
	{
		return '
<style type="text/css" media="screen">
	.icon { margin-right:5px; }
	.file_table { border-collapse:collapse; width:45%; }
	tr.deleted td,
	tr.deleted td * { color:#999 !important; text-decoration:line-through; }
</style>
';
	}
}

?>
<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2007 EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: core.messages.php
-----------------------------------------------------
 Purpose: Private Messages
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Messages {

	// URL Writing
	var $allegiance			= 'cp';				// Side of the divide: cp or user
	var $CP					= FALSE;			// CP Object
	var $base_url			= '';				// Base URL used throughout
	var $form_url			= '';				// For CP Forms, since Rick was a doofus and changed how they work
	var $path				= 'member/';		// User Side Path
	var $request			= 'inbox';			// User Side Request
	var $cur_id				= '';				// User Side ID, if any
	var $theme_class		= 'profile_theme';
	var $images_folder		= '';				// Location of Forum Images

	// Member Specific
    var $member_id			= '';
    var $private_messages	= '0';				// Number of unread private messages
    var $block_tracking		= 'n';				// Block Sender Tracking
    	
    // Member Group Specific
    var $allow_pm			= 'y';				// Allowed to PM?
    var $attach_allowed		= 'y';				// Attachments allowed?
    
    // Private Message Preferences
    var $storage_limit		= 60;				// Limit for messages to store per user (does not count deleted)
    var $send_limit			= 20;				// Limit for messages sent a day
    var $upload_path		= '';				// Upload path for files
    var $attach_maxsize		= 250;				// Max size for attachments (KB)
    var $attach_total		= 100;				// Maximum amount for all PM attachments (MB)
    var $html_format		= 'safe';			// HTML Formatting?
    var $auto_links			= 'y';				// Auto convert URLs to links
    var $max_chars			= 6000;				// Maximum number of characters in a messages
    var $max_attachments	= 1;				// Maximum number of attachments per message
    
    // User Data Variables
    var $total_messages		= '';				// Total Store Messages for User
    var $current_folder		= '1';				// If any...
    var $folders			= array();			// Folders for User
    var $hide_preview		= FALSE;			// Whether to show the Preview of Message
    var $attachments		= array();			// Attachments for current message
    var $baddies			= array();			// Blocked List; IDs only
    var $goodies			= array();			// Buddies List; IDs only
    var $blocked			= FALSE;			// Blocked List
    var $buddies			= FALSE;			// Buddies List
    var $invalid_name		= FALSE;			// Invalid name submitted?
    
    // Menu Content
    var $menu_items			= array();			// Abstracted data for creating menu
    var $menu				= '';				// Menu fully formed
    
    // Processing and Returned Data
    var $title				= '';				// Title of Page
    var $crumb				= '';				// Crumb text for page
    var $return_data		= '';				// Output data
    var $header_javascript  = '';				// User Side Header JavaScript
    var $error				= '';				// Submission Error	
    var $single_parts		= array();			// Parts of a page: text, form, images, content
    var $conditionals		= array();			// Conditionals
    var $mimes				= '';
    
    // Changeable Class Variables
    var $default_folders	= array('Inbox', 'Sent');
    var $max_folders		= 10;				// Maximum number of folders per user
    var $per_page			= 25;				// Messages on a Folder's Page
    var $graph_width		= '300';			// Width of Total Messages Graph
    var $emoticons_per_row  = 5;				// Number of Images Per Table Row
	var $delete_expiration	= 30;				// Erase deleted messages after X days
	var $disable_emoticons  = 'n';				// Disable the showing of emoticons
	var $spellcheck_enabled = TRUE;				// Enabled Spellcheck?

    /** -----------------------------------
    /**  Constructor
    /** -----------------------------------*/

    function Messages()
    {
        global $IN, $LANG, $FNS, $PREFS, $SESS;
        
        /** -----------------------------------
        /**  A Few Things to Define, Batman
        /** -----------------------------------*/
        
        $this->member_id	  = $SESS->userdata['member_id'];
        $this->allow_pm		  = ($SESS->userdata['group_id'] == '1') ? 'y' : $SESS->userdata['can_send_private_messages'];
        $this->allow_pm		  = ($this->allow_pm == 'y' && $SESS->userdata['accept_messages'] == 'y') ? 'y' : 'n';
        
        $this->attach_allowed = $SESS->userdata('can_attach_in_private_messages');
        
		$this->storage_limit	= ($SESS->userdata['group_id'] == '1') ? 0 : $SESS->userdata['prv_msg_storage_limit'];
		$this->send_limit		= $SESS->userdata['prv_msg_send_limit'];

        if ( ! defined('AMP')) define('AMP', '&amp;');
        if ( ! defined('BR'))  define('BR',  '<br />');
        if ( ! defined('NL'))  define('NL',  "\n");
        if ( ! defined('NBS')) define('NBS', "&nbsp;");
        
		$prefs = array( 'prv_msg_attach_maxsize',
						'prv_msg_attach_total',
						'prv_msg_html_format',
						'prv_msg_auto_links',
						'prv_msg_max_chars',
						'prv_msg_max_attachments'
						);
						
		for($i=0, $t = sizeof($prefs); $i < $t; ++$i)
		{
			if (FALSE !== ($value = $PREFS->ini($prefs[$i])))
			{
				$name = str_replace('prv_msg_', '', $prefs[$i]);
				
				$this->{$name} = $value;
			}
        }       

		$this->upload_path	 = $PREFS->ini('prv_msg_upload_path', TRUE);
                
        // -----------------------------------
        //  Nearly every page requires this, 
        //  so just load it for all of them
        // -----------------------------------
        
		$LANG->fetch_language_file('messages');
		
		$this->title = $LANG->line('private_messages');
		$this->crumb = $LANG->line('private_messages');
		
		$this->images_folder = $PREFS->ini('theme_folder_url', TRUE).'cp_global_images/';
		
		$this->single_parts['path']['image_url'] = $this->images_folder;
		
		/** -----------------------------------
		/**  Maintenance
		/** -----------------------------------*/
		
		srand(time());
	
		if ((rand() % 100) < 5) 
		{  
			$this->maintenance();
		}
    }
    /* END */
    
    /** -----------------------------------
	/**  Determine request
	/** -----------------------------------*/
		
    function _determine_request()
    {
    	global $IN;
    	
		if ($this->allegiance == 'cp')
		{
			$this->base_url = BASE.AMP.'C=myaccount'.AMP.'M=messages'.AMP.'P=';
			$this->form_url = 'C=myaccount'.AMP.'M=messages'.AMP.'P=';
			$this->request = ($IN->GBL('P') !== FALSE) ? $IN->GBL('P') : 'inbox';
		}
		else
		{
			$this->form_url = $this->base_url;
		}
    }
    /* END */
    
    
    
	/** -----------------------------------
    /**  Create Path
    /** -----------------------------------*/

    function _create_path($uri='', $hidden='')
    {
    	if ($this->allegiance == 'user')
    	{
    		return $this->base_url.$uri.'/';
    	}
    	else
    	{
    		return $this->base_url.$uri.$hidden;
    	}
    }
    /* END */
    

 	// -----------------------------------
    //  Process Page :
    //  Convert and Template into Content
    // -----------------------------------   

    function _process_template($template, $data = '')
    {
    	global $LANG;
    	
    	/** -------------------------------
    	/**  Process Conditionals
    	/** -------------------------------*/
    	
    	if (sizeof($this->conditionals) > 0)
    	{
    		foreach($this->conditionals as $key => $value)
    		{
    			if ($value == 'y')
    			{
    				$template = preg_replace("/\{if\s+".$key."\}(.+?)\{\/if\}/si", "\\1", $template);
    				$template = preg_replace("/\{if\s+not_".$key."\}(.+?)\{\/if\}/si", '', $template);
    			}
    			else
    			{
    				$template = preg_replace("/\{if\s+".$key."\}.+?\{\/if\}/si", '', $template);
    				$template = preg_replace("/\{if\s+not_".$key."\}(.+?)\{\/if\}/si", "\\1", $template);
    			}
    		}
    	}
    	
    
    	/** --------------------------
    	/**  Process any sent data
    	/** --------------------------*/
    	
    	if (is_array($data) && sizeof($data) > 0)
    	{
    		foreach($data as $key => $value)
    		{
    			$template = str_replace(LD.$key.RD, $value, $template);
    		}
    	}
    	
    	/** -----------------------------
    	/**  Process any universal data
    	/** -----------------------------*/
    	
    	if (sizeof($this->single_parts) > 0)
    	{
    		foreach($this->single_parts as $key => $value)
    		{
    			if (is_array($value) && sizeof($value) > 0)
    			{
    				foreach($value as $key2 => $value2)
    				{
    					if (is_array($value2) && sizeof($value2) > 0)
    					{
    						foreach($value2 as $key3 => $value3)
    						{
    							$template = str_replace(LD.$key.':'.$key2.':'.$key3.RD, $value3, $template);
    						}
    					}
    					else
    					{
    						$template = str_replace(LD.$key.':'.$key2.RD, 
    												 ($key == 'input' && $key2 != 'body' && $key2 != 'folder_name') ? htmlspecialchars($value2, ENT_QUOTES) : $value2, 
    												 $template);	
    					}
    				}
    			}
    			elseif( ! is_array($value))
    			{
    				if ($key != 'title' && ! stristr($value, '<option')) $value = htmlspecialchars($value, ENT_QUOTES);  // {title} is link title for message menu
    				
    				$template = str_replace(LD.$key.RD, $value, $template);
    			}
    		}
    	}
    	
    	/** -------------------------------------
    	/**  Finally, process all language text
    	/** -------------------------------------*/
    	
    	if (isset($LANG->language) && is_array($LANG->language) && sizeof($LANG->language) > 0)
    	{
    		foreach($LANG->language as $key => $value)
    		{
    			$template = str_replace(LD.'lang:'.$key.RD, $value, $template);
    		}
    	}
    	
    	return $template;
 	}
 	/* END */

    
    
	/** -----------------------------------
    /**  Manage Request
    /** -----------------------------------*/

    function manager()
    {
    	global $PREFS;
    	    	
    	if ($this->allow_pm != 'y')
    	{
    		return;
    	}
    	
    	$this->_determine_request();
    	
    	if (sizeof($this->folders) == 0)
		{
			$this->fetch_folders();
		}
		
		if ($this->disable_emoticons == 'y')
		{
			$PREFS->core_ini['enable_emoticons'] = 'n';
		}
	
       	/** -----------------------------------
		/**  Call request
		/** -----------------------------------*/
		
        if (method_exists($this, $this->request))
        {	
        	$this->{$this->request}();
        }
        else
        {
        	$this->inbox();
        }
        
        /* -----------------------------------
        /*  Member Module - Fixeroo
        /*  - Rick has some legacy code in the member module that converts
        /*  certain path variables automatically.  We, however, do not want that
        /*  in our most precious Private Messages, and so we do a bit of conversion 
        /* -----------------------------------*/
        
		$this->return_data = preg_replace("/".LD."\s*path=(.*?)".RD."/", '&#123;path=\\1}', $this->return_data);
		$this->return_data = preg_replace("#".LD."\s*(profile_path\s*=.*?)".RD."#", '&#123;\\1}', $this->return_data);
		
		/** -----------------------------------
        /**  Name to ID in Form Switch - Fixeroo
        /** -----------------------------------*/
        
        $this->return_data = str_replace('opener.document.submit_message.', "opener.document.getElementById('submit_message').", $this->return_data);
    }
    /* END */
    
    
    /** -----------------------------------
    /**  Fetch Buddy and Block Lists
    /** -----------------------------------*/
    
    function fetch_lists($which='')
    {
    	global $DB;
    	
    	if ($which == 'buddy')
    	{
    		$this->goodies = array();
    		$this->buddies = array();
    	}
    	elseif($which == 'blocked')
    	{
    		$this->baddies = array();
    		$this->blocked = array();
    	}
    	else
    	{
    		$this->goodies = array();
    		$this->buddies = array();
    		$this->baddies = array();
    		$this->blocked = array();
    	}
    	
    	$extra = ($which != '') ? "AND l.listed_type = '{$which}'" : '';
    	
    	$query = $DB->query("SELECT l.*, m.username, m.screen_name, m.member_id 
    						 FROM exp_message_listed l, exp_members m
    						 WHERE l.listed_member = m.member_id
    						 AND l.member_id = '{$this->member_id}' 
    						 $extra");
    						 
    	if ($query->num_rows > 0)
    	{
    		foreach($query->result as $row)
    		{	
    			if (empty($row['username']))
    			{
    				continue;
    			}
    			
    			if ($row['listed_type'] == 'buddy')
    			{
    				$this->buddies[] = array($row['listed_member'], $row['username'], $row['screen_name'], $row['listed_description'], $row['listed_id'], $row['member_id']);
    				$this->goodies[] = $row['listed_member'];
    			}
    			else
    			{
    				$this->blocked[] = array($row['listed_member'], $row['username'], $row['screen_name'], $row['listed_description'], $row['listed_id'], $row['member_id']);
    				$this->baddies[] = $row['listed_member'];
    			}
    		}
    	}
    }
    /* END */
    
    
    /** -----------------------------------
    /**  Determine Folders for this User
    /** -----------------------------------*/
    
    function fetch_folders()
    {
    	global $DB, $REGX;
    	
    	$this->folders = array();
    	
    	$query = $DB->query("SELECT * FROM exp_message_folders WHERE member_id = '{$this->member_id}'");
    	
    	if ($query->num_rows == 0)
    	{
    		$DB->query($DB->insert_string('exp_message_folders', array('member_id' => $this->member_id)));
    		
    		$this->folders['1'] = array($this->default_folders['0'], '0');
    		$this->folders['2'] = array($this->default_folders['1'], '0');
    	}
    	else
    	{	
    		$required = array('1' => array($query->row['folder1_name'], '0'), '2' => array($query->row['folder2_name'], '0'));
    		
    		for($i=3; $i <= $this->max_folders; $i++)
    		{
    			$this->folders[$i] = htmlspecialchars($query->row['folder'.$i.'_name'], ENT_QUOTES);
    		}
    		
    		asort($this->folders);
    		
    		foreach($this->folders as $key => $value)
    		{
    			$this->folders[$key] = array($value, '0');
    		}
    		
    		$this->folders = $required + $this->folders;
    		
    		$results = $DB->query("SELECT COUNT(*) AS count, message_folder FROM exp_message_copies 
    							   WHERE recipient_id = '{$this->member_id}' 
    							   AND message_deleted = 'n'
    							   GROUP BY message_folder");
		
    		if ($results->num_rows > 0)
    		{
    			foreach($results->result as $row)
    			{
    				$this->folders[$row['message_folder']]['1'] = $row['count'];
    			}
    		}
    	}    
    }
    /* END */
    
    
	/** -----------------------------------
    /**  Edit Folders
    /** -----------------------------------*/
    
    function folders()
    {	
    	global $LANG, $FNS;
    	
    	$template = $this->retrieve_template('message_edit_folders');
    	$rows	  = $this->retrieve_template('message_edit_folders_row');
    	
    	$form_details = array('action'	=> $this->base_url.'edit_folders',
    					 	  'id'		=> 'edit_folders',
    					 	  'secure'	=> ($this->allegiance == 'cp') ? FALSE : TRUE
    					 	  );  	
    	
    	$this->single_parts['form']['form_declaration']['edit_folders'] = $FNS->form_declaration($form_details);
    	$this->single_parts['include']['current_folders'] = '';
    	$this->single_parts['include']['new_folder']	  = '';
    	
    	if ( ! isset($this->single_parts['include']['success_message']))
    	{
    		$this->single_parts['include']['success_message'] = '';
    	}
    	
    	/** -----------------------------------------
    	/**  Create Folder Rows
    	/** -----------------------------------------*/
    	
    	$t=1;
    	
    	foreach($this->folders as $key => $value)
    	{
    		if ($value['0'] == '')
    		{
    			continue;
    		}
    		
    		$t++;
    		$this->single_parts['lang']['required']		= ($key < 3) ? $LANG->line('folder_required') : '';
    		$this->single_parts['input']['folder_name']	= $value['0'];
    		$this->single_parts['input']['folder_id']	= $key;
    		$this->single_parts['style']				= ($t % 2) ? 'tableCellOne' : 'tableCellTwo';
    			
    		$this->single_parts['include']['current_folders'] .= $this->_process_template($rows);
    	}
    	
    	/** -----------------------------------------
    	/**  Create New Folder Row, If Allowed
    	/** -----------------------------------------*/
    	
    	if ($t <= $this->max_folders)
    	{
    		$t++;
    		$this->single_parts['lang']['required']		= '';
    		$this->single_parts['input']['folder_name']	= '';
    		$this->single_parts['input']['folder_id']	= 'new';
    		$this->single_parts['style']				= ($t % 2) ? 'tableCellOne' : 'tableCellTwo';
    		
    		$this->single_parts['include']['new_folder'] = $this->_process_template($rows);
    	}
    	
    	/** ----------------------------------------
		/**  Return the Folder's Contents
		/** ----------------------------------------*/
		
		$this->title = $LANG->line('edit_folders');
		$this->crumb = $LANG->line('edit_folders');
		$this->return_data = $this->_process_template($template);
    }
    /* END */
    
    
    
	/** -----------------------------------
    /**  Edit Folders
    /** -----------------------------------*/
    
    function edit_folders()
    {	
    	global $REGX, $DB, $LANG;
    	
    	/** -----------------------------------
    	/**  Check Required
    	/** -----------------------------------*/
    	
    	if ( ! isset($_POST['folder_1']) OR  ! isset($_POST['folder_2']) OR $_POST['folder_1'] == ''  OR $_POST['folder_2'] == '')
    	{
    		return $this->_error_page('missing_required_field');
    	}
    	
    	/** -----------------------------------
    	/**  Get Our Modified Data
    	/** -----------------------------------*/
    	
    	for($i=1, $data = array(); $i <= $this->max_folders; $i++)
    	{
    		if ( ! isset($_POST['folder_'.$i]) OR $_POST['folder_'.$i] == '')
    		{
    			$data['folder'.$i.'_name'] = '';
    			$DB->query("UPDATE exp_message_copies SET message_deleted = 'y' 
    						WHERE recipient_id = '{$this->member_id}' AND message_folder = '{$i}'");
    						
    			if ( ! isset($empty)) $empty = 'folder'.$i.'_name';
    		}
    		else
    		{
    			$data['folder'.$i.'_name'] = $REGX->xss_clean($_POST['folder_'.$i]);
    		}
    	}
    	
    	/** -----------------------------------
    	/**  Get Our New Folder
    	/** -----------------------------------*/
    	
    	if (isset($_POST['folder_new']) && $_POST['folder_new'] != '' && isset($empty))
    	{
    		$data[$empty] = $REGX->xss_clean($_POST['folder_new']);
    	}
    	
    	$DB->query($DB->update_string('exp_message_folders', $data, "member_id = '{$this->member_id}'"));
    	
    	$this->fetch_folders();
    	
    	/** -----------------------------------
    	/**  Success Message
    	/** -----------------------------------*/
    	
    	$this->single_parts['lang']['message'] = $LANG->line('folders_updated');
    	$this->single_parts['include']['success_message'] = $this->_process_template($this->retrieve_template('message_success'));
    	
    	/** ----------------------------------------
		/**  Return us back to Oz
		/** ----------------------------------------*/
		
		return $this->folders();
    }
    /* END */
    
    
    
	/** -----------------------------------
    /**  Abstracted Default Menu
    /** -----------------------------------*/

    function abstract_menu()
    {
    	global $LANG, $DB, $SESS, $LOC;
    	
    	/** ------------------------------
    	/**  Bulletin Board
    	/** ------------------------------*/
    	
    	$style = ($SESS->userdata['last_view_bulletins'] <= $SESS->userdata['last_bulletin_date']) ? 'defaultBold' : '';
    	
    	$this->menu_items['single_items']['bulletin_board'] = array('text' => $LANG->line('bulletin_board'),
    														    	'link' => $this->_create_path('bulletin_board'),
    														    	'image' => '',
    														    	'style' => $style);
    	
    	/** ------------------------
    	/**  Compose New Message
    	/** ------------------------*/
    	
    	$this->menu_items['single_items']['compose_message'] = array('text' => $LANG->line('compose_message'),
    					 											 'link' => $this->_create_path('compose'),
    					 											 'image' => '',
    																 'style' => 'defaultBold');
    																
    																
    	/** ------------------------
    	/**  Drafts Folder
    	/** ------------------------*/
    	
    	$query = $DB->query("SELECT COUNT(*) AS count FROM exp_message_data 
    						 WHERE sender_id = '{$this->member_id}' 
    						 AND message_status = 'draft'");
    	
    	if ($query->row['count'] > 0)
    	{
    		$this->menu_items['repeat_items']['folders'][] = array('text' => $LANG->line('draft_messages') .' ('.$query->row['count'].')',
    															   'link' => $this->_create_path('drafts'),
    															   'image' => '',
    															   'style' => '');
    	}
    	
    	
    	/** -------------------------------
    	/**  User Folders
    	/** -------------------------------*/
    	
    	if (sizeof($this->folders) == 0)
    	{
    		$this->fetch_folders();
    	}
    	
    	foreach($this->folders as $key => $value)
    	{
    		if ($value['0'] != '')
    		{
    			if ($this->allegiance == 'user')
    			{
    				$url = $this->base_url.'view_folder/'.$key.'/';
    			}
    			else
    			{
    				$url = $this->base_url.'view_folder'.AMP.'folder='.$key;
    			}
    		
    			$this->menu_items['repeat_items']['folders'][] = array('text' => ' - '.$value['0'].' ('.$value['1'].')',
    																  'link' => $url,
    																  'image' => '',
    																  'style' => '');
    		}
    	}
    	
    	/** ------------------------
    	/**  Deleted Folder
    	/** ------------------------*/
    	
    	$query = $DB->query("SELECT COUNT(*) AS count FROM exp_message_copies 
    						 WHERE recipient_id = '{$this->member_id}'
    						 AND message_deleted = 'y'");
    	
    	$this->menu_items['repeat_items']['folders'][] = array('text' => $LANG->line('deleted_messages').' ('.$query->row['count'].')',
    														   'link' => $this->_create_path('deleted'),
    														   'image' => '',
    														   'style' => '');
    	
    	/* ------------------------
    	// Message Tracking
    	// ------------------------
    	
    	$this->menu_items['single_items']['track_messages'] = array('text' => $LANG->line('track_messages'),
    															    'link' => $this->_create_path('track'),
    															    'image' => '',
    															    'style' => '');
    	*/
    	/** ------------------------
    	/**  Edit Message Folders
    	/** ------------------------*/
    	
    	$this->menu_items['single_items']['edit_folders'] = array('text' => $LANG->line('edit_folders'),
    															  'link' => $this->_create_path('folders'),
    															  'image' => '',
    															  'style' => '');
    	
    	/** ------------------------
    	/**  Buddy List
    	/** ------------------------*/

    	$this->menu_items['single_items']['buddy_list'] = array('text' => $LANG->line('buddy_list'),
    														    'link' => $this->_create_path('buddies'),
    														    'image' => '',
    														    'style' => '');
    	
    	/** ------------------------
    	/**  Block List
    	/** ------------------------*/
    	
    	$this->menu_items['single_items']['block_list'] = array('text' => $LANG->line('blocked_list'),
    														    'link' => $this->_create_path('blocked'),
    														    'image' => '',
    														    'style' => '');
    
 	}
 	/* END */


	/** -----------------------------------
    /**  Create Messages Menu
    /** -----------------------------------*/

    function create_menu()
    {
    	global $DB, $DSP, $IN, $LANG;
    	
    	if ($this->allow_pm == 'n')
    	{
    		return;
    	}
    	
    	$this->_determine_request();
    	
    	$this->abstract_menu();
    	
    	/** --------------------------------
    	/**  Open/Close JavaScript
    	/** --------------------------------*/
    	
    	if ($IN->GBL('myaccount_messages', 'COOKIE') && $IN->GBL('myaccount_messages', 'COOKIE') == 'on')
    	{
    		$text		  = '[-]';
    		$hidden_style = '';
    	}
    	else
    	{
    		$text		  = '[+]';
    		$hidden_style = 'display: none; padding:0;';
    	}
    		
    	$hidden_link = '<a href="javascript:void(0);" id="extLink1" class="altLinks" onclick="showHide(1,this);return false;">'.$text.'</a>';
    	
    	/** --------------------------------
    	/**  Create Menu
    	/** --------------------------------*/
    	
    	$map = array('bulletin_board', 'compose_message', 'folders', 'edit_folders', 'buddy_list', 'block_list');
    	
    	if ($this->allegiance == 'cp')
    	{	
    		/** --------------------------------
    		/**  Menu Section + JavaScript
    		/** --------------------------------*/
    		
			$expand		= '<img src="'.PATH_CP_IMG.'expand.gif" border="0"  width="10" height="10" alt="Expand" />&nbsp;&nbsp;';
			$collapse	= '<img src="'.PATH_CP_IMG.'collapse.gif" border="0"  width="10" height="10" alt="Collapse" />&nbsp;&nbsp;';		
    		    		    	
			$pm_state = ($IN->GBL('M') == 'messages') ? TRUE : FALSE;
			
			$this->menu  .= '<div id="menu_pm_h" style="display: '.(($pm_state == TRUE) ? 'none' : 'block').'; padding:0; margin: 0;">';			
			$js = ' onclick="showhide_menu(\'menu_pm\');return false;" onMouseover="navTabOn(\'pmx\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onMouseout="navTabOff(\'pmx\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
			$this->menu .= $DSP->div();
			$this->menu .= "<div class='tableHeadingAlt' id='pmx' ".$js.">";
			$this->menu .= $expand.$LANG->line('private_messages');
			$this->menu .= $DSP->div_c();
			$this->menu .= $DSP->div_c();
			$this->menu .= $DSP->div_c();
			 
			$this->menu .= '<div id="menu_pm_b" style="display: '.(($pm_state == TRUE) ? 'block' : 'none').'; padding:0; margin: 0;">';
			
			$js = ' onclick="showhide_menu(\'menu_pm\');return false;" onMouseover="navTabOn(\'pmx2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" onMouseout="navTabOff(\'pmx2\', \'tableHeadingAlt\', \'tableHeadingAltHover\');" ';
			$this->menu .= $DSP->div();
			$this->menu .= "<div class='tableHeadingAlt' id='pmx2' ".$js.">";
			$this->menu .= $collapse.$LANG->line('private_messages');
			$this->menu .= $DSP->div_c();
			$this->menu .= $DSP->div('profileMenuInner');

            /** --------------------------------
    		/**  Create Menu Based on Item Map
    		/** --------------------------------*/
            				
            foreach($map as $item)
            {
            	if (isset($this->menu_items['repeat_items'][$item]))
            	{
            		foreach($this->menu_items['repeat_items'][$item] as $item_member)
            		{
            			$this->create_item($item_member);
            		}
            	}
            	else
            	{
            		$this->create_item($this->menu_items['single_items'][$item]);
            	}
            }            				
            				
            $this->menu .= $DSP->div_c().$DSP->div_c().$DSP->div_c();
    	}
    	else
    	{
    		$template = $this->retrieve_template('message_menu');
    		$rows	  = $this->retrieve_template('message_menu_rows');
    		
    		$this->single_parts['include']['hide_menu_style'] = $hidden_style;
    		$this->single_parts['include']['hide_menu_link']  = $hidden_link;
    		$this->single_parts['include']['hide_menu_js']	  = $this->showhide_js();
    		
    		$this->single_parts['include']['menu_items'] = '';
    		
    		foreach($map as $item)
            {
            	if (isset($this->menu_items['repeat_items'][$item]))
            	{
            		foreach($this->menu_items['repeat_items'][$item] as $item_member)
            		{
            			$this->single_parts['title'] = $item_member['text'];
            			$this->single_parts['link']  = $item_member['link'];
            			
            			$this->single_parts['include']['menu_items'] .= $this->_process_template($rows);
            		}
            	}
            	else
            	{
            		$this->single_parts['title'] = $this->menu_items['single_items'][$item]['text'];
            		$this->single_parts['link']  = $this->menu_items['single_items'][$item]['link'];
            		
            		$this->single_parts['include']['menu_items'] .= $this->_process_template($rows);
            	}
            }
            
            $this->menu = $this->_process_template($template);
    	}
 	}
 	/* END */
 	
 	
	/** -----------------------------------
    /**  Create Menu Item
    /** -----------------------------------*/

    function create_item($data)
    {
    	global $DB, $DSP;
    	
		$this->menu .= $DSP->div(($data['style'] != '') ? $data['style'] : 'navPad').
    		   		   $DSP->anchor($data['link'], $data['text']).
    		   		   $DSP->div_c();
 	}
 	/* END */
 	 	



	// -----------------------------------
    //  Inbox
    //  This function is kind of superfluous, but I find it comforting
    //  to know that there is an inbox function for a messaging system.
    //  Besides, it makes it a tad easier to understand certain parts 
    //  of the code to have this function called opposed to what is 
    //  really being calling. That is all, move along now...
    // -----------------------------------   

    function inbox()
    {	
    	$this->view_folder('1');
 	}
 	/* END */
 	
 	/** -----------------------------------
    /**  Deleted Messages
    /** -----------------------------------*/

    function deleted()
    {
    	$this->view_folder('0');
    }
    /* END */


	/** -----------------------------------
    /**  View Folder Contents
    /** -----------------------------------*/

    function drafts()
    {
    	global $LANG, $DB, $OUT, $IN, $LOC;
    	
    	$row_count = 0;  // How many rows shown this far (i.e. offset)
    	
    	if ($this->allegiance == 'user')
    	{
    		$row_count = $this->cur_id;
    	}
    	else
    	{
    		$row_count = ($IN->GBL('page', 'GP') === false) ? 0 : $IN->GBL('page', 'GP');
    	}
    	
    	if ( ! is_numeric($row_count))
    	{
    		$row_count = 0;
    	}
    	
    	$this->single_parts['lang']['folder_id']   = '1';
    	$this->single_parts['lang']['folder_name'] = $LANG->line('draft_messages');
    	$this->conditionals['paginate'] = 'n';
    	
    	$this->conditionals['drafts_folder']	= 'y';
    	$this->conditionals['sent_folder']		= 'n';
    	$this->conditionals['trash_folder']		= 'n';
    	$this->conditionals['regular_folder']	= 'n';
    						 
    	/** ---------------------------------------
    	/**  Retrieve Folder Contents Query
    	/** ---------------------------------------*/
    	
    	$dql = "SELECT 
				exp_message_data.sender_id, 
    			exp_message_data.message_date, 
    			exp_message_data.message_id,
    			exp_message_data.message_subject, 
    			exp_message_data.message_recipients,
    			exp_message_data.message_cc,
    			exp_message_data.message_attachments, 
    			exp_members.screen_name as sender, 
    			exp_members.username as sender_username ";
    	
    	$sql = "FROM exp_message_data
    			LEFT JOIN exp_members ON exp_members.member_id = exp_message_data.sender_id
    			WHERE exp_message_data.message_status = 'draft' 
    			AND exp_message_data.sender_id = '{$this->member_id}'
    			ORDER BY exp_message_data.message_date ";
    			
    	/** ----------------------------------------
        /**  Run "count" query for pagination
        /** ----------------------------------------*/
        
		$query = $DB->query("SELECT COUNT(exp_message_data.message_id) AS count ".$sql);
		
		/** ----------------------------------------
        /**  If No Messages, we say so.
        /** ----------------------------------------*/
		
		if ($query->row['count'] == 0)
		{
			$this->title = $LANG->line('draft_messages');
			$this->crumb = $LANG->line('draft_messages');
			
			$this->single_parts['include']['folder_rows']  = $this->retrieve_template('message_no_folder_rows');
			$this->single_parts['form']['form_declaration']['modify_messages'] = '';
			
			$this->return_data = $this->folder_wrapper(($folder_id == '0') ? 'n' : 'y');
			
			return;
		}
		
		/** ----------------------------------------
        /**  Determine Current Page
        /** ----------------------------------------*/
		
		$current_page = ($row_count / $this->per_page) + 1;
			
        $total_pages = intval($query->row['count'] / $this->per_page);
        
        if ($query->row['count'] % $this->per_page) 
        {
            $total_pages++;
        }
        
        $this->single_parts['include']['page_count'] = $LANG->line('folder_page').' '.$current_page.' '.$LANG->line('of').' '.$total_pages;
    	
    	/** -----------------------------
    	/**  Do we need pagination?
    	/** -----------------------------*/
				
		$pager = ''; 
		
		if ($query->row['count'] > $this->per_page)
		{ 											
			if ( ! class_exists('Paginate'))
			{
				require PATH_CORE.'core.paginate'.EXT;
			}
			
			$PGR = new Paginate();
			
			if ($this->allegiance == 'user')
    		{
    			$PGR->path = $this->_create_path('drafts');
    		}
    		else
    		{
    			$PGR->base_url = $this->_create_path('drafts');
    			$PGR->qstr_var = 'page';
    		}
			
			$PGR->total_count 	= $query->row['count'];
			$PGR->per_page		= $this->per_page;
			$PGR->cur_page		= $row_count;
			
			$this->single_parts['include']['pagination_link'] = $PGR->show_links();
			$this->conditionals['paginate'] = 'y';
			 
			$sql .= " LIMIT ".$row_count.", ".$this->per_page;			
		}
		
		/** ----------------------------------------
        /**  Retrieve Folder Contents
        /** ----------------------------------------*/
		
		$folder_rows_template	= $this->retrieve_template('message_folder_rows');
		$r = '';
		$i = 0;
		
		$query = $DB->query($dql.$sql);
		
		foreach($query->result as $row)
		{
			++$i;
			$data = $row;
			$data['msg_id']		  = 'd'.$row['message_id'];
			$data['message_date'] = $LOC->set_human_time($data['message_date']);
			$data['style']		  = ($i % 2) ? 'tableCellTwo' : 'tableCellOne';
			
			if ($this->allegiance == 'user')
    		{
    			$data['message_url']  = $this->base_url.'compose/'.$row['message_id'].'/';
    		}
    		else
    		{
    			$data['message_url'] = $this->base_url.'compose'.AMP.'msg='.$row['message_id'];
    		}
    		
    		$data['message_status'] = '';
    		
    		// This Requires Extra Queries and Processing
    		// So We Only Do It When Those Variables Are Found
    		
    		if (stristr($folder_rows_template, '{recipients}') !== FALSE)
    		{
    			$data['recipients'] = htmlspecialchars($this->convert_recipients($row['message_recipients']), ENT_QUOTES);
    		}
    		
    		if (stristr($folder_rows_template, '{cc}') !== FALSE)
    		{
    			$data['cc'] = htmlspecialchars($this->convert_recipients($row['message_cc']), ENT_QUOTES);
    		}
    		
    		$r .= $this->_process_template($folder_rows_template, $data);
		}
		
		$this->single_parts['include']['folder_rows'] = $r;
		
		/** ----------------------------------------
		/**  Return the Folder's Contents
		/** ----------------------------------------*/
		
		$this->title = $LANG->line('draft_messages');
		$this->crumb = $LANG->line('draft_messages');
		$this->return_data = $this->folder_wrapper('y', 'n', 'n');
 	}
 	/* END */
 	
 	
	/** -----------------------------------
    /**  View Folder Contents
    /** -----------------------------------*/

    function view_folder($folder_id='')
    {
    	global $LANG, $DB, $OUT, $IN, $LOC;
    	
    	// ---------------------------------
    	// Find Requested Folder ID
    	// $IN->QSTR - User
    	// $IN->GBL('folder', 'GP') - CP
    	// ---------------------------------
    	
    	$row_count = 0;  // How many rows shown this far (i.e. offset)
    	
    	if ($folder_id == '')
    	{	
    		if ($this->allegiance == 'user')
    		{
    			// Unknown, probably will have to do something similar to
    			// the list of members where we have to create 
    			// a pseudo-query string in one of the URI segments
    			// folder_pagenum : 1_1
    			
    			if ($this->cur_id == '')
    			{
    				$folder_id = 1;
    			}
    			else
    			{
    				$x = explode('_', $this->cur_id);
    				
    				$folder_id = ( ! is_numeric($x['0'])) ? 1 : $x['0'];
    				$row_count = ( ! isset($x['1']) OR ! is_numeric($x['1'])) ? 0 : $x['1'];
    			}
    		}
    		else
    		{
    			$folder_id = ($IN->GBL('folder', 'GP') === false) ? '1' : $IN->GBL('folder', 'GP');
    			$row_count = ($IN->GBL('page', 'GP') === false) ? 0 : $IN->GBL('page', 'GP');
    		}
    	}
    	
    	if ( ! is_numeric($folder_id) OR $folder_id > $this->max_folders)
    	{
    		$folder_id = '1';
    	}
    	
    	if ( ! is_numeric($row_count))
    	{
    		$row_count = 0;
    	}
    	
    	
    	/** ---------------------------------------
    	/**  Retrieve Folder Name for User
    	/** ---------------------------------------*/
    	
    	if ($folder_id == '0')
    	{
    		$folder_name = $LANG->line('deleted_messages');
    	}
    	elseif ( ! isset($this->folders[$folder_id]['0']) OR $this->folders[$folder_id]['0'] == '')
    	{
    		if ($this->allegiance == 'cp')
    		{
    			return $DSP->no_access_message();
    		}
    		else
    		{
    			return $OUT->show_user_error('general', array($LANG->line('not_authorized')));
    		}
    	}
    	else
    	{
    		$folder_name = $this->folders[$folder_id]['0'];
    	}
    	
    	$this->single_parts['lang']['folder_name'] = $LANG->line('messages_folder').' - '.$folder_name;
    	$this->single_parts['lang']['folder_id'] = $folder_id;
    	$this->current_folder = $folder_id;
    	$this->conditionals['paginate'] = 'n';
    	
    	/** -----------------------------------
    	/**  Folder Conditionals
    	/** -----------------------------------*/
    	
    	$this->conditionals['drafts_folder']	= 'n';
    	$this->conditionals['sent_folder']		= 'n';
    	$this->conditionals['trash_folder']		= 'n';
    	$this->conditionals['regular_folder']	= 'n';
    	
    	if ($folder_id == '0')
    	{
    		$this->conditionals['trash_folder'] = 'y';
    	}
    	elseif ($folder_id == '2')
    	{
    		$this->conditionals['sent_folder'] = 'y';
    	}
    	else
    	{
    		$this->conditionals['regular_folder'] = 'y';
    	}
    						 
    	/** ---------------------------------------
    	/**  Retrieve Folder Contents Query
    	/** ---------------------------------------*/
    	
    	$dql = "SELECT 
				exp_message_copies.message_status,
				exp_message_copies.message_id,
				exp_message_copies.message_read,
				exp_message_copies.copy_id as msg_id, 
				exp_message_data.sender_id, 
    			exp_message_data.message_date, 
    			exp_message_data.message_subject, 
    			exp_message_data.message_recipients,
    			exp_message_data.message_cc,
    			exp_message_data.message_attachments, 
    			exp_members.screen_name as sender, 
    			exp_members.username as sender_username ";
    	
    	$sql = "FROM exp_message_copies
    			LEFT JOIN exp_message_data ON exp_message_data.message_id = exp_message_copies.message_id
    			LEFT JOIN exp_members ON exp_members.member_id = exp_message_copies.sender_id
    			WHERE exp_message_copies.recipient_id = '{$this->member_id}' ";
    			
    	if ($folder_id == '0')
    	{
    		$sql .= "AND exp_message_copies.message_deleted = 'y' ";
    	}
    	else
    	{
    		$sql .= "AND exp_message_copies.message_folder = '{$folder_id}'
    				 AND exp_message_copies.message_deleted = 'n' ";
    	}		
    			
    	$sql .= "AND exp_message_data.message_status = 'sent'
    			 ORDER BY exp_message_data.message_date desc";
    			
    	/** ----------------------------------------
        /**  Run "count" query for pagination
        /** ----------------------------------------*/
        
		$query = $DB->query("SELECT COUNT(exp_message_copies.copy_id) AS count ".$sql);
		
		/** ----------------------------------------
        /**  If No Messages, we say so.
        /** ----------------------------------------*/
		
		if ($query->row['count'] == 0)
		{
			$this->title = $folder_name;
			$this->crumb = $folder_name;
			
			$this->single_parts['include']['folder_rows']  = $this->retrieve_template('message_no_folder_rows');
			$this->single_parts['form']['form_declaration']['modify_messages'] = '';
			
			$this->return_data = $this->folder_wrapper(($folder_id == '0') ? 'n' : 'y');
			
			return;
		}
		
		/** ----------------------------------------
        /**  Determine Current Page
        /** ----------------------------------------*/
		
		$current_page = ($row_count / $this->per_page) + 1;
			
        $total_pages = intval($query->row['count'] / $this->per_page);
        
        if ($query->row['count'] % $this->per_page) 
        {
            $total_pages++;
        }
        
        $this->single_parts['include']['page_count'] = $LANG->line('folder_page').' '.$current_page.' '.$LANG->line('of').' '.$total_pages;
    	
    	/** -----------------------------
    	/**  Do we need pagination?
    	/** -----------------------------*/
				
		$pager = ''; 		
		
		if ($query->row['count'] > $this->per_page)
		{ 											
			if ( ! class_exists('Paginate'))
			{
				require PATH_CORE.'core.paginate'.EXT;
			}
			
			$PGR = new Paginate();
			
			if ($this->allegiance == 'user')
    		{
    			$PGR->path = $this->base_url.'view_folder/'.$folder_id.'_';
    		}
    		else
    		{
    			$PGR->base_url = $this->base_url.'view_folder'.AMP.'folder='.$folder_id;
    			$PGR->qstr_var = 'page';
    		}
			
			$PGR->total_count 	= $query->row['count'];
			$PGR->per_page		= $this->per_page;
			$PGR->cur_page		= $row_count;
			
			$this->single_parts['include']['pagination_link'] = $PGR->show_links();
			$this->conditionals['paginate'] = 'y';
			 
			$sql .= " LIMIT ".$row_count.", ".$this->per_page;			
		}
		
		/** ----------------------------------------
        /**  Retrieve Folder Contents
        /** ----------------------------------------*/
		
		$message_ids			= array();
		$folder_rows_template	= $this->retrieve_template('message_folder_rows');
		$i = 0;
		$r = '';
		
		$query = $DB->query($dql.$sql);
		
		foreach($query->result as $row)
		{
			$i++;
			$data			= $row;
			$message_ids[]	= $row['message_id'];
			
			$data['buddy_list_link'] = '';
    		$data['block_list_link'] = '';
			$data['message_date'] = $LOC->set_human_time($data['message_date']);
			$data['style']		  = ($i % 2) ? 'tableCellTwo' : 'tableCellOne';
			
			if ($this->allegiance == 'user')
    		{
    			$data['message_url']  = $this->base_url.'view_message/'.$row['msg_id'].'/';
    			//$data['buddy_list_link'] = $this->_create_path('add_buddy').$row['sender_id'].'/';
    			//$data['block_list_link'] = $this->_create_path('add_block').$row['sender_id'].'/';
    		}
    		else
    		{
    			$data['message_url'] = $this->base_url.'view_message'.AMP.'msg='.$row['msg_id'];
    			//$data['buddy_list_link'] = $this->_create_path('add_buddy').AMP.'id='.$row['sender_id'];
    			//$data['block_list_link'] = $this->_create_path('add_block').AMP.'id='.$row['sender_id'];
    		}
    		
    		// --------------------------------
    		//  Message Status Entities:
    		//  &bull; - unread
    		//  &rarr; - forwarded
    		//  &crarr; - Reply
    		// --------------------------------
    		
    		if ($row['message_status'] == 'replied')
    		{
    			$data['message_status'] = '&crarr;';
    		}
    		elseif ($row['message_status'] == 'forwarded')
    		{
    			$data['message_status'] = '&rarr;';
    		}
    		elseif ($row['message_read'] == 'y')
    		{
    			$data['message_status'] = '';
    		}
    		elseif($row['message_read'] == 'n')
    		{
    			$data['message_status'] = '&bull;';
    		}
    		
    		// This Requires Extra Queries and Processing
    		// So We Only Do It When Those Variables Are Found
    		
    		if (stristr($folder_rows_template, '{recipients}') !== FALSE)
    		{
    			$data['recipients'] = htmlspecialchars($this->convert_recipients($row['message_recipients']), ENT_QUOTES);
    		}
    		
    		if (stristr($folder_rows_template, '{cc}') !== FALSE)
    		{
    			$data['cc'] = htmlspecialchars($this->convert_recipients($row['message_cc']), ENT_QUOTES);
    		}
    		
    		$r .= $this->_process_template($folder_rows_template, $data);
		}
		
		$this->single_parts['include']['folder_rows'] = $r;
		
		/** ----------------------------------------
        /**  If Displayed, Messages are Received (not read)
        /** ----------------------------------------*/
		
		if (sizeof($message_ids) > 0 && $this->block_tracking == 'n')
		{
			$DB->query("UPDATE exp_message_copies SET message_received = 'y' 
						WHERE recipient_id = '{$this->member_id}'
						AND message_id IN ('".implode("','",$message_ids)."')");
		}
		
		/** ----------------------------------------
		/**  Return the Folder's Contents
		/** ----------------------------------------*/
		
		$this->title = $folder_name;
		$this->crumb = $folder_name;
		$this->return_data = $this->folder_wrapper(($folder_id == '0') ? 'n' : 'y');
 	}
 	/* END */
 	
 	
 	
 	
 	
 	
	/** ----------------------------------------
    /**  Wrapper for a Folder and its Contents
    /** ----------------------------------------*/

    function folder_wrapper($deleted='y', $moved='y', $copied = 'y')
    {
    	global $FNS;
    	
 		$folder_template = $this->retrieve_template('message_folder');
 		
 		$this->folders_pulldown();
 		
 		$this->single_parts['include']['hidden_js'] = $this->hidden_js();
 		$this->single_parts['include']['toggle_js'] = $this->toggle_js();
 		$this->single_parts['path']['compose_message'] = $this->_create_path('compose');
 		$this->single_parts['path']['erase_messages']  = $this->_create_path('erase');
 		
 		$details = array('hidden_fields'	=> array('this_folder' => $this->single_parts['lang']['folder_id'], 'daction' => ''),
    					 'action'			=> $this->_create_path('modify_messages'),
    					 'id'				=> 'target',
    					 'enctype'			=> 'multi',
    					 'secure'			=> ($this->allegiance == 'cp') ? FALSE : TRUE
    					 );  	
    	
    	$this->single_parts['form']['form_declaration']['modify_messages']  = $FNS->form_declaration($details);

		/** ---------------------------------
		/**  Move, Copy, Delete Buttons
		/** ---------------------------------*/
 		
 		$this->_buttons($deleted, $moved, $copied);
 		
 		/** -------------------------------
 		/**  Storage Graph
 		/** -------------------------------*/
 		
 		if ( ! isset($this->single_parts['image']['messages_graph']))
 		{
 			$this->storage_graph();
 		}
 		
 		return $this->_process_template($folder_template);
	}
	/* END */
	
	
	/** ----------------------------------------
    /**  Buttons for Various Pages
    /** ----------------------------------------*/

    function _buttons($deleted='y', $moved='y', $copied = 'y')
    {
    	global $LANG;
    
    	$style = 'buttons';
    
    
 		/** ---------------------------------
		/**  Move, Copy, Delete Buttons
		/** ---------------------------------*/
 		
 		if ($deleted == 'n')
 		{
 			$this->single_parts['form']['delete_button'] = '';
 		}
 		else
 		{
 			$this->single_parts['form']['delete_button'] = "<button type='submit' id='delete' name='delete' value='delete' ".
 														   "class='{$style}' title='{lang:delete_selected}' ".
 														   "onclick='dynamic_action(\"delete\");'>".
 														   "{lang:messages_delete}</button> ";
 		}
 		
 		if ($moved == 'n')
 		{
 			$this->single_parts['form']['move_button'] = '';
 		}
 		else
 		{
 			$this->single_parts['form']['move_button'] = "<button type='submit' id='move' name='move' value='move' ".
 														  "class='{$style}' title='{lang:move_selected}' ".
 														  "onclick='dynamic_move();return false;'>".
 														  "{lang:messages_move}</button>".NBS.NBS;
 		}
 		
 		if ($copied == 'n')
 		{
 			$this->single_parts['form']['copy_button'] = '';
 		}
 		else
 		{
 			$this->single_parts['form']['copy_button'] = "<button type='submit' id='copy' name='copy' value='copy' ".
 														  "class='{$style}' title='{lang:copy_selected}' ".
 														  "onclick='dynamic_copy();return false;'>".
 														  "{lang:messages_copy}</button>".NBS.NBS;
 		}
 		
 		$this->single_parts['form']['forward_button'] = "<button type='submit' id='forward' name='forward' value='forward' ".
 														 "class='{$style}' title='{lang:messages_forward}' ".
 														 "onclick='dynamic_action(\"forward\");'>".
 														 "{lang:messages_forward}</button>".NBS.NBS;
 														 
 														 
 		$this->single_parts['form']['reply_button'] = "<button type='submit' id='reply' name='reply' value='reply' ".
 														  "class='{$style}' title='{lang:messages_reply}' ".
 														  "onclick='dynamic_action(\"reply\");'>".
 														  "{lang:messages_reply}</button>".NBS.NBS;
 														  
 		$this->single_parts['form']['reply_all_button'] = "<button type='submit' id='reply_all' name='reply_all' value='reply_all' ".
 														  "class='{$style}' title='{lang:messages_reply_all}' ".
 														  "onclick='dynamic_action(\"reply_all\");'>".
 														  "{lang:messages_reply_all}</button>".NBS.NBS;
 														  
 		$this->single_parts['form']['add_button']	= $this->list_js().
 													  "<button type='submit' id='add' name='add' value='add' ".
 													  "class='{$style}' title='{lang:add_member}' ".
 													  "onclick='list_addition();return false;'>".
 													  "{lang:add_member}</button>".NBS.NBS;
 		
 		
	}
	/* END */
	
	
	/** ----------------------------------------
    /**  Retrieve a Template for Processing
    /** ----------------------------------------*/

    function retrieve_template($which='')
    {
    	global $PREFS, $FNS;
    	
 		if ($which == '')
 		{
 			$which = 'message_folder';
 		}
 		
 		/** ----------------------------------
 		/**  CP Templates - Quick, Easy
 		/** ----------------------------------*/
 		
 		if ($this->allegiance == 'cp')
 		{
 			if ( ! class_exists('Messages_CP'))
 			{
 				require PATH_CP.'cp.messages.php';
 				$this->CP = new Messages_CP();
 			}
 			
 			$which = $which.'_cp';
 			return $this->CP->{$which}();
 		}

		/** ----------------------------------
 		/**  User Templates - Slow, Painful
 		/** ----------------------------------*/
 		
 		if ( ! class_exists($this->theme_class))
		{
			if ($this->theme_path == '')
			{
				$theme = ($PREFS->ini('member_theme') == '') ? 'default' : $FNS->filename_security($PREFS->ini('member_theme'));
				
				$this->theme_path = PATH_MBR_THEMES.$theme.'/profile_theme'.EXT;
			}
		
            include_once $this->theme_path;    
		}
	
		if ( ! isset($MS) OR ! is_object($MS))
		{
			$MS = new $this->theme_class();
		}

		if ( ! method_exists($MS, $which))
		{
			global $OUT, $LANG, $PREFS;

			// echo $which;
			
			$data = array(	'title' 	=> $LANG->line('error'),
							'heading'	=> $LANG->line('general_error'),
							'content'	=> $LANG->line('nonexistant_page'),
							'redirect'	=> '',
							'link'		=> array($PREFS->ini('site_url'), stripslashes($PREFS->ini('site_name')))
						 );
               
			return $OUT->show_message($data, 0);
		
		}

		return trim($MS->$which());
	}
	/* END */

	
	
	/** -----------------------------------
    /**  Folders Pulldown Menu
    /** -----------------------------------*/

    function folders_pulldown()
    {
    	global $LANG;
			  
		$str=NL; $str2=NL;
		
		foreach($this->folders as $key => $value)
    	{
    		if ($value['0'] != '')
    		{
    			if ($this->allegiance == 'user')
    			{
    				$url = $this->base_url.'view_folder/'.$key.'/';
    			}
    			else
    			{
    				$url = $this->base_url.'view_folder'.AMP.'folder='.$key;
    			}
    			
    			$selected = ($key == $this->current_folder) ? ' selected="selected"' : '';
    		
    			$str  .= '<option value="'.$url.'"'.$selected.'>'.$value['0'].'</option>'.NL;
    			$str2 .= '<option value="'.$key.'"'.$selected.'>'.$value['0'].'</option>'.NL;
    		}
    	}
    	
    	$str  .= '</select>'.NL;
    	$str2 .= '</select>'.NL;
    	
    	$yoffset = ($this->allegiance == 'cp') ? 30 : 20;
    	
   		$move_js = <<<DOH
<script type="text/javascript"> 
//<![CDATA[

var movepopup = new PopupWindow("movemenu");
movepopup.offsetY={$yoffset};
movepopup.autoHide();

//]]>
</script>
DOH;

   		$copy_js = <<<DOH
<script type="text/javascript"> 
//<![CDATA[

var copypopup = new PopupWindow("copymenu");
copypopup.offsetY={$yoffset};
copypopup.autoHide();

//]]>
</script>
DOH;
    	
    	$this->single_parts['include']['folder_pulldown']['change'] = '<select name="change_folder" class="select" onchange="location.href=this.value">'.$str;
    	
    	$this->single_parts['include']['folder_pulldown']['move'] = '<div id="movemenu" class="tableCellOne" style="border: 1px solid #666; position:absolute;visibility:hidden;">'.
    																'<select name="moveto" class="select" onchange="this.form.submit();">'.
    																'<option value="none">'.$LANG->line('choose_folder').'</option>'.NL.
    																$str2.
    																'</div>'.NL.
    																$move_js;
    	
    	$this->single_parts['include']['folder_pulldown']['copy'] = '<div id="copymenu" class="tableCellOne" style="border: 1px solid #666; position:absolute;visibility:hidden;">'.
    																'<select name="copyto" class="select" onchange="this.form.submit();">'.
    																'<option value="none">'.$LANG->line('choose_folder').'</option>'.NL.
    																$str2.
    																'</div>'.NL.
    																$copy_js;
    }
    /* END */
    
    
    
	
	/** -----------------------------------
    /**  Member Search Popup
    /** -----------------------------------*/

    function member_search($message='')
    {
    	global $LANG, $DB, $IN, $FNS, $PREFS;
    	
    	$this->title = ''; 
    	$this->crumb = '';
    	
    	if ($this->allegiance == 'cp')
    	{
    		$which_field = ( ! $IN->GBL('field')) ? 'recipients' : $IN->GBL('field');
    	}
    	else
    	{
    		$which_field = ($this->cur_id != '') ? $this->cur_id : 'recipients';
    	}
    	
    	$form_details = array('hidden_fields' => array('which_field' => $which_field),
    						  'action'	=> $this->_create_path('do_member_search', AMP.'Z=1'),
    					 	  'secure'	=> ($this->allegiance == 'cp') ? FALSE : TRUE
    					 	  );
    	
    	$this->single_parts['form']['form_declaration']['do_member_search'] = $FNS->form_declaration($form_details);
    	$this->single_parts['include']['message'] = $message;
    	
    	$this->conditionals['message'] = ($message != '') ? 'y' : 'n';
                              
        // Member group select list
        
        $this->single_parts['include']['member_group_options'] = '';

        $query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND include_in_memberlist = 'y' ORDER BY group_title");
              
        foreach ($query->result as $row)
        {                                
             $this->single_parts['include']['member_group_options'] .= '<option value="'.$row['group_id'].'">'.$row['group_title'].'</option>';
        }
        
        $this->return_data = $this->_process_template($this->retrieve_template('search_members'));
        
        if ($this->allegiance == 'cp')
        {
        	global $DSP;
        	
        	$DSP->title = $LANG->line('member_search');
	        $DSP->body  = $this->return_data;
    	}
	}
	/* END */
	

    /** -----------------------------
    /**  Perform Member search
    /** -----------------------------*/
    
    function do_member_search()
    {  
        global $IN, $LANG, $FNS, $DB, $PREFS, $SESS;
        
        $this->title = ''; 
    	$this->crumb = '';
    	
    	$which_field = ( ! $IN->GBL('which_field')) ? 'recipients' : $IN->GBL('which_field');
        
        if ($this->allegiance == 'cp')
        {
        	$s = ($PREFS->ini('admin_session_type') != 'c') ? $SESS->userdata['session_id'] : 0;
			$redirect_url = $PREFS->ini('cp_url', FALSE).'?S='.$s.
							AMP.'C=myaccount'.
							AMP.'M=messages'.
							AMP.'P=member_search'.
							AMP.'field='.$which_field.
							AMP.'Z=1';
        }
        else
        {
        	$redirect_url = $this->_create_path('member_search').$which_field.'/';
        }
        
        $this->single_parts['path']['new_search_url'] = $redirect_url;
        $this->single_parts['which_field']			  = $which_field;
        
		/** --------------------------------
		/**  Parse the POST request
		/** --------------------------------*/
        
        if ($_POST['screen_name'] 	== '' &&
        	$_POST['email'] 		== ''
        	) 
        {
        	$FNS->redirect($redirect_url);
			exit;
		}
        	
        $search_query = array();
        
        foreach ($_POST as $key => $val)
		{
			if ($key == 'which_field' OR $key == 'XID')
			{
				continue;
			}
			if ($key == 'group_id')
			{
				if ($val != 'any')
				{
					$search_query[] = " exp_member_groups.group_id ='".$DB->escape_str($_POST['group_id'])."'";
				}
			}
			else
			{
				if ($val != '')
				{
					$search_query[] = $key." LIKE '%".$DB->escape_str($val)."%'";
				}
			}
		}
		
		if (count($search_query) < 1)
		{
			$FNS->redirect($redirect_url);
			exit; 
		}
                        
  		$Q = implode(" AND ", $search_query);
                
        $sql = "SELECT DISTINCT exp_members.screen_name FROM exp_members, exp_member_groups 
        		WHERE exp_members.group_id = exp_member_groups.group_id 
        		AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND include_in_memberlist = 'y'
        		AND ".$Q;                 
        
        $query = $DB->query($sql);
        
        $total_count = $query->num_rows;
        
        if ($total_count == 0)
        {
            return $this->member_search($LANG->line('no_search_results'));
        }
        
        $this->single_parts['include']['search_results'] = '';
        
        foreach($query->result as $row)
        {
        	// Changed this from htmlentities to htmlspecialchars because of problems with foreign characters, specifically persian ones
        	$value = htmlspecialchars(str_replace(array('(', ')', "'", '"'), array('\(', '\)', "\'", '\"'), '<'.$row['screen_name']).'>', ENT_QUOTES);
        	
			$this->single_parts['include']['search_results'] .= $this->_process_template($this->retrieve_template('member_results_row'), array('item' => '<a href="#" onclick="insert_name(\''.$value.'\');return false;">'.$row['screen_name'].'</a>'));
        }
        
        $this->return_data = $this->_process_template($this->retrieve_template('member_results'));
        
        if ($this->allegiance == 'cp')
        {
        	global $DSP;
        	
        	$DSP->title = $LANG->line('search_results');
        	$DSP->body  = $this->return_data;
        }
    }
    /* END */
    
    
	/** -----------------------------------
    /**  Buddy Search Popup
    /** -----------------------------------*/

    function buddy_search($message='')
    {
    	global $LANG, $DB, $IN, $FNS, $PREFS;
    	
    	$this->title = ''; 
    	$this->crumb = '';
    	
    	if ($this->allegiance == 'cp')
    	{
    		$which = ( ! $IN->GBL('which')) ? 'buddy' : $IN->GBL('which');
    	}
    	else
    	{
    		$which = ($this->cur_id != '') ? $this->cur_id : 'buddy';
    	}
    	
    	$form_details = array('hidden_fields' => array('which' => $which),
    						  'action'	=> $this->_create_path('do_buddy_search', AMP.'Z=1'),
    					 	  'secure'	=> ($this->allegiance == 'cp') ? FALSE : TRUE
    					 	  );
    	
    	$this->single_parts['form']['form_declaration']['do_member_search'] = $FNS->form_declaration($form_details);
    	$this->single_parts['include']['message'] = $message;
    	
    	$this->conditionals['message'] = ($message != '') ? 'y' : 'n';
                              
        // Member group select list
        
        $this->single_parts['include']['member_group_options'] = '';

        $query = $DB->query("SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND include_in_memberlist = 'y' ORDER BY group_title");
              
        foreach ($query->result as $row)
        {                                
             $this->single_parts['include']['member_group_options'] .= '<option value="'.$row['group_id'].'">'.$row['group_title'].'</option>';
        }
        
        $this->return_data = $this->_process_template($this->retrieve_template('search_members'));
        
        if ($this->allegiance == 'cp')
        {
        	global $DSP;
        	
        	$DSP->title = $LANG->line('member_search');
	        $DSP->body  = $this->return_data;
    	}
	}
	/* END */
	

    /** -----------------------------
    /**  Perform Buddy search
    /** -----------------------------*/
    
    function do_buddy_search()
    {  
        global $IN, $LANG, $FNS, $DB, $PREFS, $SESS;
        
        $this->title = ''; 
    	$this->crumb = '';
    	
    	$which = ( ! $IN->GBL('which')) ? 'buddy' : $IN->GBL('which');
        
        if ($this->allegiance == 'cp')
        {
        	$s = ($PREFS->ini('admin_session_type') != 'c') ? $SESS->userdata['session_id'] : 0;
			$redirect_url = $PREFS->ini('cp_url', FALSE).'?S='.$s.
							AMP.'C=myaccount'.
							AMP.'M=messages'.
							AMP.'P=buddy_search'.
							AMP.'which='.$which.
							AMP.'Z=1';
        }
        else
        {
        	$redirect_url = $this->_create_path('buddy_search').$which.'/';
        }
        
        $this->single_parts['path']['new_search_url'] = $redirect_url;
        $this->single_parts['which_field']			  = $which;  // For the results
        
		/** --------------------------------
		/**  Parse the POST request
		/** --------------------------------*/
        
        if ($_POST['screen_name'] 	== '' &&
        	$_POST['email'] 		== ''
        	) 
        	{
        		$FNS->redirect($redirect_url);
				exit;    
        	}
        	
        $search_query = array();
        
        foreach ($_POST as $key => $val)
		{
			if ($key == 'which' OR $key == 'XID')
			{
				continue;
			}
			if ($key == 'group_id')
			{
				if ($val != 'any')
				{
					$search_query[] = " group_id ='".$DB->escape_str($_POST['group_id'])."'";
				}
			}
			else
			{
				if ($val != '')
				{
					$search_query[] = $key." LIKE '%".$DB->escape_str($val)."%'";
				}
			}
		}
		
		if (count($search_query) < 1)
		{
			$FNS->redirect($redirect_url);
			exit; 
		}
                        
  		$Q = implode(" AND ", $search_query);
                
        $sql = "SELECT DISTINCT exp_members.member_id, exp_members.screen_name FROM exp_members, exp_member_groups 
        		WHERE exp_members.group_id = exp_member_groups.group_id 
        		AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND include_in_memberlist = 'y'
        		AND ".$Q;                 
        
        $query = $DB->query($sql);
        
        $total_count = $query->num_rows;
        
        if ($total_count == 0)
        {
            return $this->buddy_search($LANG->line('no_search_results'));
        }
        
        $r = '';
        
        foreach($query->result as $row)
        {
        	$link =  ($which == 'blocked') ? $this->_create_path('add_block') : $this->_create_path('add_buddy');
        	
        	$link .= ($this->allegiance == 'cp') ? '&mid='.$row['member_id'] : $row['member_id'].'/';
        	
			$r .= $this->_process_template($this->retrieve_template('member_results_row'), array('item' => '<a href="#" onclick="window.opener.location.href=\''.$link.'\';return false;">'.$row['screen_name'].'</a>'));
        }
        
        $this->single_parts['include']['search_results'] = $r;
        
        $this->return_data = $this->_process_template($this->retrieve_template('member_results'));
        
        if ($this->allegiance == 'cp')
        {
        	global $DSP;
        	
        	$DSP->title = $LANG->line('search_results');
        	$DSP->body  = $this->return_data;
        }
    }
    /* END */
       

    
    
	/** -----------------------------------
    /**  Current Storage Usage
    /** -----------------------------------*/

    function storage_usage()
    {
    	global $LANG, $DB;
    	
    	$sql = "SELECT COUNT(exp_message_copies.copy_id) AS count 
    			FROM exp_message_copies
    			WHERE exp_message_copies.recipient_id = '{$this->member_id}'
    			AND exp_message_copies.message_deleted = 'n'";
    			
    	$tql = "SELECT COUNT(exp_message_data.message_id) AS count 
    			FROM exp_message_data
    			WHERE exp_message_data.sender_id = '{$this->member_id}'
    			AND exp_message_data.message_status = 'draft'";
    			
    	$query   = $DB->query($sql);
    	$results = $DB->query($tql);
    	
    	$this->total_messages = $query->row['count']; + $results->row['count'];
 	}
 	/* END */
 	
 	
	/** -----------------------------------
    /**  Current Storage Graph
    /** -----------------------------------*/

    function storage_graph()
    {
    	global $LANG, $DB, $SESS;
    	
    	if ($this->total_messages == '')
    	{
    		$this->storage_usage();
    	}
    	
    	/** ----------------------
    	/**  Default
    	/** ----------------------*/
    	
    	$this->single_parts['lang']['total_messages']	= $this->total_messages;
    	$this->single_parts['lang']['max_messages']		= ($this->storage_limit == '0') ? $LANG->line('unlimited_messages') : $this->storage_limit;
    	$this->single_parts['lang']['usage_percent']	= '0';
    	
    	$this->single_parts['image']['messages_graph']['width']		= '1';
    	$this->single_parts['image']['messages_graph']['height']	= '11';
    	$this->single_parts['image']['messages_graph']['url']		= $this->images_folder.'bar.gif';
    	
    	/** ----------------------
    	/**  Calculate
    	/** ----------------------*/
    	
    	if ($this->total_messages > 0 && $this->storage_limit > 0)
    	{
    		$this->single_parts['lang']['usage_percent'] 			= round(($this->total_messages / $this->storage_limit) * 100, 1);
    		$this->single_parts['image']['messages_graph']['width'] = ceil(($this->total_messages / $this->storage_limit) * $this->graph_width);
    		
    		/* We are subtracting some pixels to take into account CSS padding (~6px) */
    		
    		if ($this->single_parts['image']['messages_graph']['width'] > 7)
    		{
    			$this->single_parts['image']['messages_graph']['width'] -= 6;
    		}
 			
 			if ($this->single_parts['image']['messages_graph']['width'] > $this->graph_width)
 			{
 				$this->single_parts['image']['messages_graph']['width'] = $this->graph_width;
 			}
 		}
 		
 		$this->single_parts['lang']['storage_status']		= str_replace(array('%x', '%y'), array($this->single_parts['lang']['total_messages'], $this->single_parts['lang']['max_messages']), $LANG->line('storage_status'));
    	$this->single_parts['lang']['storage_percentage']	= str_replace('%x', $this->single_parts['lang']['usage_percent'], $LANG->line('storage_percentage'));
 	}
 	/* END */


	/** -----------------------------------
    /**  Modify Messages Request
    /** -----------------------------------*/

    function modify_messages()
    {
    	global $LANG, $DB, $IN, $FNS, $SESS;
    	
    	if ( ! $IN->GBL('toggle', 'POST'))
        {
        	$folder_id = ( ! $IN->GBL('this_folder')) ? 1 : $IN->GBL('this_folder');
        	
        	return $this->view_folder($folder_id);
        }
        
        
        foreach ($_POST as $key => $val)
        { 
			if (strstr($key, 'toggle') AND ! is_array($val))
			{
				if ($IN->GBL('daction', 'POST') == 'delete')
				{
					if (substr($val, 0, 1) == 'd')
					{
						// We're deleting a draft
						$DB->query("DELETE FROM exp_message_data
        							WHERE sender_id = '{$this->member_id}'
        							AND message_id = '".$DB->escape_str(substr($val, 1))."'
        							AND message_status = 'draft'");
					}
					else
					{
						$DB->query("UPDATE exp_message_copies SET message_deleted = 'y', message_read = 'y'
        							WHERE recipient_id = '{$this->member_id}'
        							AND copy_id = '".$DB->escape_str($val)."'");
        							
						/** ----------------------------------
						/**  Reduce exp_members.private_messages
						/** ----------------------------------*/
						
						// quick sanity check juuuuust in case
						if ($SESS->userdata['private_messages'] > 0)
						{
							$DB->query("UPDATE exp_members SET private_messages = private_messages - 1
										WHERE member_id = '{$this->member_id}'");

							$SESS->userdata['private_messages']--;							
						}
        			}
        		}
        		elseif ($IN->GBL('daction', 'POST') == 'reply')
				{
					$this->hide_preview = TRUE;
					return $this->compose($val);
				}
				elseif ($IN->GBL('daction', 'POST')  == 'reply_all')
				{
					$this->hide_preview = TRUE;
					return $this->compose($val);
				}
				elseif ($IN->GBL('daction', 'POST') == 'forward')
				{
					$this->hide_preview = TRUE;
					return $this->compose($val);
				}
        		elseif ($IN->GBL('moveto', 'POST') && $IN->GBL('moveto', 'POST') != 'none')
				{
					$folder_id = $IN->GBL('moveto', 'POST');
					
					if (is_numeric($folder_id) && $folder_id <= $this->max_folders && isset($this->folders[$folder_id]))
					{
						$DB->query("UPDATE exp_message_copies 
									SET message_deleted = 'n',
									message_folder = '".$DB->escape_str($folder_id)."'
        						    WHERE recipient_id = '{$this->member_id}'
        						    AND copy_id = '".$DB->escape_str($val)."'");
					}
				}
				elseif ($IN->GBL('copyto', 'POST') && $IN->GBL('copyto', 'POST') != 'none')
				{
					$folder_id = $IN->GBL('copyto', 'POST');
					
					if (is_numeric($folder_id) && $folder_id <= $this->max_folders && isset($this->folders[$folder_id]))
					{
						$query = $DB->query("SELECT * FROM exp_message_copies 
											 WHERE recipient_id = '{$this->member_id}'
        						    		 AND copy_id = '".$DB->escape_str($val)."'");
        						    		 
        				$query->row['copy_id'] = '';
        				$query->row['message_folder']  = $DB->escape_str($folder_id);
        				$query->row['message_deleted'] = 'n';
        				
        				$DB->query($DB->insert_string('exp_message_copies', $query->row), TRUE);
					}
				}
			}
		}
		
		if ($this->allegiance == 'user')
    	{
    		$url = $this->base_url.'view_folder/'.$IN->GBL('this_folder', 'POST').'/';
    	}
    	else
    	{
    		$url = $this->base_url.'view_folder'.AMP.'folder='.$IN->GBL('this_folder', 'POST');
    	}
        
        $FNS->redirect($url);
        exit;
 	}
 	/* END */
 	
 	
	/** -----------------------------------
    /**  Erase Messages
    /** -----------------------------------*/

    function erase($delete='')
    {
    	global $FNS, $DB;
    	
    	// -------------------------------------------
    	//  If this is the last copy of a message, 
    	//  then perhaps we should erase the 
    	//  exp_message_data too, no?
    	// -------------------------------------------
    	
    	if (is_array($delete) && sizeof($delete) > 0)
    	{
    		$query = $DB->query("SELECT message_id, copy_id FROM exp_message_copies 
    				        	 WHERE recipient_id = '{$this->member_id}'
    					         AND copy_id IN ('".implode("','", $delete)."')");
    	}
    	else
    	{
    		$query = $DB->query("SELECT message_id, copy_id FROM exp_message_copies 
    				        	 WHERE recipient_id = '{$this->member_id}'
    					         AND message_deleted = 'y'");
    	}
    	
    	if ($query->num_rows > 0)
    	{
    		$copy_ids	 = array();
    		$message_ids = array();
    		
    		foreach($query->result as $row)
    		{
    			$copy_ids[] = $row['copy_id']; 
    			
    			/*  The reason we are using an implode on the $copy_ids array
    				is because there is a very small possibility that through
    				the copy and move commands that a single member might have
    				the last two remaining copies of a message and delete them
    				at the same time.  Thus, a normal copy_id != $row['copy_id']
    				would not work effectively in this query to detemine if the
    				exp_message_data table should have this message removed.
    			*/
    			
    			$results = $DB->query("SELECT COUNT(*) AS count FROM exp_message_copies
    								   WHERE message_id = '".$DB->escape_str($row['message_id'])."'
    								   AND copy_id NOT IN ('".implode("','", $copy_ids)."')");
    			
    			if ($results->row['count'] == 0)
    			{
    				$message_ids[]	= $row['message_id'];
    			}
    		}
    	
    		if (sizeof($message_ids) > 0)
    		{
    			$DB->query("DELETE FROM exp_message_data WHERE message_id IN ('".implode("','", $message_ids)."')");
    			
    			// Remove any attachments as well
    			
    			$query = $DB->query("SELECT attachment_location FROM exp_message_attachments WHERE message_id IN ('".implode("','", $message_ids)."')");
							  
				if ($query->num_rows > 0)
				{
					foreach ($query->result as $row)
					{
						@unlink($row['attachment_location']);
					}
					
					$DB->query("DELETE FROM exp_message_attachments WHERE message_id IN ('".implode("','", $message_ids)."')");			
				}
    		}
    	
    		$DB->query("DELETE FROM exp_message_copies WHERE copy_id IN ('".implode("','", $copy_ids)."')");		
    	}
    	
    	/** ---------------------------------------
    	/**  UPDATE Unread Message Count
    	/** ---------------------------------------*/
    	
    	$query = $DB->query("SELECT COUNT(*) AS count FROM exp_message_copies 
    						 WHERE recipient_id = '{$this->member_id}'
    						 AND message_read = 'n'");
    						 
    	$results = $DB->query("UPDATE exp_members SET private_messages = '".$DB->escape_str($query->row['count'])."'
    						   WHERE member_id = '{$this->member_id}'");
    	
    	
    	if (is_array($delete) && sizeof($delete) > 0)
    	{
    		return;
    	}
    				
    	/** ----------------------------------------
    	/**  Redirect Back to Inbox
    	/** ----------------------------------------*/
    	
    	$FNS->redirect($this->_create_path('inbox'));
        exit;
 	}
 	/* END */
 	
 	
 	
 	/** -----------------------------------
    /**  Convert Recipients to Valid List
    /** -----------------------------------*/

    function convert_recipients($str, $type='string', $by='screen_name')
    {
    	global $DB; 
    	
    	/** -------------------------------------
    	/**  Retrieve Protected Names
    	/** -------------------------------------*/
    	
    	$protected = array();
    	
    	if (preg_match_all("/\<(.*?)\>/", $str, $matches))
    	{
    		for($i=0, $s=sizeof($matches['1']); $i < $s; ++$i)
    		{
    			$protected[] = $matches['1'][$i];
    			$str = str_replace($matches['0'][$i], '', $str);
    		}
    	}
    	
    	/** -------------------------------------
    	/**  Clean Up Remaining and Combine
    	/** -------------------------------------*/
    	
    	$str = str_replace('|', ',', $str);
    	$str = str_replace(',,', ',', $str);
    	$str = preg_replace("/\s*,\s*/", ',', $str);
    	
    	if (substr($str, -1) == ',')
    	{
    		$str = substr($str, 0, -1);
    	}
    	
    	if (substr($str, 0, 1) == ',')
    	{
    		$str = substr($str, 1);
    	}
    	
    	$temp_list  = array_merge(explode(',', $str), $protected);
    	$list		= array();
    	
    	for($i=0, $s = sizeof($temp_list); $i < $s; ++$i)
    	{
    		if(trim($temp_list[$i]) != '')
    		{
    			$list[] = $DB->escape_str($temp_list[$i]);
    		}
    	}
    	
    	if (sizeof($list) == 0)
    	{
    		if ($type == 'array')
    		{
    			return array();
    		}
    		else
    		{
    			$str = '';
    			return $str;
    		}
    	}
    	
    	
    	/** -------------------------------------
    	/**  Check for matches in the database
    	/** -------------------------------------*/
    	
    	if ( ! is_numeric($list['0']))
    	{
    		$query = $DB->query("SELECT {$by} FROM exp_members 
    							 WHERE screen_name IN ('".implode("','", $list)."')");
    	}
    	else
    	{
    		$query = $DB->query("SELECT {$by} FROM exp_members 
    							 WHERE member_id IN ('".implode("','", $list)."')");
    	}
    	
    	$array = array();
    		
    	if ($query->num_rows > 0)
    	{
    		foreach($query->result as $row)
    		{
    			$array[] = ($by == 'screen_name') ? '<'.$row[$by].'>' : $row[$by];
    		}
    		
    		$array = array_unique($array);
    		sort($array);
    	}
    	
    	// ----------------------------------
    	//  Do we have as many returned as we had in our initial list?
    	//  If not, we will be outputting an error in the send_message() function
    	// ----------------------------------
    	
    	if (count($list) != count($array))
    	{
    		$this->invalid_name = TRUE;
    	}
    	
    	if ($type == 'array')
    	{
    		return $array;
    	}
    	else
    	{
    		$str = implode(', ', $array);
    		
    		return $str;
    	}
   	}
   	/* END */

 	

    
    /** -------------------------------------
    /**  Check if User is Over Storage Amount
    /** -------------------------------------*/

	function _check_overflow($id)
	{
    	global $DB, $SESS, $PREFS;
    	
    	/** ---------------------------------
    	/**  Check Member Group Permissions
    	/** ---------------------------------*/
    	
    	$sql = "SELECT exp_member_groups.group_id, exp_member_groups.can_send_private_messages, exp_member_groups.prv_msg_storage_limit, exp_members.accept_messages
				FROM exp_members, exp_member_groups
    			WHERE exp_members.group_id = exp_member_groups.group_id
    			AND exp_member_groups.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'
    			AND exp_members.member_id = '".$DB->escape_str($id)."'";
    			
    	$query = $DB->query($sql);
    	
		if ($query->num_rows > 0)
		{
			// Super Admins have ALL the fun
			if ($query->row['group_id'] == 1)
			{
				return TRUE;
			}
			elseif ($query->row['can_send_private_messages'] != 'y' OR $query->row['accept_messages'] != 'y')
			{
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
    	
    	/** -------------------------------
    	/**  Check Message Count
    	/** -------------------------------*/
    	
		$user_storage_limit = $query->row['prv_msg_storage_limit'];
		
    	if ($user_storage_limit == 0)
    	{
    		return TRUE;
    	}
    	
    	$sql = "SELECT COUNT(exp_message_copies.copy_id) AS count 
    			FROM exp_message_copies
    			WHERE exp_message_copies.recipient_id = '{$id}'
    			AND exp_message_copies.message_deleted = 'n'";
    			
    	$query = $DB->query($sql);

    	if ($query->row['count'] >= $user_storage_limit)
    	{
    		return FALSE;
    	}
    	
    	return TRUE;
    }
    /* END */
    
    /** -----------------------------------
    /**  Compose to Member
    /** -----------------------------------*/

    function pm()
    {
		global $IN;

    	if ($this->allegiance == 'cp' && $IN->GBL('mid') !== FALSE && is_numeric($IN->GBL('mid')))
    	{
    		$_GET['recipients'] = $IN->GBL('mid');
    	}
    	elseif ($this->allegiance == 'user' && $this->cur_id != '' && is_numeric($this->cur_id))
    	{
    		$_GET['recipients'] = $this->cur_id;
    		$this->cur_id = '';
    	}
    	
    	$this->compose();
    }
    /* END */
    
 	

	/** -----------------------------------
    /**  Compose New Message Form
    /** -----------------------------------*/

    function compose($id = '', $errors='')
    {
    	global $LANG, $DB, $PREFS, $IN, $FNS;
    	
    	if ($id == '')
    	{
    		if ($this->allegiance == 'cp' && $IN->GBL('msg') !== FALSE && is_numeric($IN->GBL('msg')))
    		{
    			$id = $IN->GBL('msg');
    			$this->hide_preview = TRUE;
    		}
    		elseif ($this->allegiance == 'user' && $this->cur_id != '' && is_numeric($this->cur_id))
    		{
    			$id = $this->cur_id;
    			$this->hide_preview = TRUE;
    		}
    	}
    	
    	$template = $this->retrieve_template('message_compose');
    	
    	/** --------------------------------
    	/**  Form Declaration and Hidden Form Fields
    	/** --------------------------------*/
    	
    	$hidden = array();
    	
    	if ($id != '' && $IN->GBL('daction') != 'forward' && $IN->GBL('daction') != 'reply' && $IN->GBL('daction') != 'reply_all')
    	{
    		$hidden['message_id'] = $id;
    	}
    	
    	if ($IN->GBL('daction') == 'forward')
    	{
    		$hidden['create_attach'] = 'y';
    	}
    	
    	if ($IN->GBL('daction') == 'forward' OR $IN->GBL('forwarding') !== FALSE)
    	{
    		$hidden['forwarding'] = ($IN->GBL('daction') == 'forward') ? $id : $IN->GBL('forwarding');
    	}
    	
    	if ($IN->GBL('daction') == 'reply_all' OR $IN->GBL('daction') == 'reply' OR $IN->GBL('replying') !== FALSE)
    	{
    		$hidden['replying'] = ($IN->GBL('daction') == 'reply' OR $IN->GBL('daction') == 'reply_all') ?  $id : $IN->GBL('replying');
    	}
    	
    	
    	$details = array('action'			=> $this->_create_path('send_message'),
    					 'id'				=> 'submit_message',
    					 'enctype'			=> 'multi',
    					 'secure'			=> ($this->allegiance == 'cp') ? FALSE : TRUE
    					 );  	
    	
    	/** -------------------------------------
    	/**  Default Values for Form
    	/** -------------------------------------*/
    	
    	$this->single_parts['include']['emoticons']	= $this->emoticons(); 
    	$this->single_parts['include']['hidden_js']	= $this->hidden_js();
    	
    	$this->single_parts['image']['search_glass']	= '<img src="'.$this->images_folder.'search_glass.gif" style="border: 0px" width="12" height="12" alt="Search Glass" />';
    	$this->single_parts['include']['search']['recipients']  = '<a href="#" title="{lang:member_search}" onclick="perform_search(1); return false;">'.$this->single_parts['image']['search_glass'].'</a>';
    	$this->single_parts['include']['search']['cc']			= '<a href="#" title="{lang:member_search}" onclick="perform_search(2); return false;">'.$this->single_parts['image']['search_glass'].'</a>';
    	$this->single_parts['include']['search_js']				= $this->search_js();
    	$this->single_parts['include']['text_counter_js']		= $this->text_counter_js();
    	   	
    	$this->conditionals['attachments_allowed']    = $this->attach_allowed;
    	$this->conditionals['attachments_exist']   	  = 'n';
    	$this->single_parts['include']['attachments'] = '';
    	$this->single_parts['lang']['max_file_size']  = $this->attach_maxsize;
    	$this->single_parts['lang']['max_chars']	  = $this->max_chars;
    	
    	$this->single_parts['input']['tracking_checked']  = "";
    	$this->single_parts['input']['sent_copy_checked'] = '';
    	$this->single_parts['input']['hide_cc_checked']	  = '';
    	
    	$this->single_parts['include']['compose_header_js']		= $this->compose_header_js();
    	$this->single_parts['include']['dynamic_address_book']	= $this->dynamic_address_book();
    	$this->single_parts['include']['submission_error']		= '';
    	    	
    	$this->header_javascript .= $this->compose_header_js();
    	
    	/** -------------------------------------
    	/**  Spell Check Related Values
    	/** -------------------------------------*/
    	
    	$this->single_parts['include']['spellcheck_js']  = ($this->spellcheck_enabled !== TRUE) ? '' : $this->spellcheck_js(); // goes first for enabled check
		$this->single_parts['path']['spellcheck_iframe'] = ($this->spellcheck_enabled !== TRUE) ? '' : $this->_create_path('spellcheck_iframe');
		$this->conditionals['spellcheck']				 = ($this->spellcheck_enabled !== TRUE) ? 'n' : 'y';
	
    	/** -------------------------------------
		/**  Create the HTML formatting buttons
		/** -------------------------------------*/
	
		$this->single_parts['include']['html_formatting_buttons'] = '';
		
		if ( ! class_exists('Html_buttons'))
		{
			if (include_once(PATH_LIB.'html_buttons'.EXT))
			{
				$BUTT = new Html_buttons();
				$button_include = $BUTT->create_buttons();
				
				if ($this->allegiance == 'cp')
				{
					$button_include = str_replace('htmlButtonOff', 'htmlButtonA', $button_include);
					$button_include = str_replace('htmlButtonOn', 'htmlButtonB', $button_include);
				}
				
				/** -------------------------------
				/**  Fixes a small problem with single and double quotes
				/** -------------------------------*/
				
				$this->single_parts['include']['html_formatting_buttons'] = str_replace("document.getElementById('submit_post').", 
																						"document.getElementById('submit_message').", 
																						$button_include);
			}
		}
		
		/** ----------------------------------------
		/**  Preview, Reply, Forward or New Entry?
		/** ----------------------------------------*/
		
		if ($id != '' && is_numeric($id))
    	{
    		if ($IN->GBL('daction') !== FALSE && ($IN->GBL('daction', 'POST') == 'reply' OR $IN->GBL('daction', 'POST') == 'reply_all' OR $IN->GBL('daction', 'POST') == 'forward'))
    		{
    			$data = $this->_message_data($id, '', $this->member_id);
    			
    			$booger['hidden_fields'] = array('message_id' => $data['id'], 'forward' => 'y');
    			
    			$prefix = ($IN->GBL('daction', 'POST') == 'forward') ? 'forward_prefix' : 'reply_prefix';
    			$prefix = (substr($data['subject'], 0, strlen($LANG->line($prefix))) == $LANG->line($prefix)) ? '' : '{lang:'.$prefix.'}';
    			
    			$this->single_parts['input']['subject']				= ($data === FALSE) ? '' : $prefix.$data['subject'];
				$this->single_parts['input']['body']   				= ($data === FALSE) ? '' : NL.NL.NL.'[quote]'.NL.$data['body'].NL.'[/quote]';
				$this->single_parts['input']['recipients']			= ($data === FALSE OR $IN->GBL('daction', 'POST') == 'forward') ? '' : $this->convert_recipients($data['sender_id']);
				
				if ($data === FALSE OR $IN->GBL('daction', 'POST') != 'reply_all')
				{
					$this->single_parts['input']['cc'] = '';
				}
				else
				{
					$cc = $this->convert_recipients($data['recipients'].','.$data['cc']);
					$x = explode(', ', $cc);
					$y = explode(', ', $this->single_parts['input']['recipients']);
					
					// Make sure CC does not contain members in Recipients
					$cc = array_diff($x, $y);
					
					$this->single_parts['input']['cc'] = implode(', ', $cc);
				}
				
    			$this->single_parts['include']['preview_message'] 	= '';	
    			
				if ($IN->GBL('daction', 'POST') == 'forward' && $data !== FALSE && sizeof($data['attachments']) > 0)
				{
					$this->conditionals['attachments_allowed']		= (sizeof($data['attachments']) >= $this->max_attachments) ? 'n' : 'y';
					$this->conditionals['attachments_exist']		= 'y';
					$this->single_parts['include']['attachments']	= $this->_display_attachments($data['attachments']);
    				
    				$hidden['attach'] = implode('|', $data['attach']);
    			}
    		}
    		else
    		{
    			$data = $this->_message_data($id, $this->member_id);
    	
    			$this->single_parts['input']['subject']				= ($data === FALSE) ? '' : $data['subject'];
				$this->single_parts['input']['body']   				= ($data === FALSE) ? '' : $data['body'];
				$this->single_parts['input']['recipients']			= ($data === FALSE) ? '' : $this->convert_recipients($data['recipients']);
				$this->single_parts['input']['cc']					= ($data === FALSE) ? '' : $this->convert_recipients($data['cc']);
				$this->single_parts['include']['preview_message'] 	= ($data === FALSE OR $this->hide_preview === TRUE) ? '' : $data['preview'];
				
				$this->single_parts['input']['tracking_checked']  = ($data === FALSE OR $data['tracking'] == 'n') ? '' : "checked='checked'";
    			$this->single_parts['input']['sent_copy_checked']   = ($data === FALSE OR $data['sent_copy'] == 'n')  ? '' : "checked='checked'";;
    			$this->single_parts['input']['hide_cc_checked']   = ($data === FALSE OR $data['hide_cc'] == 'n')  ? '' : "checked='checked'";	
				
				if ($data !== FALSE && sizeof($data['attachments']) > 0)
				{
					$this->conditionals['attachments_allowed']		= (sizeof($data['attachments']) >= $this->max_attachments) ? 'n' : 'y';
					$this->conditionals['attachments_exist']		= 'y';
					$this->single_parts['include']['attachments']	= $this->_display_attachments($data['attachments']);
    				
    				$hidden['attach'] = implode('|', $data['attach']);
    			}
    		}
    	}
    	else
    	{
			$this->single_parts['input']['subject']    		  = ( ! $IN->GBL('subject')) ? '' : $IN->GBL('subject');
    		$this->single_parts['input']['body']    		  = ( ! $IN->GBL('body')) ? '' :  $IN->GBL('body');
    		$this->single_parts['input']['cc']				  = '';
    		$this->single_parts['input']['recipients']		  = ( ! $IN->GBL('recipients')) ? '' : $this->convert_recipients($IN->GBL('recipients'));
    		$this->single_parts['include']['preview_message'] = '';		
 		}
 		
 		$details['hidden_fields'] = $hidden;
 		
 		$this->single_parts['form']['form_declaration']['messages'] = $FNS->form_declaration($details);
 		
 		// --------------------------------------------
 		//  If upload path is not specified we 
 		//  override all attachment related settings
 		// --------------------------------------------
 		
 		if ($this->upload_path == '')
 		{
 			$this->conditionals['attachments_allowed']    = 'n';
    		$this->conditionals['attachments_exist']   	  = 'n';
    		$this->single_parts['include']['attachments'] = '';
 		}
 		
 		/** ---------------------------------------
 		/**  Error Message Displaying, if any
 		/** ---------------------------------------*/
 		
 		if (is_array($errors) && sizeof($errors) > 0)
 		{
 			$this->single_parts['lang']['error_message'] = implode(BR, $errors);
 			
 			$this->single_parts['include']['submission_error'] = $this->_process_template($this->retrieve_template('message_submission_error'));
 		}
    	
    	/** ----------------------------------------
		/**  Return the Compose Form Contents
		/** ----------------------------------------*/
		
		$this->title = $LANG->line('compose_message');
		$this->crumb = $LANG->line('compose_message');
		$this->return_data = $this->_process_template($template);
 	
 	}
 	/* END */
 		
 	
 	//----------------------------------------
	// 	Fetch Message Data
	//  Massage that message data, si vous plait
	//----------------------------------------
 	
 	function _message_data($id, $sender='', $recipient='')
 	{
 		global $DB, $LOC;
 		
 		$data = array();
 		
 		// ------------------------------------------
 		//  If $recipient is set, this is a message
 		//  being viewed. So, $id is actually the copy_id
 		//  in the exp_message_copies table.
 		// -----------------------------------------
 		
 		if ($recipient != '' && is_numeric($recipient))
 		{
 			$query = $DB->query("SELECT message_id, message_folder, message_read FROM exp_message_copies 
 								 WHERE copy_id = '".$DB->escape_str($id)."'
 								 AND recipient_id = '".$DB->escape_str($recipient)."'");
 								 
 			if ($query->num_rows == 0)
 			{
 				return FALSE;
 			}
 			
 			$mid = $query->row['message_id'];
 			$data['folder_id'] = $query->row['message_folder'];
 			$data['message_read'] = $query->row['message_read'];
 		}
 		else
 		{
 			$mid = $id;
 		}
 		
 		$sql = "SELECT * FROM exp_message_data WHERE message_id = '".$DB->escape_str($mid)."'";
 		
 		if ($sender != '' && is_numeric($sender))
 		{
 			$sql .= " AND sender_id = '".$DB->escape_str($sender)."'";
 		}
 							 
 		$query = $DB->query($sql);
 				
 		if ($query->num_rows == 0)
 		{
 			return FALSE;
 		}
 		
 		/** ---------------------------------
 		/**  Do a Little Data Work
 		/** ---------------------------------*/
 		
 		foreach($query->row as $key => $value)
 		{
 			$data[str_replace('message_', '', $key)] = $value;
 		}
 		
 		$data = str_replace('message_', '', $data);
 		
 		$data['recipients']  = str_replace('|', ', ', $data['recipients']);
 		$data['cc'] 		 = str_replace('|', ', ', $data['cc']);
 		$data['attachments'] = array();
 		$data['date']		 = $LOC->set_human_time($data['date']);
 		
 		$results = $DB->query("SELECT screen_name FROM exp_members WHERE member_id = '".$DB->escape_str($data['sender_id'])."'");
 		
 		$data['sender'] = $results->row['screen_name'];
 		
 		/** ---------------------------------
 		/**  Create Preview of Message
 		/** ---------------------------------*/
 		
 		if ( ! class_exists('Typography'))
		{
			require PATH_CORE.'core.typography'.EXT;
		}
		
		$TYPE = new Typography;
		$TYPE->highlight_code = TRUE;

		$this->single_parts['include']['parsed_message'] = $TYPE->parse_type(stripslashes($data['body']), 
									 		 								  array(
									 		 								  'text_format'   => 'xhtml',
									 		 								  'html_format'   => $this->html_format,
									 		 								  'auto_links'    => $this->auto_links,
									 		 								  'allow_img_url' => 'n'
									 		 								 ));
											
		$data['preview'] = $this->_process_template($this->retrieve_template('preview_message'));
 		
 		/** ---------------------------------
 		/**  Fetch Attachment Information
 		/** ---------------------------------*/
 							 
 		if ($query->row['message_attachments'] == 'y')
 		{
 			$results = $DB->query("SELECT attachment_name, attachment_id, attachment_size, attachment_hash
 								   FROM exp_message_attachments
 								   WHERE message_id = '".$DB->escape_str($mid)."'");
 								   
 			if ($results->num_rows > 0)
 			{
 				$data['attach'] = array();
 				
 				foreach($results->result as $row)
 				{
 					$data['attachments'][] = $row;
 					$data['attach'][] = $row['attachment_id'];
 				}
 			}
 		}
 		
 		return $data;
 	}
 	/* END */
 	
 	
	//----------------------------------------
	// 	Output the displaying of attachments
	//  for the Compose Page
	//----------------------------------------
 	
 	function _display_attachments($data)
 	{
 		$main = $this->retrieve_template('message_attachments');
 		$rows = $this->retrieve_template('message_attachment_rows');
 		
 		$this->single_parts['include']['attachment_rows'] = '';
 		
 		for($i = 0, $l = sizeof($data); $i < $l; $i++)
 		{
 			foreach($data[$i] as $key => $value)
 			{
 				$this->single_parts['input'][$key] = $value;
 			}
 			
 			$this->single_parts['include']['attachment_rows'] .= $this->_process_template($rows);
 		}
 	
 		return $this->_process_template($main);
 	}
 	/* END */
 	
	//----------------------------------------
	// 	Output the displaying of attachments
	//  for the viewing of a message
	//----------------------------------------
 	
 	function _attachment_links($data)
 	{
 		$rows = $this->retrieve_template('message_attachment_link');
 		
 		if ($this->allegiance == 'user')
    	{
    		$url = $this->base_url.'attachment/';
    	}
    	else
    	{
    		$url = $this->base_url.'attachment'.AMP.'aid=';
    	}
 		
 		$r = '';
 		
 		for($i = 0, $l = sizeof($data); $i < $l; $i++)
 		{
 			foreach($data[$i] as $key => $value)
 			{
 				$this->single_parts['input'][$key] = $value;
 			}
 			
 			$this->single_parts['path']['download_link'] = ($this->allegiance == 'user') ? $url.$data[$i]['attachment_hash'].'/' : $url.$data[$i]['attachment_hash'];
 			
 			$r .= $this->_process_template($rows);
 		}
 		
 		return $r;
 	}
 	/* END */
 	 	

 	/** -----------------------------------
    /**  Send Message
    /** -----------------------------------*/

    function send_message()
    {
    	if ( ! class_exists('Messages_send'))
    	{
    		require PATH_CORE.'core.messages_send.php';
    	}
    	
    	$MS = new Messages_send();
			
		foreach(get_object_vars($this) as $key => $value)
		{
			$MS->{$key} = $value;
		}
    	
    	$MS->send_message();
    	$this->return_data = $MS->return_data;
    }
    /* END */

	
 	
 	/** -----------------------------------
    /**  View a Single Message
    /** -----------------------------------*/

    function view_message($copy_id = '')
    {
    	global $LANG, $DB, $LOC, $IN, $FNS, $SESS, $REGX;
    	
    	/** ----------------------------------- 
    	/**  What Private Message?
    	/** -----------------------------------*/
    	
    	if ($copy_id == '')
    	{
    		if ($this->allegiance == 'cp' && $IN->GBL('msg') !== FALSE && is_numeric($IN->GBL('msg')))
    		{
    			$copy_id = $IN->GBL('msg');
    		}
    		elseif ($this->allegiance == 'user' && $this->cur_id != '' && is_numeric($this->cur_id))
    		{
    			$copy_id = $this->cur_id;
    		}
    	}
    	
    	/** ----------------------------------- 
    	/**  Retrieve Data
    	/** -----------------------------------*/
    	
    	$data = $this->_message_data($copy_id, '', $this->member_id);
    	
    	if ($data === FALSE)
    	{
    		return $this->_error_page('invalid_message');
    	}
    	
    	$this->single_parts['include']['subject']				= $data['subject'];
		$this->single_parts['include']['body']   				= $data['body'];
		$this->single_parts['include']['recipients']			= $REGX->xml_convert($this->convert_recipients($data['recipients'], 'string', 'screen_name'));
		$this->single_parts['include']['cc']					= ($data['cc'] == '') ? '' : $REGX->xml_convert($this->convert_recipients($data['cc'], 'string', 'screen_name'));
		$this->single_parts['include']['sender']				= $data['sender'];
		$this->single_parts['include']['date'] 					= $data['date'];
		$this->conditionals['attachments_exist']				= 'n';
		
		if ($data['sender'] == $SESS->userdata['screen_name'] && isset($data['folder_id']) && $data['folder_id'] == 2)
		{
			$this->conditionals['show_cc'] = 'y';
		}
		elseif(in_array($this->member_id, explode(', ',$data['cc'])))
		{	
			$this->conditionals['show_cc'] = 'y';
			$this->single_parts['include']['cc'] = $SESS->userdata['screen_name'];
		}
		elseif($data['hide_cc'] == 'y' OR $data['cc'] == '')
		{
			$this->conditionals['show_cc'] = 'n';
		}
		else
		{
			$this->conditionals['show_cc'] = 'y';
		}
		
    	/** ----------------------------------- 
    	/**  Prepare Attachments for Display
    	/** -----------------------------------*/
			
		$this->conditionals['attachments_exist']			= (sizeof($data['attachments']) > 0) ? 'y' : 'n';
		$this->single_parts['include']['attachment_links']	= (sizeof($data['attachments']) > 0) ? $this->_attachment_links($data['attachments']) : '';

    	// ----------------------------------- 
    	//  Form Declaration
    	//
    	//  NOTE: $data['id'] = $message_id
    	// -----------------------------------
    	
    	$details = array('hidden_fields'	=> array('message_id' => $copy_id, 'toggle[]' => $copy_id, 'daction' => ''),
    					 'action'			=> $this->_create_path('modify_messages'),
    					 'id'				=> 'target',
    					 'enctype'			=> 'multi',
    					 'secure'			=> ($this->allegiance == 'cp') ? FALSE : TRUE
    					 );  	
    	
    	$this->single_parts['form']['form_declaration']['view_message']  = $FNS->form_declaration($details);
    	
    	/** ----------------------------------- 
    	/**  Various Components
    	/** -----------------------------------*/
    	
    	$this->current_folder = ( ! isset($data['folder_id'])) ? 'n' : $data['folder_id'];
    	$this->folders_pulldown();
    	$this->_buttons();
    	$this->single_parts['include']['hidden_js'] = $this->hidden_js();
    	
    	/** ----------------------------------
		/**  Reduce exp_members.private_messages
		/** ----------------------------------*/
		
		if ($data['message_read'] == 'n' && $SESS->userdata['private_messages'] > 0)
		{
			$DB->query("UPDATE exp_members SET private_messages = private_messages - 1
						WHERE member_id = '{$this->member_id}'");
			$SESS->userdata['private_messages']--;
    	}
    	
    	/** ----------------------------------- 
    	/**  Tracking and Status
    	/** -----------------------------------*/
    	
    	if ($data['message_read'] == 'n')
		{
    		$udata = array('message_read' => 'y', 'message_time_read' =>  $LOC->now);
    		$DB->query($DB->update_string('exp_message_copies', $udata, "copy_id = {$copy_id}"));
    	}
    	
    	/** ----------------------------------------
		/**  Return the Compose Form Contents
		/** ----------------------------------------*/
		
		$this->title = $LANG->line('private_message').' - '.$FNS->word_limiter($data['subject'], 10);
		$this->crumb = $LANG->line('private_message');
		$this->return_data = $this->_process_template($this->retrieve_template('view_message'));
 		
 	}
 	/* END */
 
	/** --------------------------------
	/**  Show Attachment
	/** --------------------------------*/
	
	function attachment()
	{
		global $IN, $DB, $LOC;
		
		if ($this->allegiance == 'cp')
		{
			$attach_hash = $IN->GBL('aid');
		}
		else
		{
			$attach_hash = $this->cur_id;
		}
		
		/** ---------------------------
		/**  Valid ID?
		/** ---------------------------*/
		
		$query = $DB->query("SELECT attachment_location, attachment_name, attachment_extension, message_id 
							 FROM exp_message_attachments WHERE attachment_hash = '".$DB->escape_str($attach_hash)."'");
	
		if ($query->num_rows == 0)
		{
			exit;
		}
		
		/** ---------------------------
		/**  Attachment for User?
		/** ---------------------------*/
		
		$results = $DB->query("SELECT COUNT(*) AS count FROM exp_message_copies 
							   WHERE message_id = '".$DB->escape_str($query->row['message_id'])."'
							   AND recipient_id = '{$this->member_id}'");
		
		if ($results->row['count'] == 0)
		{
			exit;
		}

		$filepath = $query->row['attachment_location'];
		
		$extension = strtolower(str_replace('.', '', $query->row['attachment_extension']));
        	
		if ($this->mimes == '')
		{
			include(PATH_LIB.'mimes.php');			
			$this->mimes = $mimes;
		}
        	
		if ( ! file_exists($filepath) OR ! isset($this->mimes[$extension]))
		{
			exit;
		}
		
		$DB->query("UPDATE exp_message_copies SET attachment_downloaded = 'y' 
					WHERE message_id = '".$DB->escape_str($query->row['message_id'])."'
					AND recipient_id = '{$this->member_id}'");
					
		header('Content-Disposition: filename="'.$query->row['attachment_name'].'"');		
		header('Content-Type: '.$this->mimes[$extension]);
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($filepath));
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $LOC->now).' GMT');
			
		if ( ! $fp = @fopen($filepath, 'rb'))
		{
			exit;
		}
		
		fpassthru($fp);
		@fclose($fp);
		exit;
	}
	/* END */
 	
	/** -----------------------------------
    /**  Buddies List
    /** -----------------------------------*/
    
    function buddies($message='')
    {
    	global $LANG, $FNS, $DB;
    	
    	$template = $this->retrieve_template('buddies_block_list');
    	
    	$form_details = array('hidden_fields' => array('name' => '','description' => '', 'which' => 'buddy', 'daction' => ''),
    						  'action'		  => $this->_create_path('edit_list'),
    					 	  'id'			  => 'target',
    					 	  'secure'		  => ($this->allegiance == 'cp') ? FALSE : TRUE
    					 	  );  	
    	
    	$this->single_parts['form']['form_declaration']['list'] = $FNS->form_declaration($form_details);
    	$this->single_parts['lang']['list_title'] =	$LANG->line('buddy_list');
    	$this->single_parts['include']['toggle_js'] = $this->toggle_js();
    	$this->single_parts['include']['buddy_search_js'] =  $this->buddy_search_js();
    	$this->single_parts['image']['search_glass'] = '<img src="'.$this->images_folder.'search_glass.gif" style="border: 0px" width="12" height="12" alt="Search Glass" />';
    	$this->single_parts['include']['member_search'] = '<a href="#" title="{lang:member_search}" onclick="buddy_search(1); return false;">'.$this->single_parts['image']['search_glass'].'</a>';
    	
    	
    	$this->single_parts['include']['list_rows'] = '';
    	
    	$this->_buttons('y', 'n', 'n');
    	
    	/** ----------------------------------------
    	/**  Retrieve and Output List
    	/** ----------------------------------------*/
    	
    	if ($this->buddies === FALSE)
    	{
    		$this->fetch_lists('buddy');
    	}
    	
    	$list_size = sizeof($this->buddies);
    	
    	if ($list_size == 0)
    	{
    		$this->single_parts['include']['list_rows'] .= $this->_process_template($this->retrieve_template('empty_list'));
    	}
    	else
    	{
    		$rows = $this->retrieve_template('buddies_block_row');
    		
    		for($i=0; $i < $list_size; ++$i)
    		{
    			$this->single_parts['style']				= ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
    			$this->single_parts['screen_name']			= $this->buddies[$i]['2'];
    			$this->single_parts['member_description']	= $this->buddies[$i]['3'];
    			$this->single_parts['listed_id']			= $this->buddies[$i]['4'];
    			
    			if ($this->allegiance == 'user')
    			{
    				$this->single_parts['path']['send_pm'] = $this->_create_path('pm').$this->buddies[$i]['5'].'/';
    			}
    			else
    			{
    				$this->single_parts['path']['send_pm'] = $this->_create_path('pm').AMP.'mid='.$this->buddies[$i]['5'];
    			}
    			
    			$this->single_parts['include']['list_rows'] .= $this->_process_template($rows);
    		}
    	}
    	
    	/** ----------------------------------------
		/**  Return the Trackable Messages
		/** ----------------------------------------*/
		
		$this->title = $LANG->line('buddy_list');
		$this->crumb = $LANG->line('buddy_list');
		$this->return_data = $this->_process_template($template);
    }
    /* END */
    
    
	/** -----------------------------------
    /**  Bulletin Board
    /** -----------------------------------*/
    
    function bulletin_board($message='')
    {
   		global $LANG, $DB, $OUT, $IN, $LOC, $SESS;
   		
   		$DB->query("UPDATE exp_members SET last_view_bulletins = '".$LOC->now."' WHERE member_id = '{$this->member_id}'");
   		
   		$this->title = $LANG->line('bulletin_board');
		$this->crumb = $LANG->line('bulletin_board');
		
		$this->conditionals['bulletins']		= 'n';
		$this->conditionals['no_bulletins']		 = 'y';
		$this->conditionals['paginate']			 = 'n';
		$this->conditionals['can_post_bulletin'] = ($SESS->userdata['can_send_bulletins'] == 'y') ? 'y' : 'n';
		
		$this->single_parts['include']['message'] = $message;
    	
    	$this->conditionals['message'] = ($message != '') ? 'y' : 'n';
		
		$this->single_parts['path']['send_bulletin'] = $this->_create_path('send_bulletin');
    	
    	/** ---------------------------------------
    	/**  Retrieve Bulletins
    	/** ---------------------------------------*/
    	
    	$dql = "SELECT m.screen_name, b.sender_id, b.bulletin_message, b.bulletin_date, b.bulletin_id ";
    	
    	$sql =  "FROM exp_member_bulletin_board b, exp_members m
				 WHERE b.sender_id = m.member_id
				 AND b.bulletin_group = ".$DB->escape_str($SESS->userdata['group_id'])."
				 AND bulletin_date < ".$LOC->now."
				 AND 
				 (
				 	b.bulletin_expires > ".$LOC->now."
				 	OR
				 	b.bulletin_expires = 0
				 )
				 ORDER BY b.bulletin_date DESC";
    			
    	/** ----------------------------------------
        /**  Run "count" query for pagination
        /** ----------------------------------------*/
        
		$query = $DB->query("SELECT COUNT(b.bulletin_id) AS count ".$sql);
		
		/** ----------------------------------------
        /**  If No Messages, we say so.
        /** ----------------------------------------*/
		
		if ($query->row['count'] == 0)
		{
			$this->single_parts['include']['bulletins'] = $LANG->line('message_no_bulletins');
			$this->return_data = $this->_process_template($this->retrieve_template('bulletin_board'));
			return;
		}
		
		/** ----------------------------------------
        /**  Determine Current Page
        /** ----------------------------------------*/
        
        $row_count = 0;  // How many rows shown this far (i.e. offset)
    	
    	if ($this->allegiance == 'user')
    	{
    		$row_count = $this->cur_id;
    	}
    	else
    	{
    		$row_count = ($IN->GBL('page', 'GP') === false) ? 0 : $IN->GBL('page', 'GP');
    	}
    	
    	if ( ! is_numeric($row_count))
    	{
    		$row_count = 0;
    	}
    	
    	$this->per_page = 5;
		
		$current_page = ($row_count / $this->per_page) + 1;
			
        $total_pages = intval($query->row['count'] / $this->per_page);
        
        if ($query->row['count'] % $this->per_page) 
        {
            $total_pages++;
        }
        
        $this->single_parts['include']['page_count'] = $current_page.' '.$LANG->line('of').' '.$total_pages;
    	
    	/** -----------------------------
    	/**  Do we need pagination?
    	/** -----------------------------*/
				
		$pager = ''; 		
		
		if ($query->row['count'] > $this->per_page)
		{ 											
			if ( ! class_exists('Paginate'))
			{
				require PATH_CORE.'core.paginate'.EXT;
			}
			
			$PGR = new Paginate();
			
			if ($this->allegiance == 'user')
    		{
    			$PGR->path = $this->base_url.'bulletin_board/';
    		}
    		else
    		{
    			$PGR->base_url = $this->base_url.'bulletin_board';
    			$PGR->qstr_var = 'page';
    		}
			
			$PGR->total_count 	= $query->row['count'];
			$PGR->per_page		= $this->per_page;
			$PGR->cur_page		= $row_count;
			
			$this->single_parts['include']['pagination_link'] = $PGR->show_links();
			$this->conditionals['paginate'] = 'y';
			 
			$sql .= " LIMIT ".$row_count.", ".$this->per_page;			
		}
		
		/** ----------------------------------------
        /**  Create Bulletins
        /** ----------------------------------------*/
        
        $this->conditionals['bulletins']	= 'y';
        $this->conditionals['no_bulletins']	= 'n';
        
		$folder_rows_template = $this->retrieve_template('bulletin');
		$i = 0;
		$r = '';
		
		$query = $DB->query($dql.$sql);
		
		if ($query->row['bulletin_date'] != $SESS->userdata['last_bulletin_date'])
		{
			$DB->query($DB->update_string('exp_members', array('last_bulletin_date' => $query->row['bulletin_date']), "group_id = '".$DB->escape_str($SESS->userdata['group_id'])."'"));
		}
		
		foreach($query->result as $row)
		{
			++$i;
			$data = $row;
			
			$this->conditionals['can_delete_bulletin']		= ($SESS->userdata['group_id'] == 1 OR $row['sender_id'] == $SESS->userdata['member_id']) ? 'y' : 'n';
			
			if ($this->allegiance == 'cp')
			{
				$this->single_parts['path']['delete_bulletin']	= $this->_create_path('delete_bulletin', AMP.'bulletin_id='.$row['bulletin_id']);
			}
			else
			{
				$this->single_parts['path']['delete_bulletin']	= $this->_create_path('delete_bulletin').$row['bulletin_id'].'/';
			}
			
			$data['bulletin_sender'] = $row['screen_name'];
			$data['bulletin_date'] = $LOC->set_human_time($row['bulletin_date']);
			$data['style']		   = ($i % 2) ? 'tableCellTwo' : 'tableCellOne';
    		
    		$r .= $this->_process_template($folder_rows_template, $data);
		}
		
		$this->single_parts['include']['bulletins'] = $r;
		
		/** ----------------------------------------
		/**  Return the Folder's Contents
		/** ----------------------------------------*/
		
		$this->return_data = $this->_process_template($this->retrieve_template('bulletin_board'));
    }
    /* END */
    
    /** -----------------------------------
    /**  Delete a Bulletin
    /** -----------------------------------*/
    
    function delete_bulletin()
    {
   		global $LANG, $DB, $IN, $SESS;
   		
   		if ($this->allegiance == 'cp')
		{
			if ($IN->GBL('bulletin_id') === FALSE)
			{
				return $this->bulletin_board();
			}
			
			$this->cur_id = $IN->GBL('bulletin_id');
		}
   		
   		$sql = "SELECT b.sender_id, b.bulletin_id, b.hash
   				FROM exp_member_bulletin_board b
				WHERE b.bulletin_id = '".$DB->escape_str($this->cur_id)."'";
        
		$query = $DB->query($sql);
		
		if ($query->num_rows == 0)
		{
			return $this->bulletin_board();
		}
		elseif($SESS->userdata['group_id'] != 1 && $query->row['sender_id'] != $SESS->userdata['member_id'])
		{
			return $this->bulletin_board();
		}
   		
   		$DB->query("DELETE FROM exp_member_bulletin_board WHERE hash = '".$DB->escape_str($query->row['hash'])."'");
   		
   		return $this->bulletin_board($LANG->line('bulletin_deleted'));
   	}
   	/* END */
    
    
	/** -----------------------------------
    /**  Send a Bulletin Form for Admins
    /** -----------------------------------*/
    
    function send_bulletin($message='')
    {
   		global $LANG, $DB, $OUT, $IN, $LOC, $SESS, $FNS, $PREFS;
   		
		/** -----------------------------------
		/**  Nasty Little Hobbits! No Sending!
		/** -----------------------------------*/
   		
   		if ($SESS->userdata['can_send_bulletins'] != 'y')
   		{
   			return FALSE;
   		}
   		
   		$this->title = $LANG->line('send_bulletin');
		$this->crumb = $LANG->line('send_bulletin');
		
		/** ----------------------------------------
		/**  Some Form Data
		/** ----------------------------------------*/
		
		$form_details = array('action'	=> $this->base_url.'sending_bulletin',
    					 	  'id'		=> 'sending_bulletin',
    					 	  'secure'	=> ($this->allegiance == 'cp') ? FALSE : TRUE
    					 	  );  	
    	
    	$this->single_parts['form']['form_declaration']['sending_bulletin'] = $FNS->form_declaration($form_details);
    	
		$this->single_parts['input']['bulletin_date']	 = $LOC->set_human_time($LOC->now);
		$this->single_parts['input']['bulletin_expires'] = $LOC->set_human_time($LOC->now + 30*24*60*60);
		
		$this->single_parts['include']['message'] = $message;
    	
    	$this->conditionals['message'] = ($message != '') ? 'y' : 'n';
    	
    	// Member Group IDs
		
		$english = array('Members', 'Super Admins');
		
		$sql = "SELECT group_id, group_title FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND include_in_memberlist = 'y' AND group_id NOT IN ('2', '3', '4') ";
		
		if ($SESS->userdata('group_id') != 1)
		{
			$sql .= "AND group_id != '1' ";
		}
		
		$sql .= " ORDER BY group_title";
		
		$query = $DB->query($sql);

		$menu = "<option value='0'>".$LANG->line('mbr_all_member_groups')."</option>\n";
				
		foreach ($query->result as $row)
		{
			$group_title = $row['group_title'];
		
            if (in_array($group_title, $english))
            {
                $group_title = $LANG->line(strtolower(str_replace(" ", "_", $group_title)));
            }
					
			$menu .= "<option value='".$row['group_id']."'>".$group_title."</option>\n";
		}
		
		$this->single_parts['group_id_options'] = $menu;
		
		/** ----------------------------------------
		/**  Return the Parsed Template
		/** ----------------------------------------*/
		
		$this->return_data = $this->_process_template($this->retrieve_template('bulletin_form'));
    }
    /* END */
    
    
    
	/** -----------------------------------
    /**  Send a Bulletin to Member Group
    /** -----------------------------------*/
    
    function sending_bulletin()
    {
   		global $LANG, $DB, $LOC, $SESS, $FNS, $PREFS;
   		
		/** -----------------------------------
		/**  Nasty Little Hobbits! No Sending!
		/** -----------------------------------*/
   		
   		if ($SESS->userdata['can_send_bulletins'] != 'y')
   		{
   			return FALSE;
   		}
   		
   		if ( ! isset($_POST['group_id']) OR ! is_numeric($_POST['group_id']))
		{
			return FALSE;
		}
		
		if ( ! isset($_POST['bulletin_message']) OR trim($_POST['bulletin_message']) == '')
		{
			return $this->send_bulletin();
		}
   		
   		/** ----------------------------------------
		/**  Valid Member Groups for User
		/** ----------------------------------------*/
		
		$sql = "SELECT group_id FROM exp_member_groups WHERE site_id = '".$DB->escape_str($PREFS->ini('site_id'))."' AND include_in_memberlist = 'y' AND group_id NOT IN ('2', '3', '4') ";
		
		if ($SESS->userdata('group_id') != 1)
		{
			$sql .= "AND group_id != '1' ";
		}
		
		$sql .= " ORDER BY group_title";
		
		$query = $DB->query($sql);
		
		$group = array();
				
		foreach ($query->result as $row)
		{
			if ($_POST['group_id'] == '0' OR $_POST['group_id'] == $row['group_id'])
			{
				$groups[] = $row['group_id'];
			}
		}
		
		/** ------------------------------
		/**  Suspicious use of non-valid group
		/** ------------------------------*/
		
		if (sizeof($groups) == 0)
		{
			return $this->send_bulletin();
		}
		
		$begins  = $LOC->convert_human_date_to_gmt($_POST['bulletin_date']);
		$expires = $LOC->convert_human_date_to_gmt($_POST['bulletin_expires']);
		
		if ($begins == 0)
		{
			$begins = $LOC->now;
		}
		
		if ($expires == 0)
		{
			$expires = $LOC->now + 30*24*60*60;
		}
		
		$data = array(	'sender_id'			=> $this->member_id,
						'bulletin_date'		=> $begins,
						'hash'				=> $FNS->random('alpha', 10),
						'bulletin_expires'	=> $expires,
						'bulletin_message'	=> $_POST['bulletin_message']
					 );
					 
		foreach($groups as $group_id)
		{
			$data['bulletin_group'] = $group_id;
			
			$DB->query($DB->insert_string('exp_member_bulletin_board', $data));
			
			if ($LOC->now >= $begins)
			{
				$DB->query($DB->update_string('exp_members', array('last_bulletin_date' => $LOC->now), "group_id = '".$DB->escape_str($group_id)."'"));
			}
		}
		
		/** ----------------------------------------
		/**  Return with Success Message
		/** ----------------------------------------*/
		
		$this->send_bulletin($LANG->line('bulletin_success'));
    }
    /* END */
	
    
	/** -----------------------------------
    /**  Edit Buddies and Block Lists
    /** -----------------------------------*/
    
    function edit_list()
    {
    	global $LANG, $IN, $FNS, $DB;
    	
    	if ($IN->GBL('which') === FALSE OR $IN->GBL('which') == 'buddy')
    	{
    		$which = 'buddy';
    	}
    	else
    	{
    		$which = 'blocked';
    	}
    	
    	/** -----------------------------------
    	/**  Add to List
    	/** -----------------------------------*/
    	
    	if ( ! $IN->GBL('toggle', 'POST'))
        {
        	if ($IN->GBL('name') === FALSE OR $IN->GBL('description') === FALSE OR $IN->GBL('name') == '')
        	{
        		if ($which == 'blocked')
        		{
        			return $this->_error_page('missing_required_field');
        		}
        		else
        		{
        			return $this->_error_page('missing_required_field');
        		}
        	}
        	
        	$person = $this->convert_recipients($IN->GBL('name'), 'array', 'member_id');
        	
        	if (sizeof($person) == 0)
        	{
        		if ($which == 'blocked')
        		{
        			return $this->_error_page('invalid_username');
        		}
        		else
        		{
        			return $this->_error_page('invalid_username');
        		}
        	}
        	
        	$data = array('member_id'			=> $this->member_id,
        				  'listed_description'	=> $FNS->char_limiter($_POST['description'], 50),
        				  'listed_type'			=> $which);
        	
        	for ($i=0, $s = sizeof($person); $i < $s; ++$i)
        	{
        		if ($this->member_id == $person[$i])
        		{
        			return $this->_error_page('invalid_username');
        		}
        		elseif($which == 'buddy' && sizeof($this->goodies) > 0 && in_array($person[$i], $this->goodies))
        		{
        			return $this->_error_page('invalid_username');
        		}
        		elseif($which == 'blocked' && sizeof($this->baddies) > 0 && in_array($person[$i], $this->baddies))
        		{
        			return $this->_error_page('invalid_username');
        		}
        		
        		$data['listed_member'] = $person[$i]; 
        		$DB->query($DB->insert_string('exp_message_listed', $data));
        		
        		if ($which == 'blocked')
        		{
        			$DB->query("DELETE FROM exp_message_copies 
        						WHERE sender_id = '".$DB->escape_str($person[$i])."'
        						AND recipient_id = '{$this->member_id}'");
        		}
        	}
        	
        	$this->fetch_lists($which);
        	
        	if ($which == 'blocked')
        	{
        		return $this->blocked();
        	}
        	else
        	{
        		return $this->buddies();
        	}
        }
        
        /** -----------------------------------
        /**  Delete From List
        /** -----------------------------------*/
        
        foreach ($_POST as $key => $val)
        { 
			if (strstr($key, 'toggle') AND ! is_array($val))
			{
				if ($IN->GBL('daction', 'POST') == 'delete')
				{
					$DB->query("DELETE FROM exp_message_listed
        						WHERE member_id = '{$this->member_id}'
        						AND listed_id = '".$DB->escape_str($val)."'");
        		}
        	}
        }
        		
        		
    	if ($which == 'blocked')
        {
        	return $this->blocked();
        }
        else
        {
        	return $this->buddies();
        }
    }
    /* END */
    
    
    /** -----------------------------------
    /**  Add Buddy
    /** -----------------------------------*/
    
    function add_buddy()
    {
    	global $IN;
    	
    	if ($this->buddies === FALSE)
    	{
    		$this->fetch_lists('buddy');
    	}
    	
    	return $this->add_list_member('buddy', ($this->allegiance == 'user') ? $this->cur_id : $IN->GBL('mid'));
    }
    /* END */
    
    
	/** -----------------------------------
    /**  Add Naughty, Poo-poo head
    /** -----------------------------------*/
    
    function add_block()
    {
    	global $IN;
    	
    	if ($this->blocked === FALSE)
    	{
    		$this->fetch_lists('blocked');
    	}
    	
    	return $this->add_list_member('blocked', ($this->allegiance == 'user') ? $this->cur_id : $IN->GBL('mid'));
    }
    /* END */
    
	/** -----------------------------------
    /**  Add to Buddy or Block List
    /** -----------------------------------*/
    
    function add_list_member($which='buddy', $id='')
    {
    	global $DB;
    	
    	/** -----------------------------------
    	/**  Add to List
    	/** -----------------------------------*/
    	
    	if (empty($id))
        {
        	if ($which == 'blocked')
        	{
        		return $this->blocked();
        	}
        	else
        	{
        		return $this->buddies();
        	}
        }
        	
        $person = $this->convert_recipients($id, 'array', 'member_id');
        	
        if (sizeof($person) == 0)
        {
        	if ($which == 'blocked')
        	{
        		return $this->blocked();
        	}
        	else
        	{
        		return $this->buddies();
        	}
        }
        	
        $data = array('member_id'			=> $this->member_id,
        			  'listed_description'	=> '',
        			  'listed_type'			=> $which);
        	
        for ($i=0, $s = sizeof($person); $i < $s; ++$i)
        {
        	if ($this->member_id == $person[$i])
        	{
        		continue;
        	}
        	elseif($which == 'buddy' && sizeof($this->goodies) > 0 && in_array($person[$i], $this->goodies))
        	{
        		continue;
        	}
        	elseif($which == 'blocked' && sizeof($this->baddies) > 0 && in_array($person[$i], $this->baddies))
        	{
        		continue;
        	}
        		
        	$data['listed_member'] = $person[$i]; 
        	$DB->query($DB->insert_string('exp_message_listed', $data));
        		
        	if ($which == 'blocked')
        	{
        		$DB->query("DELETE FROM exp_message_copies 
        					WHERE sender_id = '".$DB->escape_str($person[$i])."'
        					AND recipient_id = '{$this->member_id}'");
        	}
        }
        	
        $this->fetch_lists($which);
        
		if ($which == 'blocked')
        {
        	return $this->blocked();
        }
        else
        {
        	return $this->buddies();
        }
    }
    /* END */
    
 	
 	
	/** -----------------------------------
    /**  Edit Block List
    /** -----------------------------------*/

    function blocked()
    {
    	global $LANG, $FNS, $DB;
    	
    	$template = $this->retrieve_template('buddies_block_list');
    	
    	$form_details = array('hidden_fields' => array('name' => '','description' => '', 'which' => 'blocked', 'daction' => ''),
    						  'action'		  => $this->_create_path('edit_list'),
    					 	  'id'			  => 'target',
    					 	  'secure'		  => ($this->allegiance == 'cp') ? FALSE : TRUE
    					 	  );  	
    	
    	$this->single_parts['form']['form_declaration']['list'] = $FNS->form_declaration($form_details);
    	$this->single_parts['lang']['list_title'] =	$LANG->line('blocked_list');
    	$this->single_parts['include']['toggle_js'] = $this->toggle_js();
    	$this->single_parts['include']['buddy_search_js'] =  $this->buddy_search_js();
    	$this->single_parts['image']['search_glass'] = '<img src="'.$this->images_folder.'search_glass.gif" style="border: 0px" width="12" height="12" alt="Search Glass" />';
    	$this->single_parts['include']['member_search'] = '<a href="#" title="{lang:member_search}" onclick="buddy_search(2); return false;">'.$this->single_parts['image']['search_glass'].'</a>';

    	$this->single_parts['include']['list_rows'] = '';
    	
    	$this->_buttons('y', 'n', 'n');
    	
    	/** ----------------------------------------
    	/**  Retrieve and Output List
    	/** ----------------------------------------*/
    	
    	if ($this->blocked === FALSE)
    	{
    		$this->fetch_lists('blocked');
    	}
    	
    	$list_size = sizeof($this->blocked);
    	
    	if ($list_size == 0)
    	{
    		$this->single_parts['include']['list_rows'] .= $this->_process_template($this->retrieve_template('empty_list'));
    	}
    	else
    	{
    		$rows = $this->retrieve_template('buddies_block_row');
    		
    		for($i=0; $i < $list_size; ++$i)
    		{
    			$this->single_parts['style']				= ($i % 2) ? 'tableCellOne' : 'tableCellTwo';
    			$this->single_parts['screen_name']			= $this->blocked[$i]['2'];
    			$this->single_parts['member_description']	= $this->blocked[$i]['3'];
    			$this->single_parts['listed_id']			= $this->blocked[$i]['4'];
    			
    			if ($this->allegiance == 'user')
    			{
    				$this->single_parts['path']['send_pm'] = $this->_create_path('pm').$this->buddies[$i]['5'].'/';
    			}
    			else
    			{
    				$this->single_parts['path']['send_pm'] = $this->_create_path('pm').AMP.'mid='.$this->buddies[$i]['5'];
    			}
    			
    			$this->single_parts['include']['list_rows'] .= $this->_process_template($rows);
    		}
    	}
    	
    	/** ----------------------------------------
		/**  Return the Trackable Messages
		/** ----------------------------------------*/
		
		$this->title = $LANG->line('blocked_list');
		$this->crumb = $LANG->line('blocked_list');
		$this->return_data = $this->_process_template($template);
 	}
 	/* END */


	/** --------------------------------
	/**  Generate Error Page
	/** --------------------------------*/
	
	function _error_page($msg = 'not_authorized', $replace = array())
	{
		global $LANG;
		
		$error = ( ! isset($LANG->language[$msg])) ? $msg : $LANG->line($msg);
		
		if (sizeof($replace) > 0)
		{
			foreach($replace as $key => $value)
			{
				$error = str_replace($key, $value, $error);
			}
		}
		
		
		$this->single_parts['lang']['heading'] = $LANG->line('error');
		$this->single_parts['lang']['message'] = $error;
	
		$this->title = $LANG->line('error');
		$this->crumb = $LANG->line('error');

		$this->return_data = $this->_process_template($this->retrieve_template('message_error'));		
		return;
	}
	/* END */

	
 	
	// -----------------------------------
    //  Maintenance
    //  This is where we do some automatic
    //  stuff such as erasing old and deleted messages
    // -----------------------------------   

    function maintenance()
    {
    	global $DB, $LOC;
    	
    	$deletion_time = $LOC->now - ($this->delete_expiration*24*60*60);
    	
    	// Erase old deleted messages
    	
    	$query = $DB->query("SELECT copy_id FROM exp_message_copies 
    						 WHERE recipient_id = '{$this->member_id}'
    						 AND message_deleted = 'y'
    						 AND message_time_read < $deletion_time");
    				
    	if ($query->num_rows > 0)
    	{
    		$delete = array();
    		
    		foreach($query->result as $row)
    		{
    			$delete[] = $row['copy_id'];
    		}
    		
    		$this->erase($delete);
    	}
    	
    	// Erase old, unused sent messages
    	
    	$query = $DB->query("SELECT d.message_id FROM exp_message_data d
    						 LEFT JOIN exp_message_copies c ON (d.message_id = c.message_id)
    						 WHERE d.sender_id = '{$this->member_id}'
    						 AND d.message_status = 'sent'
    						 AND d.message_date < $deletion_time
    						 AND c.message_id IS NULL");;
    				
    	if ($query->num_rows > 0)
    	{
    		$delete = array();
    		
    		foreach($query->result as $row)
    		{
    			$delete[] = $row['message_id'];
    		}
    		
    		if (sizeof($delete) > 0)
    		{
    			$DB->query("DELETE FROM exp_message_data WHERE message_id IN ('".implode("','", $delete)."')");
    		}
    	}
 	}
 	/* END */
 	
 	
 	
    /** -----------------------------------
    /**  JavaScript for Move Button
    /** -----------------------------------*/

    function hidden_js()
    {
    	$str = <<<MRT

<script type="text/javascript">
//<![CDATA[
// ===================================================================
// Author: Matt Kruse <matt@mattkruse.com>
// WWW: http://www.mattkruse.com/
//
// NOTICE: You may use this code for any purpose, commercial or
// private, without any further permission from the author. You may
// remove this notice from your final code if you wish, however it is
// appreciated by the author if at least my web site address is kept.
//
// You may *NOT* re-distribute this code in any way except through its
// use. That means, you can include it in your product, or your web
// site, or any other form where the code is actually being used. You
// may not put the plain javascript up on your site for download or
// include it in your javascript libraries for download. 
// If you wish to share this code with others, please just point them
// to the URL instead.
// Please DO NOT link directly to my .js files from your site. Copy
// the files to your server and use them there. Thank you.
// ===================================================================


/* SOURCE FILE: AnchorPosition.js */

/* 
AnchorPosition.js
Author: Matt Kruse
Last modified: 10/11/02

DESCRIPTION: These functions find the position of an <A> tag in a document,
so other elements can be positioned relative to it.

COMPATABILITY: Netscape 4.x,6.x,Mozilla, IE 5.x,6.x on Windows. Some small
positioning errors - usually with Window positioning - occur on the 
Macintosh platform.

FUNCTIONS:
getAnchorPosition(anchorname)
  Returns an Object() having .x and .y properties of the pixel coordinates
  of the upper-left corner of the anchor. Position is relative to the PAGE.

getAnchorWindowPosition(anchorname)
  Returns an Object() having .x and .y properties of the pixel coordinates
  of the upper-left corner of the anchor, relative to the WHOLE SCREEN.

NOTES:

1) For popping up separate browser windows, use getAnchorWindowPosition. 
   Otherwise, use getAnchorPosition

2) Your anchor tag MUST contain both NAME and ID attributes which are the 
   same. For example:
   <A NAME="test" ID="test"> </A>

3) There must be at least a space between <A> </A> for IE5.5 to see the 
   anchor tag correctly. Do not do <A></A> with no space.
*/ 

// getAnchorPosition(anchorname)
//   This function returns an object having .x and .y properties which are the coordinates
//   of the named anchor, relative to the page.
function getAnchorPosition(anchorname) {
	// This function will return an Object with x and y properties
	var useWindow=false;
	var coordinates=new Object();
	var x=0,y=0;
	// Browser capability sniffing
	var use_gebi=false, use_css=false, use_layers=false;
	if (document.getElementById) { use_gebi=true; }
	else if (document.all) { use_css=true; }
	else if (document.layers) { use_layers=true; }
	// Logic to find position
 	if (use_gebi && document.all) {
		x=AnchorPosition_getPageOffsetLeft(document.all[anchorname]);
		y=AnchorPosition_getPageOffsetTop(document.all[anchorname]);
		}
	else if (use_gebi) {
		var o=document.getElementById(anchorname);
		x=AnchorPosition_getPageOffsetLeft(o);
		y=AnchorPosition_getPageOffsetTop(o);
		}
 	else if (use_css) {
		x=AnchorPosition_getPageOffsetLeft(document.all[anchorname]);
		y=AnchorPosition_getPageOffsetTop(document.all[anchorname]);
		}
	else if (use_layers) {
		var found=0;
		for (var i=0; i<document.anchors.length; i++) {
			if (document.anchors[i].name==anchorname) { found=1; break; }
			}
		if (found==0) {
			coordinates.x=0; coordinates.y=0; return coordinates;
			}
		x=document.anchors[i].x;
		y=document.anchors[i].y;
		}
	else {
		coordinates.x=0; coordinates.y=0; return coordinates;
		}
	coordinates.x=x;
	coordinates.y=y;
	return coordinates;
	}

// getAnchorWindowPosition(anchorname)
//   This function returns an object having .x and .y properties which are the coordinates
//   of the named anchor, relative to the window
function getAnchorWindowPosition(anchorname) {
	var coordinates=getAnchorPosition(anchorname);
	var x=0;
	var y=0;
	if (document.getElementById) {
		if (isNaN(window.screenX)) {
			x=coordinates.x-document.body.scrollLeft+window.screenLeft;
			y=coordinates.y-document.body.scrollTop+window.screenTop;
			}
		else {
			x=coordinates.x+window.screenX+(window.outerWidth-window.innerWidth)-window.pageXOffset;
			y=coordinates.y+window.screenY+(window.outerHeight-24-window.innerHeight)-window.pageYOffset;
			}
		}
	else if (document.all) {
		x=coordinates.x-document.body.scrollLeft+window.screenLeft;
		y=coordinates.y-document.body.scrollTop+window.screenTop;
		}
	else if (document.layers) {
		x=coordinates.x+window.screenX+(window.outerWidth-window.innerWidth)-window.pageXOffset;
		y=coordinates.y+window.screenY+(window.outerHeight-24-window.innerHeight)-window.pageYOffset;
		}
	coordinates.x=x;
	coordinates.y=y;
	return coordinates;
	}

// Functions for IE to get position of an object
function AnchorPosition_getPageOffsetLeft (el) {
	var ol=el.offsetLeft;
	while ((el=el.offsetParent) != null) { ol += el.offsetLeft; }
	return ol;
	}
function AnchorPosition_getWindowOffsetLeft (el) {
	return AnchorPosition_getPageOffsetLeft(el)-document.body.scrollLeft;
	}	
function AnchorPosition_getPageOffsetTop (el) {
	var ot=el.offsetTop;
	while((el=el.offsetParent) != null) { ot += el.offsetTop; }
	return ot;
	}
function AnchorPosition_getWindowOffsetTop (el) {
	return AnchorPosition_getPageOffsetTop(el)-document.body.scrollTop;
	}

/* SOURCE FILE: PopupWindow.js */

/* 
PopupWindow.js
Author: Matt Kruse
Last modified: 02/16/04

DESCRIPTION: This object allows you to easily and quickly popup a window
in a certain place. The window can either be a DIV or a separate browser
window.

COMPATABILITY: Works with Netscape 4.x, 6.x, IE 5.x on Windows. Some small
positioning errors - usually with Window positioning - occur on the 
Macintosh platform. Due to bugs in Netscape 4.x, populating the popup 
window with <STYLE> tags may cause errors.

USAGE:
// Create an object for a WINDOW popup
var win = new PopupWindow(); 

// Create an object for a DIV window using the DIV named 'mydiv'
var win = new PopupWindow('mydiv'); 

// Set the window to automatically hide itself when the user clicks 
// anywhere else on the page except the popup
win.autoHide(); 

// Show the window relative to the anchor name passed in
win.showPopup(anchorname);

// Hide the popup
win.hidePopup();

// Set the size of the popup window (only applies to WINDOW popups
win.setSize(width,height);

// Populate the contents of the popup window that will be shown. If you 
// change the contents while it is displayed, you will need to refresh()
win.populate(string);

// set the URL of the window, rather than populating its contents
// manually
win.setUrl("http://www.site.com/");

// Refresh the contents of the popup
win.refresh();

// Specify how many pixels to the right of the anchor the popup will appear
win.offsetX = 50;

// Specify how many pixels below the anchor the popup will appear
win.offsetY = 100;

NOTES:
1) Requires the functions in AnchorPosition.js

2) Your anchor tag MUST contain both NAME and ID attributes which are the 
   same. For example:
   <A NAME="test" ID="test"> </A>

3) There must be at least a space between <A> </A> for IE5.5 to see the 
   anchor tag correctly. Do not do <A></A> with no space.

4) When a PopupWindow object is created, a handler for 'onmouseup' is
   attached to any event handler you may have already defined. Do NOT define
   an event handler for 'onmouseup' after you define a PopupWindow object or
   the autoHide() will not work correctly.
*/ 

// Set the position of the popup window based on the anchor
function PopupWindow_getXYPosition(anchorname) {
	var coordinates;
	if (this.type == "WINDOW") {
		coordinates = getAnchorWindowPosition(anchorname);
		}
	else {
		coordinates = getAnchorPosition(anchorname);
		}
	this.x = coordinates.x;
	this.y = coordinates.y;
	}
// Set width/height of DIV/popup window
function PopupWindow_setSize(width,height) {
	this.width = width;
	this.height = height;
	}
// Fill the window with contents
function PopupWindow_populate(contents) {
	this.contents = contents;
	this.populated = false;
	}
// Set the URL to go to
function PopupWindow_setUrl(url) {
	this.url = url;
	}
// Set the window popup properties
function PopupWindow_setWindowProperties(props) {
	this.windowProperties = props;
	}
// Refresh the displayed contents of the popup
function PopupWindow_refresh() {
	if (this.divName != null) {
		// refresh the DIV object
		if (this.use_gebi) {
			document.getElementById(this.divName).innerHTML = this.contents;
			}
		else if (this.use_css) { 
			document.all[this.divName].innerHTML = this.contents;
			}
		else if (this.use_layers) { 
			var d = document.layers[this.divName]; 
			d.document.open();
			d.document.writeln(this.contents);
			d.document.close();
			}
		}
	else {
		if (this.popupWindow != null && !this.popupWindow.closed) {
			if (this.url!="") {
				this.popupWindow.location.href=this.url;
				}
			else {
				this.popupWindow.document.open();
				this.popupWindow.document.writeln(this.contents);
				this.popupWindow.document.close();
			}
			this.popupWindow.focus();
			}
		}
	}
// Position and show the popup, relative to an anchor object
function PopupWindow_showPopup(anchorname) {
	this.getXYPosition(anchorname);
	this.x += this.offsetX;
	this.y += this.offsetY;
	if (!this.populated && (this.contents != "")) {
		this.populated = true;
		this.refresh();
		}
	if (this.divName != null) {
		// Show the DIV object
		if (this.use_gebi) {
			document.getElementById(this.divName).style.left = this.x + "px";
			document.getElementById(this.divName).style.top = this.y + "px";
			document.getElementById(this.divName).style.visibility = "visible";
			}
		else if (this.use_css) {
			document.all[this.divName].style.left = this.x;
			document.all[this.divName].style.top = this.y;
			document.all[this.divName].style.visibility = "visible";
			}
		else if (this.use_layers) {
			document.layers[this.divName].left = this.x;
			document.layers[this.divName].top = this.y;
			document.layers[this.divName].visibility = "visible";
			}
		}
	else {
		if (this.popupWindow == null || this.popupWindow.closed) {
			// If the popup window will go off-screen, move it so it doesn't
			if (this.x<0) { this.x=0; }
			if (this.y<0) { this.y=0; }
			if (screen && screen.availHeight) {
				if ((this.y + this.height) > screen.availHeight) {
					this.y = screen.availHeight - this.height;
					}
				}
			if (screen && screen.availWidth) {
				if ((this.x + this.width) > screen.availWidth) {
					this.x = screen.availWidth - this.width;
					}
				}
			var avoidAboutBlank = window.opera || ( document.layers && !navigator.mimeTypes['*'] ) || navigator.vendor == 'KDE' || ( document.childNodes && !document.all && !navigator.taintEnabled );
			this.popupWindow = window.open(avoidAboutBlank?"":"about:blank","window_"+anchorname,this.windowProperties+",width="+this.width+",height="+this.height+",screenX="+this.x+",left="+this.x+",screenY="+this.y+",top="+this.y+"");
			}
		this.refresh();
		}
	}
// Hide the popup
function PopupWindow_hidePopup() {
	if (this.divName != null) {
		if (this.use_gebi) {
			document.getElementById(this.divName).style.visibility = "hidden";
			}
		else if (this.use_css) {
			document.all[this.divName].style.visibility = "hidden";
			}
		else if (this.use_layers) {
			document.layers[this.divName].visibility = "hidden";
			}
		}
	else {
		if (this.popupWindow && !this.popupWindow.closed) {
			this.popupWindow.close();
			this.popupWindow = null;
			}
		}
	}
// Pass an event and return whether or not it was the popup DIV that was clicked
function PopupWindow_isClicked(e) {
	if (this.divName != null) {
		if (this.use_layers) {
			var clickX = e.pageX;
			var clickY = e.pageY;
			var t = document.layers[this.divName];
			if ((clickX > t.left) && (clickX < t.left+t.clip.width) && (clickY > t.top) && (clickY < t.top+t.clip.height)) {
				return true;
				}
			else { return false; }
			}
		else if (document.all) { // Need to hard-code this to trap IE for error-handling
			var t = window.event.srcElement;
			while (t.parentElement != null) {
				if (t.id==this.divName) {
					return true;
					}
				t = t.parentElement;
				}
			return false;
			}
		else if (this.use_gebi && e) {
			var t = e.originalTarget;
			try {while (t.parentNode != null) {
				if (t.id==this.divName) {
					return true;
					}
				t = t.parentNode;
				} } catch(g) {}
			return false;
			}
		return false;
		}
	return false;
	}
	
// Check an onMouseDown event to see if we should hide
function PopupWindow_hideIfNotClicked(e) {
	if (this.autoHideEnabled && !this.isClicked(e)) {
		this.hidePopup();
		}
	}
// Call this to make the DIV disable automatically when mouse is clicked outside it
function PopupWindow_autoHide() {
	this.autoHideEnabled = true;
	}
// This global function checks all PopupWindow objects onmouseup to see if they should be hidden
function PopupWindow_hidePopupWindows(e) {
	for (var i=0; i<popupWindowObjects.length; i++) {
		if (popupWindowObjects[i] != null) {
			var p = popupWindowObjects[i];
			p.hideIfNotClicked(e);
			}
		}
	}
// Run this immediately to attach the event listener
function PopupWindow_attachListener() {
	if (document.layers) {
		document.captureEvents(Event.MOUSEUP);
		}
	window.popupWindowOldEventListener = document.onmouseup;
	if (window.popupWindowOldEventListener != null) {
		document.onmouseup = new Function("window.popupWindowOldEventListener(); PopupWindow_hidePopupWindows();");
		}
	else {
		document.onmouseup = PopupWindow_hidePopupWindows;
		}
	}
// CONSTRUCTOR for the PopupWindow object
// Pass it a DIV name to use a DHTML popup, otherwise will default to window popup
function PopupWindow() {
	if (!window.popupWindowIndex) { window.popupWindowIndex = 0; }
	if (!window.popupWindowObjects) { window.popupWindowObjects = new Array(); }
	if (!window.listenerAttached) {
		window.listenerAttached = true;
		PopupWindow_attachListener();
		}
	this.index = popupWindowIndex++;
	popupWindowObjects[this.index] = this;
	this.divName = null;
	this.popupWindow = null;
	this.width=0;
	this.height=0;
	this.populated = false;
	this.visible = false;
	this.autoHideEnabled = false;
	
	this.contents = "";
	this.url="";
	this.windowProperties="toolbar=no,location=no,status=no,menubar=no,scrollbars=auto,resizable,alwaysRaised,dependent,titlebar=no";
	if (arguments.length>0) {
		this.type="DIV";
		this.divName = arguments[0];
		}
	else {
		this.type="WINDOW";
		}
	this.use_gebi = false;
	this.use_css = false;
	this.use_layers = false;
	if (document.getElementById) { this.use_gebi = true; }
	else if (document.all) { this.use_css = true; }
	else if (document.layers) { this.use_layers = true; }
	else { this.type = "WINDOW"; }
	this.offsetX = 0;
	this.offsetY = 0;
	// Method mappings
	this.getXYPosition = PopupWindow_getXYPosition;
	this.populate = PopupWindow_populate;
	this.setUrl = PopupWindow_setUrl;
	this.setWindowProperties = PopupWindow_setWindowProperties;
	this.refresh = PopupWindow_refresh;
	this.showPopup = PopupWindow_showPopup;
	this.hidePopup = PopupWindow_hidePopup;
	this.setSize = PopupWindow_setSize;
	this.isClicked = PopupWindow_isClicked;
	this.autoHide = PopupWindow_autoHide;
	this.hideIfNotClicked = PopupWindow_hideIfNotClicked;
	}

//]]>
</script>

<script type="text/javascript">
//<![CDATA[

var move_visible = 'n';
var copy_visible = 'n';

function dynamic_move()
{
	if (move_visible == 'y')
	{
		move_visible = 'n';
		movepopup.hidePopup();
	}
	else
	{
		copypopup.hidePopup();
		copy_visible = 'n';
		document.getElementById('target').copyto.options['0'].selected = 'none';
		movepopup.showPopup('move');
		move_visible = 'y';
	}
	
	dynamic_action('move');
}

function dynamic_copy()
{
	if (copy_visible == 'y')
	{
		copy_visible = 'n';
		copypopup.hidePopup();
	}
	else
	{
		movepopup.hidePopup();
		move_visible = 'n';
		document.getElementById('target').moveto.options['0'].selected = 'none';
		copypopup.showPopup('copy');
		copy_visible = 'y';
	}
	
	dynamic_action('copy');
}

function dynamic_action(which)
{
	if (document.getElementById('target').daction)
	{
		document.getElementById('target').daction.value = which;
	}
}


function dynamic_emoticons()
{
	emoticonspopup.showPopup('emoticons');
}

//]]>
</script>    

    
MRT;

		return $str;

    }
    /* END */
 	
 	
 	
	/** -----------------------------------
    /**  Show Hide JS
    /** -----------------------------------*/
 	
 	function showhide_js()
 	{
 		global $PREFS;
 		
 		$prefix = ( ! $PREFS->ini('cookie_prefix')) ? 'exp_' : $PREFS->ini('cookie_prefix').'_';
        $path   = ( ! $PREFS->ini('cookie_path'))   ? '/'    : $PREFS->ini('cookie_path');
        $domain = ( ! $PREFS->ini('cookie_domain')) ? ''     : $PREFS->ini('cookie_domain');
        $domain = ($domain == '') ? '' : 'domain='.$domain;
 		
$str = <<<EOT
<script type="text/javascript">
//<![CDATA[
function showHide(entryID, htmlObj)
{
	extTextDivID = ('extText' + (entryID));
	extLinkDivID = ('extLink' + (entryID));
	
	if (document.getElementById(extTextDivID).style.display == 'none')
	{
		document.getElementById(extTextDivID).style.display = "block";
		document.getElementById(extLinkDivID).innerHTML = "[-]";
		document.cookie = "{$prefix}myaccount_messages=on;{$domain};path={$path};";
	}
	else
	{
		document.getElementById(extTextDivID).style.display = "none";
		document.getElementById(extLinkDivID).innerHTML = "[+]";
		document.cookie = "{$prefix}myaccount_messages=off;{$domain};path={$path};";
	}
}

//]]>
</script>
EOT;
 	
 		return $str;
 	
 	}
 	/* END */
 	
 	
 	/** -----------------------------------------------------------
    /**  Emoticons
    /** -----------------------------------------------------------*/

    function emoticons()
    {
        global $PREFS;
        
        $r = '';
        
        if ( ! is_file(PATH_MOD.'emoticon/emoticons'.EXT))
        {
            return $r;        
        }
        else
        {
            require PATH_MOD.'emoticon/emoticons'.EXT;
        }
        
        if ( ! is_array($smileys))
        {
            return $r;
        }
        
        
        $path = $PREFS->ini('emoticon_path', 1);
                
        $i = 1;
        
        $dups = array();
        
        foreach ($smileys as $key => $val)
        {
            if ($i == 1 AND substr($r, -5) != "<tr>\n")
            {
                $r .= "<tr>\n";                
            }
            
            if (in_array($smileys[$key]['0'], $dups))
            {
            	continue;
            }
            
            $r .= "<td><a href=\"javascript:void(0);\" onclick=\"return add_smiley('".$key."');return false;\"><img src=\"".$path.$smileys[$key]['0']."\" width=\"".$smileys[$key]['1']."\" height=\"".$smileys[$key]['2']."\" alt=\"".$smileys[$key]['3']."\" style=\"border: 0px;\" /></a></td>\n";

			$dups[] = $smileys[$key]['0'];

            if ($i == $this->emoticons_per_row)
            {
                $r .= "</tr>\n";                
                
                $i = 1;
            }
            else
            {
                $i++;
            }      
        }
        
        $r = rtrim($r);
                
        if (substr($r, -5) != "</tr>")
        {
            $r .= "</tr>\n";
        }
        
        return $r;
    }
    /* END */
    

	/** -----------------------------------------
    /**  Base IFRAME for Spell Check
    /** -----------------------------------------*/

    function spellcheck_iframe()
    {
		if ( ! class_exists('Spellcheck'))
    	{
    		require PATH_CORE.'core.spellcheck'.EXT; 
    	}
    	
    	return Spellcheck::iframe();
	}
	/* END */
	
	
	/** -----------------------------------------
    /**  Spell Check for Textareas
    /** -----------------------------------------*/

    function spellcheck()
    {
    	if ( ! class_exists('Spellcheck'))
    	{
    		require PATH_CORE.'core.spellcheck'.EXT; 
    	}
    	
    	return Spellcheck::check();
	}
	/* END */
	
	/** --------------------------------
	/**  SpellCheck - JS
	/** --------------------------------*/
	
	function spellcheck_js()
	{
		if ( ! class_exists('Spellcheck'))
    	{
    		require PATH_CORE.'core.spellcheck'.EXT;
    	}
    	
    	$SPELL = new Spellcheck();
    	
    	$this->spellcheck_enabled = $SPELL->enabled;
    	
    	return $SPELL->JavaScript($this->_create_path('spellcheck'), TRUE);
	}
	/* END */
    

	/** --------------------------------
	/**  Submit Member to List - JS
	/** --------------------------------*/
	
	function list_js()
	{
		return <<<EWOK

<script type="text/javascript"> 
//<![CDATA[
function list_addition()
{
	var member_text = '{lang:member_usernames}';
	var member_description = '{lang:member_description} {lang:description_charlimit}';
	
	var Name = prompt(member_text, '');
                
     if ( ! Name || Name == null)
     {
     	return; 
     }            
    
     var Description = prompt(member_description, '');
     
     if ( ! Name || Name == null)
     {
     	return;
     }  
            
     document.getElementById('target').name.value = Name;
     document.getElementById('target').description.value = Description;
     document.getElementById('target').submit();
}

function dynamic_action(which)
{
	if (document.getElementById('target').daction)
	{
		document.getElementById('target').daction.value = which;
	}
}


//]]>
</script>
EWOK;
	
	}
	/* END */



    // -----------------------------------
    //  JavaScript for Recipients and
    //  CC Field Member Search for Composing
    // -----------------------------------   

    function search_js()
    {
    	global $LANG, $PREFS, $SESS;
    
    	if ($this->allegiance == 'cp')
    	{
    		$s = ($PREFS->ini('admin_session_type') != 'c') ? $SESS->userdata['session_id'] : 0;
				
			$url = $PREFS->ini('cp_url', FALSE).'?S='.$s.'&C=myaccount&M=messages&P=member_search&Z=1';
			$field = "&field='+which_field";
    	}
    	else
    	{
    		$url = $this->base_url.'member_search/';
    		$field = "'+which_field";
    	}
    	
    	
    	$str = <<<MRI

<script type="text/javascript">
//<![CDATA[
function perform_search(field)
{
	if (field == 2)
	{
		var which_field = 'cc';
	}
	else
	{
		var which_field = 'recipients';
	}
	
	var popWin = window.open('{$url}{$field}, '_blank', 'width=700,height=460,scrollbars=yes,status=yes,screenx=0,screeny=0,resizable=yes');
}

//]]>
</script>

MRI;

		return $str;
	}
	/* END */
	
	
	
	 // -----------------------------------
    //  JavaScript for Member Search for 
    //  Buddy and Block Lists
    // -----------------------------------   

    function buddy_search_js()
    {
    	global $LANG, $PREFS, $SESS;
    
    	if ($this->allegiance == 'cp')
    	{
    		$s = ($PREFS->ini('admin_session_type') != 'c') ? $SESS->userdata['session_id'] : 0;
				
			$url = $PREFS->ini('cp_url', FALSE).'?S='.$s.'&C=myaccount&M=messages&P=buddy_search&Z=1';
			$which = "&which='+which";
    	}
    	else
    	{
    		$url = $this->base_url.'buddy_search/';
    		$which = "'+which";
    	}
    	
    	
    	$str = <<<MRI

<script type="text/javascript">
//<![CDATA[
function buddy_search(which)
{
	if (which == 2)
	{
		var which = 'blocked';
	}
	else
	{
		var which = 'buddy';
	}
	
	var popWin = window.open('{$url}{$which}, '_blank', 'width=450,height=480,scrollbars=yes,status=yes,screenx=0,screeny=0,resizable=yes');
}

//]]>
</script>

MRI;

		return $str;
	}
	/* END */
	
	
	
	
	/** -----------------------------------
    /**  Counter for Counting Text, duh!
    /** -----------------------------------*/

    function text_counter_js()
    {
    	$str = <<<MRI

<script type="text/javascript">
//<![CDATA[
function text_counter(field)
{
	var max		= {$this->max_chars};
	var base	= document.forms.submit_message;
	var cur		= base.body.value.length;

	if (cur > max)
	{
		base.body.value = base.body.value.substring(0, max);
	} 
	else
	{
		base.charsleft.value = max - cur
	}
}

//]]>
</script>

MRI;

		return $str;
	}
	/* END */


	
	/** ----------------------------------------
    /**  Toggle JavaScript used everywhere!
    /** ----------------------------------------*/

    function toggle_js()
    {	
		$str = <<<EOT
		
<script type="text/javascript"> 
//<![CDATA[

function toggle(thebutton)
{
	if (thebutton.checked) 
	{
	   val = true;
	}
	else
	{
	   val = false;
	}
	
	if (document.target)
	{
		var theForm = document.target;
	}
	else if (document.getElementById('target'))
	{
		var theForm = document.getElementById('target');
	}
	else
	{
		return false;
	}
				
	var len = theForm.elements.length;

	for (var i = 0; i < len; i++) 
	{
		var button = theForm.elements[i];
		
		var name_array = button.name.split("["); 
		
		if (name_array[0] == "toggle") 
		{
			button.checked = val;
		}
	}
	
	theForm.toggleflag.checked = val;
}
//]]>
</script>

EOT;

		return trim($str);
	}
	/* END */
	
	
	
	// --------------------------------
	//  Headers for Compose Page
	//  Allows the Dynamic Address Book
	// --------------------------------
	
	function compose_header_js()
	{
		return <<<DOD
	
<script type="text/javascript"> 
//<![CDATA[

var buddy_div = 'address_book';

var agent = navigator.userAgent.toLowerCase();
var Opera = agent.indexOf("opera") != -1;
var MSIE = agent.indexOf("msie") != -1 && (document.all && !Opera);
var Konq = agent.indexOf("konqueror") != -1;
var Safari = agent.indexOf("safari") != -1 || Konq;
var Moz = !MSIE && (!Safari && (agent.indexOf("mozilla") != -1 || Opera));

var matches = new Array();
var current = 0;

function getChar(e) {
	
	if (document.getElementById(buddy_div).innerHTML == '') return;
	if (matches.length == 0) return;

	if (window.event)
	{
		e = window.event;
	}
	
	// IE and Firefox
	// tab => 9
	// return => 13
	// up => 38
	// down => 40

	var charCode = (!e.which || e.which == 0) ? e.keyCode : e.which;
	var action	 = 'next';

	switch(charCode)
    {
		case 38:
			action = 'previous';
			current--;
		break;
		case 40:
			action = 'next';
			current++;
		break;
		default:
			return true;
    }
    
    if (current < 0) current = 0;
    if (current > matches.length - 1) current = matches.length - 1;
	
	for (var p=0, str=""; p < matches.length; p++)
	{
		if (p == current)
		{
			str += "<div style='padding: 3px; background-color:#aaffaa'><a href='#' onclick='add_email(this.innerHTML); return false;'>" + matches[p] + "<"+"/a></div>";
		}
		else
		{
			str += "<div style='padding: 3px;'><a href='#' onclick='add_email(this.innerHTML); return false;'>" + matches[p] + "<"+"/a></div>";
		}
	}
	
	document.getElementById(buddy_div).innerHTML = str;
	return false;
}

function getCharTab(e) {
	
	if (document.getElementById(buddy_div).innerHTML == '') return;
	if (matches.length == 0) return;

	if (window.event)
	{
		e = window.event;
	}

	var charCode = (!e.which || e.which == 0) ? e.keyCode : e.which;
	var action	 = 'next';

	switch(charCode)
    {
    	case 9:
    		action = 'insert';
		break;
		case 13:
			action = 'insert';
		break;
		default:
			return true;
    }

	setTimeout("add_email('"+matches[current]+"')", 0);
	buddypopup.hidePopup();
    document.getElementById(buddy_div).innerHTML = '';
	current = 0;
	matches = new Array();
	return false;
}



document.onkeydown = getCharTab;
document.onkeyup = getChar;

//]]>
</script>	
	
DOD;
	}
	/* END */
	
	
	/** --------------------------------
	/**  Dynamic Address Book DIV & Javascript
	/** --------------------------------*/
	
	function dynamic_address_book()
	{
		if ($this->buddies === FALSE)
    	{
    		$this->fetch_lists('buddy');
    	}
    	
    	$str = '';
    	
    	for($i=0, $s = sizeof($this->buddies); $i < $s; $i++)
    	{
    		$str .= NL."\tbuddies_email[{$i}] = ['".htmlspecialchars($this->buddies[$i]['2'], ENT_QUOTES)."'];".NL;
    	}
	
	
		return <<<DIRT

<div id="address_book" class="tableCellOne" style="border: 1px solid #666; padding: 1px;position:absolute;visibility:hidden;"></div>

<script type="text/javascript"> 
//<![CDATA[

if (document.getElementById(buddy_div))
{
	var buddypopup = new PopupWindow(buddy_div);
	buddypopup.offsetY=0;
	buddypopup.offsetX=0;
	buddypopup.autoHide();
}

var selectedField = 'recipients';
var lastString = '';

function buddy_list(which)
{
	selectedField = which;

	matches		  = new Array();
	buddies_email = new Array();
	
	{$str}
	
	if (buddies_email.length == 0)
	{
		buddypopup.hidePopup();
		document.getElementById(buddy_div).innerHTML = '';
		return;
	}
	
	currentString = eval("document.getElementById('submit_message')." + which + ".value");
	
	if (currentString == '')
	{
		buddypopup.hidePopup();
		document.getElementById(buddy_div).innerHTML = '';
		return;
	}
	
	if (lastString == currentString)
	{
		return;
	}
	
	splitString = currentString.split(',');
	
	checkString = splitString[splitString.length - 1];
	checkString = checkString.toLowerCase();
	checkString = checkString.replace(/^\s*/, '').replace(/\s*$/, '');
	
	if (checkString.length == 0)
	{
		buddypopup.hidePopup();
		document.getElementById(buddy_div).innerHTML = '';
		return;
	}
	
	compareString = eval("/^" + checkString + "/gi");
	
	for(var i=0, email='', t=0; i < buddies_email.length; i++, name='', email='')
	{
		if (buddies_email[i][0].toLowerCase().substr('0',checkString.length).indexOf(checkString) > -1)
		{
			replaceString = buddies_email[i][0].slice(0, checkString.length);
			email = buddies_email[i][0].replace(compareString, replaceString.bold());
		}
		
		if(email != '')
		{
			matches[t] = email; t++;
		}
		
		if(matches.length > 25)
		{
			break;
		}
	}
	
	if (matches.length == 0)
	{
		buddypopup.hidePopup();
		document.getElementById(buddy_div).innerHTML = '';
		return;
	}
	
	for (var p=0, str=""; p < matches.length; p++)
	{
		if (p == 0)
		{
			str += "<div style='padding: 3px; background-color:#aaffaa'><a href='#' onclick='add_email(this.innerHTML); return false;'>" + matches[p] + "<"+"/a></div>";
		}
		else
		{
			str += "<div style='padding: 3px;'><a href='#' onclick='add_email(this.innerHTML); return false;'>" + matches[p] + "<"+"/a></div>";
		}
	}
	
	buddypopup.showPopup(which+'_buddies');
	document.getElementById(buddy_div).innerHTML = str;
}


function add_email(replaceString)
{
	curField = eval("document.getElementById('submit_message')." + selectedField);
	replaceString = replaceString.replace(/^ /,'').replace(/ $/,'');
	replaceString = replaceString.replace(/<B>/gi,'').replace(/<\/B>/gi,'');
	
	splitString = curField.value.split(',');
	
	for (i=0, newText=''; i < (splitString.length - 1); i++)
	{
		newText += splitString[i].replace(/^ /,'').replace(/ $/,'') + ', ';
	}
	
	buddypopup.hidePopup();
	
	newText += replaceString + ', ';
	
	curField.value = newText;
	
	curField.blur();
	curField.focus();
}

//]]>
</script>
DIRT;
	
	}
	/* END */
	
 	
/*
                                      ,.   '\'\    ,---.
     Quiet, Paul, I'm pondering.     | \\  l\\l_ //    |   
            _              _         |  \\/ `/  `.|    |   Err...right, Rick! Narf!
          / \\   \        //\        | Y |   |   ||  Y |
          |  \\   \      //  |       |  \|   |   |\ /  |   /
          [   ||        ||   ]       \   |  o|o  | >  /   /
         ]    ||        ||    [       \___\_--_ /_/__/
         |  \_|l,------.l|_/  |       /.-\(____) /--.\
         |   >'          `<   |       `--(______)----'
         \  (/~'--____--'~\)  /           U// U / \
          `-_>-__________-<_-'            / \  / /|
              /(_*(__)*_)\               ( .) / / ]
              \___/__\___/                `.`' /   [
               /__`--'__\                  |`-'    |
            /\(__,>-~~ __)                 |       |__
         /\//\\\        /                 _l       |--:.
         '\/  <^\      /^>               |  `    ( |   \\
              _\ >-__-< /_             ,-\  ,-~~->. \   `:.___,/
             (___\    /___)           (____/    (____)    `---'  
             

                    SRKmHWgK         6HP                        
                   WRWWQWm              yQgX                    
                zWQQqRRWT                 rqQqB                 
              gqQqRtWR          fbXn16hs    XXRQq0              
            gXkQ8X           pkSb     b04     RQQXXK            
          QXXQkQ7          aDahC      XSO      RbXQQRQ          
        R88kXXQ          PZFwX       kkk        bQkQkRQLt       
       QdXkkdXt        rYOZ4Vt     4bd6          qXk8X8QR       
      gQQQd8kp        G6TuTy     3khP            tXQQQQRQa      
      Q888Xdb2        1TuL6    pkS       Q        RX8QQRQH      
      Kb88kSd8        YZZZ6yF6         4h        zk888RQR       
       QQQ8bdbV       ywyn            k          X8b8RRg        
        uQXb8Qb6      zFDD          X4          XQbQXbQ        
          gRb8Qkh      apX        wQ          t8QXQQW           
            bR8QbE      hSF    1bA          bQbR8W            
              zBXQRa      zDDf2         KqQRqRbgy               
                tGEgQh                aQRRqXXW                  
                    CmWQ             KRRQgqQ                
                         wqC       HmQpYj
     
*/


}
/* END */
?>
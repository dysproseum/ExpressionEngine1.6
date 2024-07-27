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
 File: core.style.php
-----------------------------------------------------
 Purpose: This class fetches the requested stylesheet.
 It also caches it in case there are multiple stylesheet
 requests on a single page
=====================================================
*/


class Style {

	var $style_cache = array();


    function Style()
    {
        global $DB, $PREFS, $IN; 
       
    	if (isset($_GET['ACT']) && $_GET['ACT'] == 'css')
		{
			$stylesheet = $IN->fetch_uri_segment(1).'/'.$IN->fetch_uri_segment(2);
		}
		else
		{
			$stylesheet = $_GET['css'];
    	}
    
        if ( $stylesheet == '' ||
             ! preg_match("/\//", $stylesheet) ||
			   preg_match("#^(http:\/\/|www\.)#i", $stylesheet)
            )
            exit;
                        
		if ( ! isset($style_cache[$stylesheet]))
		{
			$ex =  explode("/", $stylesheet);
			
			if (count($ex) != 2)
				exit;
			
			$sql = "SELECT exp_templates.template_data, exp_templates.template_name, exp_templates.save_template_file
					FROM   exp_templates, exp_template_groups 
					WHERE  exp_templates.group_id = exp_template_groups.group_id
					AND    exp_templates.template_name = '".$DB->escape_str($ex['1'])."'
					AND    exp_template_groups.group_name = '".$DB->escape_str($ex['0'])."'
					AND    exp_templates.template_type = 'css'
					AND    exp_templates.site_id = '".$DB->escape_str($PREFS->ini('site_id'))."'";
	

			$query = $DB->query($sql);
	
			if ($query->num_rows == 0)
				exit;
					
			/** -----------------------------------------
			/**  Retreive template file if necessary
			/** -----------------------------------------*/
			
			if ($PREFS->ini('save_tmpl_files') == 'y' AND $PREFS->ini('tmpl_file_basepath') != '' AND $query->row['save_template_file'] == 'y')
			{
				$basepath = $PREFS->ini('tmpl_file_basepath', 1);
								
				$basepath .= $ex['0'].'/'.$query->row['template_name'].'.php';
				
				if ($fp = @fopen($basepath, 'rb'))
				{
					flock($fp, LOCK_SH);
					
					$query->row['template_data'] = fread($fp, filesize($basepath)); 
					
					flock($fp, LOCK_UN);
					fclose($fp); 
				}
			}
					  
			$style_cache[$stylesheet] = str_replace(LD.'site_url'.RD, stripslashes($PREFS->ini('site_url')), $query->row['template_data']);
		}

			
		if ($PREFS->ini('send_headers') == 'y')
		{        
			$this->http_status_header(200);
			@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			@header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
			@header("Cache-Control: no-store, no-cache, must-revalidate");
			@header("Cache-Control: post-check=0, pre-check=0", false);
			@header("Pragma: no-cache");
		}
			@header("Content-type: text/css");

	
		echo $style_cache[$stylesheet];		
        exit;        
    }
    /* END */
    
    
	/** -------------------------------------------
    /**  Sends Out an HTTP Status Header
    /** -------------------------------------------*/
    
    function http_status_header($code, $text='')
    {
    	if ($text == '')
    	{
    		switch($code)
    		{
    			case 200:
    				$text = 'OK';
    			break;
    			case 304:
    				$text = 'Not Modified';
    			break;
    			case 401:
    				$text = 'Unauthorized';
    			break;
    			case 404:
    				$text = 'Not Found';
    			break;
    		}
    	}
    
    	if (substr(php_sapi_name(), 0, 3) == 'cgi')
    	{
    		@header("Status: {$code} {$text}", TRUE);
    	}
    	elseif ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' OR $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0')
    	{
    		@header($_SERVER['SERVER_PROTOCOL']." {$code} {$text}", TRUE, $code);
    	}
    	else
    	{
    		@header("HTTP/1.1 {$code} {$text}", TRUE, $code);
    	}
    }
    /* END */
}
// END CLASS
?>

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
 File: core.regex.php
-----------------------------------------------------
 Purpose: Regular expression library.
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Regex {

	var $xss_hash = '';


    /** -------------------------------------
    /**  Validate Email Address
    /** -------------------------------------*/

    function valid_email($address)
    {
		if ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address))
			return false;
		else		
			return true;
    }
    /* END */
    


    /** -------------------------------------
    /**  Validate IP Address
    /** -------------------------------------*/

    function valid_ip($ip)
    {
    	//  IP is only digits and periods
    	
    	if (preg_match("/[^0-9\.]/", $ip))
    	{
    		return FALSE;
    	}
    
		$ip_segments = explode('.', $ip);
		
		// Always 4 segments needed
		if (count($ip_segments) != 4)
		{
			return FALSE;
		}
		// IP cannot start with 0
		if (substr($ip_segments[0], 0, 1) == '0')
		{
			return FALSE;
		}
		// Check each segment
		foreach ($ip_segments as $segment)
		{
			// IP segments must be digits and can not be 
			// longer than 3 digits or greater then 255
			if ($segment > 255 OR strlen($segment) > 3)
			{
				return FALSE;
			}
		}
		
		return TRUE;
    }
    /* END */


    /** -------------------------------------
    /**  Prep URL
    /** -------------------------------------*/

    function prep_url($str = '')
    {
		if ($str == 'http://' || $str == '')
		{
			return '';
		}
		
		if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://')
		{
			$str = 'http://'.$str;
		}
		
		return $str;
    }
    /* END */



    /** -------------------------------------
    /**  Prep Query String
    /** -------------------------------------*/
    
    // This function checks to see if "Force Query Strings" is on
    // If so it adds a question mark to the URL if needed

	function prep_query_string($str)
	{
		global $PREFS;
		
		if (stristr($str, '.php') AND preg_match("#/index/$#", $str))
		{
			$str = substr($str, 0, -6);
		}
		
		if ( ! stristr($str, '?') AND $PREFS->ini('force_query_string') == 'y')
		{
			if (stristr($str, '.php'))
			{
				$str = preg_replace("#(.+?)\.php(.*?)#", "\\1.php?\\2", $str);
			}
			else
			{
				$str .= "?";
			}
		}
		
		return $str;
	}
	/* END */


    /** -------------------------------------
    /**  Decode query string entities
    /** -------------------------------------*/

    function decode_qstr($str)
    {
    	return str_replace(array('&#46;','&#63;','&amp;'),
    					   array('.','?','&'),
    					   $str);
    }
    /* END */


    /** --------------------------------------------
    /**  Format HTML so it appears correct in forms
    /** --------------------------------------------*/

    function form_prep($str = '', $strip = 0)
    {
    	global $FNS;
    
        if ($str == '')
        {
            return '';
        }
    
        if ($strip != 0)
        {
            $str = stripslashes($str);
        }
        
        // $str = $FNS->entities_to_ascii($str);
    
		$str = htmlspecialchars($str);
		$str = str_replace("'", "&#39;", $str);
        
        return $str;
    }
    /* END */


    /** -----------------------------------------
    /**  Convert PHP tags to entities
    /** -----------------------------------------*/

    function encode_php_tags($str)
    {        
    	return str_replace(array('<?php', '<?PHP', '<?', '?'.'>'), 
    					   array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), 
    					   $str);
    					   
    					   // <?php BBEdit fix
	}
	/* END */


    /** -------------------------------------
    /**  Convert EE Tags
    /** -------------------------------------*/

	function encode_ee_tags($str, $convert_curly=FALSE)
	{
		if ($str != '')
		{
			if ($convert_curly === TRUE)
			{
				$str = str_replace(array('{', '}'), array('&#123;', '&#125;'), $str);
			}
			else
			{
				$str = preg_replace("/\{(\/){0,1}exp:(.+?)\}/", "&#123;\\1exp:\\2&#125;", $str);
				$str = preg_replace("/\{embed=(.+?)\}/", "&#123;embed=\\1&#125;", $str);
				$str = preg_replace("/\{path:(.+?)\}/", "&#123;path:\\1&#125;", $str);
			}
		}
		
		return $str;
	}
	/* END */


    /** ----------------------------------------------
    /**  Convert single and double quotes to entites
    /** ----------------------------------------------*/

    function convert_quotes($str)
    {    
    	return str_replace(array("\'","\""), array("&#39;","&quot;"), $str);
    }
    /* END */



    /** -------------------------------------
    /**  Convert reserved XML characters
    /** -------------------------------------*/

    function xml_convert($str, $protect_all = FALSE)
    {
        $temp = '848ff8if9a6fb627facGGcdbcce6';
        
        $str = preg_replace("/&#(\d+);/", "$temp\\1;", $str);
        
        if ($protect_all === TRUE)
        {
        	$str = preg_replace("/&(\w+);/",  "$temp\\1;", $str);
        }
        
        $str = str_replace(array("&","<",">","\"", "'", "-"),
        				   array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;"),
        				   $str);
            
        $str = preg_replace("/$temp(\d+);/","&#\\1;",$str);
        
       	if ($protect_all === TRUE)
       	{
			$str = preg_replace("/$temp(\w+);/","&\\1;", $str);
		}
		
        return stripslashes($str);
    }    
    /* END */


    /** ----------------------------------------
    /**  ASCII to Entities
    /** ----------------------------------------*/

	function ascii_to_entities($str)
	{
		$count	= 1;
		$out	= '';
		$temp	= array();
			
		for ($i = 0, $s = strlen($str); $i < $s; $i++)
		{
			$ordinal = ord($str[$i]);
			
			if ($ordinal < 128)
			{
				$out .= $str[$i];            
			}
			else
			{
				if (count($temp) == 0)
				{
					$count = ($ordinal < 224) ? 2 : 3;
				}
				
				$temp[] = $ordinal;
				
				if (count($temp) == $count)
				{
					$number = ($count == 3) ? (($temp['0'] % 16) * 4096) + (($temp['1'] % 64) * 64) + ($temp['2'] % 64) : (($temp['0'] % 32) * 64) + ($temp['1'] % 64);
	
					$out .= '&#'.$number.';';
					$count = 1;
					$temp = array();
				}   
			}   
		}
		
		return $out;
	}
	/* END */
	
	
    /** ----------------------------------------
    /**  Entities to ASCII
    /** ----------------------------------------*/

	function entities_to_ascii($str, $all = TRUE)
	{
		if (preg_match_all('/\&#(\d+)\;/', $str, $matches))
		{
			for ($i = 0, $s = count($matches['0']); $i < $s; $i++)
			{				
				$digits = $matches['1'][$i];
	
				$out = '';
		
				if ($digits < 128)
				{
					$out .= chr($digits);
				
				} 
				elseif ($digits < 2048)
				{
					$out .= chr(192 + (($digits - ($digits % 64)) / 64));
					$out .= chr(128 + ($digits % 64));
				} 
				else
				{
					$out .= chr(224 + (($digits - ($digits % 4096)) / 4096));
					$out .= chr(128 + ((($digits % 4096) - ($digits % 64)) / 64));
					$out .= chr(128 + ($digits % 64));
				}
		
				$str = str_replace($matches['0'][$i], $out, $str);				
			}
		}
		
		if ($all)
		{
			$str = str_replace(array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;"),
							   array("&","<",">","\"", "'", "-"),
	        				   $str);
		}
		
		return $str;
	}
	/* END */



    /** -------------------------------------------------
    /**  Trim slashes "/" from front and back of string
    /** -------------------------------------------------*/

    function trim_slashes($str)
    {
        if (substr($str, 0, 1) == '/')
		{
			$str = substr($str, 1);
		}
		
		if (substr($str, 0, 5) == "&#47;")
		{
			$str = substr($str, 5);
		}
		
		if (substr($str, -1) == '/')
		{
			$str = substr($str, 0, -1);
		}
		
		if (substr($str, -5) == "&#47;")
		{
			$str = substr($str, 0, -5);
		}

        return $str;
    }
    /* END */


    /** -------------------------------------------------
    /**  Removes double commas from string
    /** -------------------------------------------------*/

    function remove_extra_commas($str)
    {
		$str = str_replace(",,", ",", $str);
    
        if (substr($str, 0, 1) == ',')
        {
            $str = substr($str, 1);
        }
       
        if (substr($str, -1) == ',')
        {
            $str = substr($str, 0, -1);
        }
        
        return $str;
    }
    /* END */
    
    
    /** -------------------------------------------------
    /**  Strip quotes
    /** -------------------------------------------------*/

    function strip_quotes($str)
    {
    	return str_replace(array('"', "'"), '', $str);
    }
    /* END */


	/** ----------------------------------------
	/**  Clean Keywords - used for searching
	/** ----------------------------------------*/
	
	function keyword_clean($str)
	{	
		//$str = strtolower($str);
		$str = strip_tags($str);
		
		// We allow some words with periods. 
		// This array defines them.
		// Note:  Do not include periods in the array.
		
		$allowed = array( 
							'Mr',
							'Ms',
							'Mrs',
							'Dr'
						);
		
		foreach ($allowed as $val)
		{
			$str = str_replace($val.".", $val."T9nbyrrsXCXv0pqemUAq8ff", $str);
		}
	
		// Remove periods unless they are within a word
	
		$str = preg_replace("#\.*(\s|$)#", " ", $str);
		
		// These are disallowed characters
	
		$chars = array(
						","	,
						"("	,
						")"	,
						"+"	,
						"!"	,
						"?"	,
						"["	,
						"]"	,
						"@"	,
						"^"	,
						"~"	,
						"*"	,
						"|"	,
						"\n",
						"\t"
					  );
		
				
		$str = str_replace($chars, ' ', $str);

		$str = preg_replace("(\s+)", " ", $str);
	
		// Put allowed periods back
		$str = str_replace('T9nbyrrsXCXv0pqemUAq8ff', '.', $str);
		
		// Kill naughty stuff...
		
		$str = $this->xss_clean($str);
		
		return trim($str);
	}
	/* END */


    /** -------------------------------------------------
    /**  Convert disallowed characters into entities
    /** -------------------------------------------------*/

	function convert_dissallowed_chars($str)
	{
		$bad = array(
						"\("	=>	"&#40;", 
						"\)"	=>	"&#41;",
						'\$'	=>	"&#36;",
						"%28"	=>	"&#40;",	// (  
						"%29"	=>	"&#41;",	// ) 
						"%2528"	=>	"&#40;",	// (
						"%24"	=>	"&#36;"		// $
					);

        foreach ($bad as $key => $val)
        {
			$str = preg_replace("#".$key."#i", $val, $str);   
        }

		return $str;
	}
	/* END */
	
	/** -------------------------------------------------
    /**  A Random Hash Used for Protecting URLs
    /** -------------------------------------------------*/

	function xss_protection_hash()
	{	
		global $FNS;
		
		if ($this->xss_hash == '')
		{
			/*
			 * We cannot use the $FNS random() method, so we create something that while
			 * not perfectly random will serve our purposes well enough
			 */
			 
			if (phpversion() >= 4.2)
				mt_srand();
			else
				mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);
			
			$this->xss_hash = md5(time() + mt_rand(0, 1999999999));
		}
		
		return $this->xss_hash;
	}
	/* END */
	
	
    /** -------------------------------------------------
    /**  XSS hacking stuff
    /** -------------------------------------------------*/

	function xss_clean($str, $is_image=FALSE)
	{	
		global $PREFS;
		
		/* ----------------------------------
		/*  Every so often an array will be sent to this function,
		/*  and so we simply go through the array, clean, and return
		/* ----------------------------------*/
		
		if (is_array($str))
		{
			while (list($key) = each($str))
			{
				$str[$key] = $this->xss_clean($str[$key]);
			}
			
			return $str;
		}
		
		$charset = strtoupper($PREFS->ini('charset'));
	
		/*
		 * Remove Null Characters
		 *
		 * This prevents sandwiching null characters
		 * between ascii characters, like Java\0script.
		 *
		 */
		$str = preg_replace('/\0+/', '', $str);
		$str = preg_replace('/(\\\\0)+/', '', $str);
		
		/*
		 * Protect GET variables in URLs
		 */
		 
		 // 901119URL5918AMP18930PROTECT8198
		 
		$str = preg_replace('|\&([a-z\_0-9]+)\=([a-z\_0-9]+)|i', $this->xss_protection_hash()."\\1=\\2", $str);
		
		/*
		 * Validate standard character entities
		 *
		 * Add a semicolon if missing.  We do this to enable
		 * the conversion of entities to ASCII later.
		 *
		 */
		$str = preg_replace('#(&\#?[0-9a-z]+)[\x00-\x20]*;?#i', "\\1;", $str);
		
		/*
		 * Validate UTF16 two byte encoding (x00) 
		 *
		 * Just as above, adds a semicolon if missing.
		 *
		 */
		$str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);
		
		/*
		 * Un-Protect GET variables in URLs (Yes, it has to be a preg_replace in this form again)
		 */
		 
		$str = str_replace($this->xss_protection_hash(), '&', $str);

		/*
		 * URL Decode
		 *
		 * Just in case stuff like this is submitted:
		 *
		 * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		 *
		 * Note: Normally urldecode() would be easier but it removes plus signs
		 *
		 */	
		$str = preg_replace("/(%20)+/", '9u3iovBnRThju941s89rKozm', $str);
		$str = preg_replace("/%u0([a-z0-9]{3})/i", "&#x\\1;", $str);
		$str = preg_replace("/%([a-z0-9]{2})/i", "&#x\\1;", $str); 
		$str = str_replace('9u3iovBnRThju941s89rKozm', "%20", $str);       
        		
		/*
		 * Convert character entities to ASCII 
		 *
		 * This permits our tests below to work reliably.
		 * We only convert entities that are within tags since
		 * these are the ones that will pose security problems.
		 *
		 */
		
		$str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_attribute_conversion'), $str);
		 
		$str = preg_replace_callback("/<([\w]+)[^>]*>/si", array($this, '_html_entity_decode_callback'), $str);
		
		/*
		
		Old Code that when modified to use preg_replace()'s above became more efficient memory-wise
		
		if (preg_match_all("/[a-z]+=([\'\"]).*?\\1/si", $str, $matches))
		{        
			for ($i = 0; $i < count($matches[0]); $i++)
			{
				if (stristr($matches[0][$i], '>'))
				{
					$str = str_replace(	$matches['0'][$i], 
										str_replace('>', '&lt;', $matches[0][$i]),  
										$str);
				}
			}
		}
		 
        if (preg_match_all("/<([\w]+)[^>]*>/si", $str, $matches))
        {        
			for ($i = 0; $i < count($matches[0]); $i++)
			{
				$str = str_replace($matches[0][$i], 
									$this->_html_entity_decode($matches[0][$i], $charset), 
									$str);
			}
		}
		*/
		
		/*
		 * Convert all tabs to spaces
		 *
		 * This prevents strings like this: ja	vascript
		 * NOTE: we deal with spaces between characters later.
		 * NOTE: preg_replace was found to be amazingly slow here on large blocks of data,
		 * so we use str_replace.
		 *
		 */
		 
		$str = str_replace("\t", " ", $str);
		
		/* ----------------------------------
		/*  Images are Handled in a Special Way
		/* ----------------------------------*/
		
		if ($is_image === TRUE)
		{
			$converted_string = $str;
		}
		
		/*
		 * Not Allowed Under Any Conditions
		 */	
		$bad = array(
						'document.cookie'	=> '[removed]',
						'document.write'	=> '[removed]',
						'.parentNode'		=> '[removed]',
						'.innerHTML'		=> '[removed]',
						'window.location'	=> '[removed]',
						'-moz-binding'		=> '[removed]',
						'<!--'				=> '&lt;!--',
						'-->'				=> '--&gt;',
						'<![CDATA['			=> '&lt;![CDATA['
					);
	
		foreach ($bad as $key => $val)
		{
			$str = str_replace($key, $val, $str);   
		}
		
		$bad = array(
						"javascript\s*:"	=> '[removed]',
						"expression\s*\("	=> '[removed]',
						"Redirect\s+302"	=> '[removed]'
					);
	
		foreach ($bad as $key => $val)
		{
			$str = preg_replace("#".$key."#i", $val, $str);   
		}
	
		/*
		 * Makes PHP tags safe
		 *
		 *  Note: XML tags are inadvertently replaced too:
		 *
		 *	<?xml
		 *
		 * But it doesn't seem to pose a problem.
		 *
		 */
		if ($is_image === TRUE)
		{
			// Images have a tendency to have the PHP short opening and closing tags every so often
			// so we skip those and only do the long opening tags.
			$str = str_replace(array('<?php', '<?PHP'),  array('&lt;?php', '&lt;?PHP'), $str);
		}
		else
		{
			$str = str_replace(array('<?php', '<?PHP', '<?', '?'.'>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
		}
		
		/*
		 * Compact any exploded words
		 *
		 * This corrects words like:  j a v a s c r i p t
		 * These words are compacted back to their correct state.
		 *
		 */		
		$words = array('javascript', 'expression', 'vbscript', 'script', '<applet', 'alert', 'document', 'write', 'cookie', 'window');
		foreach ($words as $word)
		{
			$temp = '';
			for ($i = 0; $i < strlen($word); $i++)
			{
				$temp .= substr($word, $i, 1)."\s*";
			}
			
			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback(
				'#('.substr($temp, 0, -3).')(\W)#is',
				function($matches) {
					return preg_replace('/\s+/s', '\\1', $matches[0]).'\\2';
				},
				$str
			);
		}
	
		/*
		 * Remove disallowed Javascript in links or img tags
		 */	
		do
		{
			$original = $str;
			
			if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && stripos($str, '</a>') !== FALSE) OR 
				 preg_match("/<\/a>/i", $str))
			{
				$str = preg_replace_callback("#<a.*?</a>#si", array($this, '_js_link_removal'), $str);
			}
			
			if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && stripos($str, '<img') !== FALSE) OR 
				 preg_match("/img/i", $str))
			{
				$str = preg_replace_callback("#<img.*?".">#si", array($this, '_js_img_removal'), $str);
			}
			
			if ((version_compare(PHP_VERSION, '5.0', '>=') === TRUE && (stripos($str, 'script') !== FALSE OR stripos($str, 'xss') !== FALSE)) OR
				 preg_match("/(script|xss)/i", $str))
			{
				$str = preg_replace("#</*(script|xss).*?\>#si", "", $str);
			}
		}
		while($original != $str);
		
		unset($original);

		/*
		 * Remove JavaScript Event Handlers
		 *
		 * Note: This code is a little blunt.  It removes
		 * the event handler and anything upto the closing >, 
		 * but it's unlkely to be a problem.
		 *
		 */
		$event_handlers = array('onblur','onchange','onclick','onerror', 'onfocus','onload','onmouseover','onmouseup','onmousedown','onselect','onsubmit','onunload','onkeypress','onkeydown','onkeyup','onresize','xmlns');
		
		if ($is_image === TRUE)
		{
			/*
			 * Adobe Photoshop puts XML metadata into JFIF images, including namespacing, 
			 * so we have to allow this for images. -Paul
			 */
			unset($event_handlers[array_search('xmlns', $event_handlers)]);
		}
		
		$str = preg_replace("#<([^>]+)(".implode('|', $event_handlers).")([^>]*)>#iU", "&lt;\\1\\2\\3&gt;", $str);
	
		/*
		 * Sanitize naughty HTML elements
		 *
		 * If a tag containing any of the words in the list 
		 * below is found, the tag gets converted to entities.
		 *
		 * So this: <blink>
		 * Becomes: &lt;blink&gt;
		 *
		 */		
		$str = preg_replace('#<(/*\s*)(alert|applet|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|layer|link|meta|object|plaintext|style|script|textarea|title|xml|xss)([^>]*)>#is', "&lt;\\1\\2\\3&gt;", $str);
		
		/*
		 * Sanitize naughty scripting elements
		 *
		 * Similar to above, only instead of looking for
		 * tags it looks for PHP and JavaScript commands
		 * that are disallowed.  Rather than removing the
		 * code, it simply converts the parenthesis to entities
		 * rendering the code unexecutable.
		 *
		 * For example:	eval('some code')
		 * Becomes:		eval&#40;'some code'&#41;
		 *
		 */
		$str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);
						
		/*
		 * Final clean up
		 *
		 * This adds a bit of extra precaution in case
		 * something got through the above filters
		 *
		 */	
		$bad = array(
						'document.cookie'	=> '[removed]',
						'document.write'	=> '[removed]',
						'.parentNode'		=> '[removed]',
						'.innerHTML'		=> '[removed]',
						'window.location'	=> '[removed]',
						'-moz-binding'		=> '[removed]',
						'<!--'				=> '&lt;!--',
						'-->'				=> '--&gt;',
						'<![CDATA['			=> '&lt;![CDATA['
					);
	
		foreach ($bad as $key => $val)
		{
			$str = str_replace($key, $val, $str);   
		}
		
		$bad = array(
						"javascript\s*:"	=> '[removed]',
						"expression\s*\("	=> '[removed]',
						"Redirect\s+302"	=> '[removed]'
					);
	
		foreach ($bad as $key => $val)
		{
			$str = preg_replace("#".$key."#i", $val, $str);   
		}
		
		/* ----------------------------------
		/*  Images are Handled in a Special Way
		/*  - Essentially, we want to know that after all of the character conversion is done whether
		/*  any unwanted, likely XSS, code was found.  If not, we return TRUE, as the image is clean.
		/*  However, if the string post-conversion does not matched the string post-removal of XSS,
		/*  then it fails, as there was unwanted XSS code found and removed/changed during processing.
		/* ----------------------------------*/
		
		if ($is_image === TRUE)
		{
			if ($str == $converted_string)
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		
		return $str;
	}
	// END xss_clean()	
	
	/** -------------------------------------------------
    /**  JS Link Removal
    /**  Callback function to sanitize links
    /** -------------------------------------------------*/

	function _js_link_removal($match)
	{
		return preg_replace("#<a.+?href=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>.*?</a>#si", "", $match[0]);
	}
	// END _js_link_removal()
	
	/** -------------------------------------------------
    /**  JS Image Removal
	/**  Callback function to sanitize image tags
    /** -------------------------------------------------*/
	
	function _js_img_removal($match)
	{
		return preg_replace("#<img.+?src=.*?(alert\(|alert&\#40;|javascript\:|window\.|document\.|\.cookie|<script|<xss).*?\>#si", "", $match[0]);
	}
	// END _js_img_removal()
	
    /** -------------------------------------------------
    /**  Create URL Title
    /** -------------------------------------------------*/

	function create_url_title($str)
	{
		global $PREFS;
		
		if (function_exists('mb_convert_encoding'))
		{
			$str = mb_convert_encoding($str, 'ISO-8859-1', 'auto');
		}
		elseif(function_exists('iconv') AND ($iconvstr = @iconv('', 'ISO-8859-1', $str)) !== FALSE)
		{
			$str = $iconvstr;
		}
		else
		{
			$str = utf8_decode($str);
		}
		
		$str = preg_replace_callback('/(.)/', array($this, "convert_accented_characters"), $str);
		
		$str = strip_tags(strtolower($str));
		$str = preg_replace('/\&#\d+\;/', "", $str);
		
		// Use dash as separator		

		if ($PREFS->ini('word_separator') == 'dash')
		{
			$trans = array(
							"_"									=> '-',
							"\&\#\d+?\;"                        => '',
							"\&\S+?\;"                          => '',
							"['\"\?\.\!*\$\#@%;:,\_=\(\)\[\]]"  => '',
							"\s+"                               => '-',
							"\/"                                => '-',
							"[^a-z0-9-_]"						=> '',
							"-+"                                => '-',
							"\&"                                => '',
							"-$"                                => '',
							"^-"                                => ''
						   );
		}
		else // Use underscore as separator
		{
			$trans = array(
							"-"									=> '_',
							"\&\#\d+?\;"                        => '',
							"\&\S+?\;"                          => '',
							"['\"\?\.\!*\$\#@%;:,\-=\(\)\[\]]"  => '',
							"\s+"                               => '_',
							"\/"                                => '_',
							"[^a-z0-9-_]"						=> '',
							"_+"                                => '_',
							"\&"                                => '',
							"_$"                                => '',
							"^_"                                => ''
						   );
		}
					   
		foreach ($trans as $key => $val)
		{
			$str = preg_replace("#".$key."#", $val, $str);
		} 
		
		$str = trim(stripslashes($str));

		return $str;
	}
	/* END */
	
	
	/** ---------------------------------------
	/**  Convert Accented Characters to Unaccented Equivalents
	/** ---------------------------------------*/
	
	function convert_accented_characters($match)
	{
		global $EXT;

		/* -------------------------------------
		/*  'foreign_character_conversion_array' hook.
		/*  - Allows you to use your own foreign character conversion array
		/*  - Added 1.6.0
		*/  
			if (isset($EXT->extensions['foreign_character_conversion_array']))
			{
				$foreign_characters = $EXT->call_extension('foreign_character_conversion_array');
			}
			else
			{
		    	$foreign_characters = array('223'	=>	"ss", // ß
		    								'224'	=>  "a",  '225' =>  "a", '226' => "a", '229' => "a",
		    								'227'	=>	"ae", '230'	=>	"ae", '228' => "ae",
		    								'231'	=>	"c",
		    								'232'	=>	"e",  // è
		    								'233'	=>	"e",  // é
		    								'234'	=>	"e",  // ê  								
		    								'235'	=>	"e",  // ë
		    								'236'	=>  "i",  '237' =>  "i", '238' => "i", '239' => "i",
		    								'241'	=>	"n",
		    								'242'	=>  "o",  '243' =>  "o", '244' => "o", '245' => "o",
		    								'246'	=>	"oe", // ö
		    								'249'	=>  "u",  '250' =>  "u", '251' => "u",
		    								'252'	=>	"ue", // ü
		    								'255'	=>	"y",
		    								'257'	=>	"aa", 
											'269'	=>	"ch", 
											'275'	=>	"ee", 
											'291'	=>	"gj", 
											'299'	=>	"ii", 
											'311'	=>	"kj", 
											'316'	=>	"lj", 
											'326'	=>	"nj", 
											'353'	=>	"sh", 
											'363'	=>	"uu", 
											'382'	=>	"zh",
											'256'	=>	"aa", 
											'268'	=>	"ch", 
											'274'	=>	"ee", 
											'290'	=>	"gj", 
											'298'	=>	"ii", 
											'310'	=>	"kj", 
											'315'	=>	"lj", 
											'325'	=>	"nj", 
											'352'	=>	"sh", 
											'362'	=>	"uu", 
											'381'	=>	"zh",
		    								);				
			}
		/*
		/* -------------------------------------*/
    								
    	$ord = ord($match['1']);
    		
		if (isset($foreign_characters[$ord]))
		{
			return $foreign_characters[$ord];
		}
		else
		{
			return $match['1'];
		}
	}
	/* END */
	
	/** -------------------------------------------------
    /**  Used for a callback in XSS Clean
    /** -------------------------------------------------*/
    
    function _attribute_conversion($match)
    {
    	return str_replace('>', '&lt;', $match[0]);
    }
    
    /* END */
	
	
	/** -------------------------------------------------
    /**  Replacement for html_entity_decode()
    /** -------------------------------------------------*/
    
    /*
    NOTE: html_entity_decode() has a bug in some PHP versions when UTF-8 is the 
    character set, and the PHP developers said they were not back porting the
    fix to versions other than PHP 5.x.
    */
    
    function _html_entity_decode_callback($match)
    {
    	global $PREFS;
    	return $this->_html_entity_decode($match[0], strtoupper($PREFS->ini('charset')));
    }
	
	function _html_entity_decode($str, $charset='ISO-8859-1') 
	{
		if (stristr($str, '&') === FALSE) return $str;
	
		// The reason we are not using html_entity_decode() by itself is because
		// while it is not technically correct to leave out the semicolon
		// at the end of an entity most browsers will still interpret the entity
		// correctly.  html_entity_decode() does not convert entities without
		// semicolons, so we are left with our own little solution here. Bummer.
		
		if ( ! in_array(strtoupper($charset), 
						array('ISO-8859-1', 'ISO-8859-15', 'UTF-8', 'cp866', 'cp1251', 'cp1252', 'KOI8-R', 'BIG5', 'GB2312', 'BIG5-HKSCS', 'Shift_JIS', 'EUC-JP')))
		{
			$charset = 'ISO-8859-1';
		}
	
		if (function_exists('html_entity_decode') && (strtolower($charset) != 'utf-8' OR version_compare(phpversion(), '5.0.0', '>=')))
		{
			$str = html_entity_decode($str, ENT_QUOTES, $charset);
			$str = preg_replace_callback(
				'~&#x([0-9a-f]{2,5})~i',
				function($matches) {
					return chr(hexdec("\\1"));
				},
				$str
			);
			return preg_replace_callback(
				'~&#([0-9]{2,4})~',
				function($matches) {
					chr('\\1');
				},
				$str
			);
		}
		
		// Numeric Entities
		$str = preg_replace('~&#x([0-9a-f]{2,5});{0,1}~ei', 'chr(hexdec("\\1"))', $str);
		$str = preg_replace('~&#([0-9]{2,4});{0,1}~e', 'chr(\\1)', $str);
	
		// Literal Entities - Slightly slow so we do another check
		if (stristr($str, '&') === FALSE)
		{
			$str = strtr($str, array_flip(get_html_translation_table(HTML_ENTITIES)));
		}
		
		return $str;
	}
	/* END */
	
	function unhtmlentities($str)
	{
		return $this->_html_entity_decode($str);
	}


	/** -------------------------------------------------
    /**  Removes slashes from array
    /** -------------------------------------------------*/

     function array_stripslashes($vals)
     {
     	if (is_array($vals))
     	{	
     		foreach ($vals as $key=>$val)
     		{
     			$vals[$key] = $this->array_stripslashes($val);
     		}
     	}
     	else
     	{
     		$vals = stripslashes($vals);
     	}
     	
     	return $vals;
	}
	/* END */

}
// END CLASS
?>

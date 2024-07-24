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
 File: core.typography.php
-----------------------------------------------------
 Purpose: Typographic rendering class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Typography {

    var $single_line_pgfs		= TRUE;		// Whether to treat single lines as paragraphs in auto-xhtml
    var $allow_js_img_anchors	= FALSE;	// Whether to allow JavaScript submitted within <a href> and <img> tags
    var $text_format    		= 'xhtml';  // xhtml, br, none, or lite
    var $html_format    		= 'safe';   // safe, all, none
    var $auto_links     		= 'y'; 
    var $allow_img_url  		= 'n';
    var $parse_images   		= FALSE;
    var $encode_email   		= TRUE;
	var $encode_type			= 'javascript'; // javascript or noscript
    var $use_span_tags  		= TRUE;
    var $popup_links    		= FALSE;
    var $bounce					= '';
    var $smiley_array        	= FALSE;
    var $parse_smileys			= TRUE;
    var $highlight_code			= TRUE;
    var $convert_curly			= TRUE;		// Convert Curly Brackets Into Entities
    var $emoticon_path  		= '';
    var $site_index				= '';
    var $word_censor    		= FALSE;
    var $censored_words 		= array();
    var $censored_replace		= '';
    var $file_paths     		= array();
    var $text_fmt_types			= array('xhtml', 'br', 'none', 'lite');
    var $text_fmt_plugins		= array();
    var $html_fmt_types			= array('safe', 'all', 'none');
    var $yes_no_syntax			= array('y', 'n');
    var $code_chunks			= array();
    var $code_counter			= 0;
	var $http_hidden 			= 'ed9f01a60cc1ac21bf6f1684e5a3be23f38a51b9'; // hash to protect URLs in [url] pMcode
    
    
    /** -------------------------------------
    /**  Allowed tags
    /** -------------------------------------*/
    
    // Note: The decoding array is associative, allowing more precise mapping
           
    var $safe_encode = array('b', 'i', 'u', 'em', 'strike', 'strong', 'pre', 'code', 'blockquote');
    
    var $safe_decode = array(
                                'b'             => 'b', 
                                'i'             => 'i',
                                'u'             => 'u', 
                                'em'            => 'em', 
                                'strike'        => 'strike', 
                                'strong'        => 'strong', 
                                'pre'           => 'pre', 
                                'code'          => 'code', 
                                'blockquote'    => 'blockquote',
                                'quote'         => 'blockquote',
                                'QUOTE'         => 'blockquote'
                             );
    


    /** -------------------------------------
    /**  Constructor
    /** -------------------------------------*/

    function Typography($parse_images = TRUE, $allow_headings = TRUE)
    {
        global $PREFS, $FNS;
                
        if ($parse_images == TRUE)
        {
            $this->file_paths = $FNS->fetch_file_paths();
        }
        
        $this->parse_images = $parse_images;
        
        if ($allow_headings == TRUE)
        {
        	foreach (array('h2', 'h3', 'h4', 'h5', 'h6') as $val)
        	{
        		$this->safe_encode[] = $val;
        		$this->safe_decode[$val] = $val;
        	}
        }
            
        /** -------------------------------------
        /**  Fetch emoticon prefs
        /** -------------------------------------*/
        
        if ($PREFS->ini('enable_emoticons') == 'y')
        {
            if (is_file(PATH_MOD.'emoticon/emoticons'.EXT))
            {
                require PATH_MOD.'emoticon/emoticons'.EXT;
                
                if (is_array($smileys))
                {
                    $this->smiley_array = $smileys;
                    $this->emoticon_path = $PREFS->ini('emoticon_path', 1);
                }
            }
        }
        
		/* -------------------------------------------
		/*	Hidden Configuration Variables
		/*	- popup_link => Have links created by Typography class open in a new window (y/n)
        /* -------------------------------------------*/
        
        if ($PREFS->ini('popup_link') !== FALSE)
        {
            $this->popup_links = ($PREFS->ini('popup_link') == 'y') ? TRUE : FALSE;
        }

        /** -------------------------------------
        /**  Fetch word censoring prefs
        /** -------------------------------------*/
        
        if ($PREFS->ini('enable_censoring') == 'y' AND $PREFS->ini('censored_words') != '')
        {
			if ($PREFS->ini('censor_replacement') !== FALSE)
			{
				$this->censored_replace = $PREFS->ini('censor_replacement');
			}
		
			$words = preg_replace("/\s+/", "", trim($PREFS->ini('censored_words')));
			
			$words = str_replace('||', '|', $words);
	
			if (substr($words, -1) == "|")
			{
				$words = substr($words, 0, -1);
			}
					
			$this->censored_words = explode("|", $words);
			
			if (count($this->censored_words) > 0)
			{
				$this->word_censor = TRUE;
			}
        }
        

        /** -------------------------------------
        /**  Fetch plugins
        /** -------------------------------------*/
        
		$this->text_fmt_plugins = $this->fetch_plugins();  
    }
    /* END */
    
    
    
    /** -------------------------------------
    /**  Fetch installed plugins
    /** -------------------------------------*/
    
    function fetch_plugins()
    {
        global $PREFS;
        
        $exclude = array('auto_xhtml');
    
        $filelist = array();
    
        if ($fp = @opendir(PATH_PI)) 
        { 
            while (false !== ($file = readdir($fp))) 
            { 
				if ( eregi(EXT."$",  $file))
				{
					if (substr($file, 0, 3) == 'pi.')
					{
						$file = substr($file, 3, - strlen(EXT));
					
						if ( ! in_array($file, $exclude))
							$filelist[] = $file;
					}
				}
            } 
            
            @closedir($fp);
        } 
    
        sort($filelist);
		return $filelist;      
    }
    /* END */


    /** ----------------------------------------
    /**  Parse file paths
    /** ----------------------------------------*/

    function parse_file_paths($str)
    {
        global $DB;
        
        if ($this->parse_images == FALSE OR count($this->file_paths) == 0)
        {
            return $str;
        }
        
        foreach ($this->file_paths as $key => $val)
        {
            $str = str_replace("{filedir_".$key."}", $val, $str);
        }

        return $str;
    }
    /* END */


    /** -------------------------------------
    /**  Typographic parser
    /** -------------------------------------*/
    
    // Note: The processing order is very important in this function so don't change it!
    
    function parse_type($str, $prefs = '')
    {
    	global $REGX, $FNS, $EXT;
    	     
        if ($str == '')
        {
            return;    
        }
        
        // -------------------------------------------
        // 'typography_parse_type_start' hook.
		//  - Modify string prior to all other typography processing
		//
			if ($EXT->active_hook('typography_parse_type_start') === TRUE)
			{
				$str = $EXT->call_extension('typography_parse_type_start', $str, $this, $prefs);
			}	
		//
		// -------------------------------------------

        /** -------------------------------------
        /**  Encode PHP tags
        /** -------------------------------------*/
        
        // Before we do anything else, we'll convert PHP tags into character entities.
        // This is so that PHP submitted in weblog entries, comments, etc. won't get parsed.
        // Since you can enable templates to parse PHP, it would open up a security
        // hole to leave PHP submitted in entries and comments intact.
        
		$str = $REGX->encode_php_tags($str);

        /** -------------------------------------
        /**  Encode EE tags
        /** -------------------------------------*/
		
		// Next, we need to encode EE tags contained in entries, comments, etc. so that they don't get parsed.
				
		$str = $REGX->encode_ee_tags($str, $this->convert_curly);  
		    
        /** -------------------------------------
        /**  Set up our preferences
        /** -------------------------------------*/
        
        if (is_array($prefs))
        {
            if (isset($prefs['text_format']))
            {
				if ($prefs['text_format'] != 'none')
				{
					if (in_array($prefs['text_format'], $this->text_fmt_types))
					{
						$this->text_format = $prefs['text_format'];
					}
					else
					{
						if (in_array($prefs['text_format'], $this->text_fmt_plugins) AND file_exists(PATH_PI.'pi.'.$prefs['text_format'].EXT))
						{
							$this->text_format = $prefs['text_format'];
						}
					}
				}
				else
				{
					$this->text_format = 'none';
				}
            }
        
            if (isset($prefs['html_format']) AND in_array($prefs['html_format'], $this->html_fmt_types))
            {
                $this->html_format = $prefs['html_format'];
            }
        
            if (isset($prefs['auto_links']) AND in_array($prefs['auto_links'], $this->yes_no_syntax))
            {
                $this->auto_links = $prefs['auto_links'];
            }

            if (isset($prefs['allow_img_url'])  AND in_array($prefs['allow_img_url'], $this->yes_no_syntax))
            {
            	$this->allow_img_url = $prefs['allow_img_url'];
            }
        }
        
        /** -------------------------------------
        /**  Are single lines considered paragraphs?
        /** -------------------------------------*/
                
		if ($this->single_line_pgfs != TRUE)
		{
			if ($this->text_format == 'xhtml' AND ! preg_match("/(\015\012)|(\015)|(\012)/", $str))
			{
				$this->text_format = 'lite';
			}
        }
        
        /** -------------------------------------
        /**  Fix emoticon bug
        /** -------------------------------------*/
        
        $str = str_replace(array('>:-(', '>:('), array(':angry:', ':mad:'), $str);
        
        
        /** -------------------------------------
        /**  Highlight text within [code] tags
        /** -------------------------------------*/
        
        // If highlighting is enabled, we'll highlight <pre> tags as well.
        
        if ($this->highlight_code == TRUE)
        {
			$str = str_replace(array('[pre]', '[/pre]'), array('[code]', '[/code]'), $str);
        }        
            
		// We don't want pMcode parsed if it's within code examples so we'll convert the brackets
        
		if (preg_match_all("/\[code\](.+?)\[\/code\]/si", $str, $matches))
		{      		
			for ($i = 0; $i < count($matches['1']); $i++)
			{				
				$temp = str_replace(array('[', ']'), array('&#91;', '&#93;'), $matches['1'][$i]);
				$str  = str_replace($matches['0'][$i], '[code]'.$temp.'[/code]', $str);
			}			
		}
        
		if ($this->highlight_code == TRUE)
		{
			$str = $this->text_highlight($str);
		}
		else
		{
			$str = str_replace(array('[code]', '[/code]'),	array('<code>', '</code>'),	$str);
		}        

        /** -------------------------------------
        /**  Strip IMG tags if not allowed
        /** -------------------------------------*/

        if ($this->allow_img_url == 'n')
        {
            $str = $this->strip_images($str);
        }

        /** -------------------------------------
        /**  Format HTML
        /** -------------------------------------*/
    
        $str = $this->format_html($str);

        /** -------------------------------------
        /**  Auto-link URLs and email addresses
        /** -------------------------------------*/
                
        if ($this->auto_links == 'y' AND $this->html_format != 'none')
        {
            $str = $this->auto_linker($str);
        }
        
		/** -------------------------------------
        /**  Parse file paths (in images)
        /** -------------------------------------*/

        $str = $this->parse_file_paths($str);

		/** ---------------------------------------
		/**  Convert HTML links in CP to pMcode
		/** ---------------------------------------*/
		
		// Forces HTML links output in the control panel to pMcode so they will be formatted
		// as redirects, to prevent the control panel address from showing up in referrer logs
		
		if (REQ == 'CP')
		{
			$str = preg_replace("#<a(.*?)href=(\042|\047)([^\\2]*?)\\2(.*?)\>(.*?)</a>#si", "[url=\"\\3\"\\1\\4]\\5[/url]", $str);
		}
		
        /** -------------------------------------
        /**  Decode pMcode
        /** -------------------------------------*/
    
        $str = $this->decode_pmcode($str);
  
        /** -------------------------------------
        /**  Format text
        /** -------------------------------------*/

        switch ($this->text_format)
        {
            case 'none';
                break;
            case 'xhtml'	: $str = $this->xhtml_typography($str);
                break;
            case 'lite'		: $str = $this->light_xhtml_typography($str);  // Used with weblog entry titles
                break;
            case 'br'		: $str = $this->nl2br_except_pre($str);
                break;
            default			:
            
			if ( ! class_exists('Template'))
			{
				global $TMPL;
				require PATH_CORE.'core.template'.EXT;
				$TMPL = new Template();
			}            
			
			$plugin = ucfirst($prefs['text_format']);
			
			if ( ! class_exists($plugin))
			{	
				require_once PATH_PI.'pi.'.$prefs['text_format'].EXT;
			}
			
			if (class_exists($plugin))
			{
				$PLG = new $plugin($str);
			
				if (isset($PLG->return_data))
				{
					$str = $PLG->return_data;
				}
			}
            
            	break;
        }
        
        /** -------------------------------------
        /**  Parse emoticons
        /** -------------------------------------*/

        $str = $this->emoticon_replace($str);
        
        /** -------------------------------------
        /**  Parse censored words
        /** -------------------------------------*/

        $str = $this->filter_censored_words($str);

        /** ------------------------------------------
        /**  Decode and spam-protect email addresses
        /** ------------------------------------------*/
        
        // {encode="you@yoursite.com" title="Click Me"}
        
        // Note: We only do this here if it's a CP request since the
        // template parser handles this for page requets
        
        if (REQ == 'CP')
        {
			if (preg_match_all("/\{encode=(.+?)\}/i", $str, $matches))
			{	
				for ($j = 0; $j < count($matches['0']); $j++)
				{	
					$str = str_replace($matches['0'][$j], $FNS->encode_email($matches['1'][$j]), $str);
				}
			}  		
        }
        
        // Standard email addresses
        
        $str = $this->decode_emails($str);
        
        /** ------------------------------------------
        /**  Insert the cached code tags
        /** ------------------------------------------*/
        
		// The hightlight function called earlier converts the original code strings into markers
		// so that the auth_xhtml function doesn't attempt to process the highlighted code chunks.
		// Here we convert the markers back to their correct state.
        
        if (count($this->code_chunks) > 0)
        {
        	foreach ($this->code_chunks as $key => $val)
        	{
        		if ($this->text_format == 'xhtml')
        		{
        			// First line takes care of the line break that might be there, which should
        			// be a line break because it is just a simple break from the [code] tag.
        			$str = str_replace('{'.$key.'yH45k02wsSdrp}'."\n<br />", '</p>'.$val.'<p>', $str);
        			$str = str_replace('{'.$key.'yH45k02wsSdrp}', '</p>'.$val.'<p>', $str);
        		}
        		else
        		{
        			$str = str_replace('{'.$key.'yH45k02wsSdrp}', $val, $str);
        		}
        	}
        }
        
        // -------------------------------------------
        // 'typography_parse_type_end' hook.
		//  - Modify string after all other typography processing
		//
			if ($EXT->active_hook('typography_parse_type_end') === TRUE)
			{
				$str = $EXT->call_extension('typography_parse_type_end', $str, $this, $prefs);
			}	
		//
		// -------------------------------------------

        return $str;
    }
    /* END */


    /** -------------------------------------
    /**  Format HTML
    /** -------------------------------------*/

    function format_html($str)
    {
    	global $REGX;
    
        $html_options = array('all', 'safe', 'none');
    
        if ( ! in_array($this->html_format, $html_options))
        {
            $this->html_format = 'safe';
        }    
    
        if ($this->html_format == 'all')
        {
            return $str;
        }

        if ($this->html_format == 'none')
        {
            return $this->encode_tags($str);
        }
    
        /** -------------------------------------
        /**  Permit only safe HTML
        /** -------------------------------------*/
        
        $str = $REGX->xss_clean($str);
        
        // We strip any JavaScript event handlers from image links or anchors
        // This prevents cross-site scripting hacks.
        
     	$js = array(   
						'onBlur',
						'onChange',
						'onClick',
						'onFocus',
						'onLoad',
						'onMouseOver',
						'onmouseup',
						'onmousedown',
						'onSelect',
						'onSubmit',
						'onUnload',
						'onkeypress',
						'onkeydown',
						'onkeyup',
						'onresize'
					);
        
        
		foreach ($js as $val)
		{
			$str = preg_replace("/<img src\s*=(.+?)".$val."\s*\=.+?\>/i", "<img src=\\1 />", $str);
			$str = preg_replace("/<a href\s*=(.+?)".$val."\s*\=.+?\>/i", "<a href=\\1>", $str);			
		}        
        
        // Turn <br /> tags into newlines
        
		$str = preg_replace("#<br>|<br />#i", "\n", $str);
		
		// Strip paragraph tags
		
		$str = preg_replace("#<p>|<p[^>]*?>|</p>#i", "",  preg_replace("#<\/p><p[^>]*?>#i", "\n", $str));

        // Convert allowed HTML to pMcode
        
        foreach($this->safe_encode as $val)
        {
            $str = preg_replace("#<".$val.">(.+?)</".$val.">#si", "[$val]\\1[/$val]", $str);
        }

        // Convert anchors to pMcode
        // We do this to prevent allowed HTML from getting converted in the next step
        // Old method would only convert links that had href= as the first tag attribute
		// $str = preg_replace("#<a\s+href=[\"'](\S+?)[\"'](.*?)\>(.*?)</a>#si", "[url=\"\\1\"\\2]\\3[/url]", $str);
		        
		$str = preg_replace("#<a(.*?)href=(\042|\047)([^\\2]*?)\\2(.*?)\>(.*?)</a>#si", "[url=\"\\3\"\\1\\4]\\5[/url]", $str);

        // Convert image tags pMcode

		$str = str_replace("/>", ">", $str);
        
        $str = preg_replace("#<img(.*?)src=\s*[\"'](.+?)[\"'](.*?)\s*\>#si", "[img]\\2\\3\\1[/img]", $str);

        $str = preg_replace( "#(^|\s|\()((http(s?)://)|(www\.))(\w+[^\s\)\<]+)\.(jpg|jpeg|gif|png)#i", "\\1[img]http\\4://\\5\\6.\\7[/img]", $str);

        return $this->encode_tags($str);
    }
    /* END */



    /** -------------------------------------
    /**  Auto link URLs and email addresses
    /** -------------------------------------*/

    function auto_linker($str)
    {
    	global $FNS, $PREFS;
    	  
        $str .= ' ';
        
        // We don't want any links that appear in the control panel (in weblog entries, comments, etc.)
        // to point directly at URLs.  Why?  Becuase the control panel URL will end up in people's referrer logs, 
        // This would be a bad thing.  So, we'll point all links to the "bounce server"
                
		$qm = ($PREFS->ini('force_query_string') == 'y') ? '' : '?';

        $this->bounce = (REQ == 'CP' || $PREFS->ini('redirect_submitted_links') == 'y') ? $FNS->fetch_site_index().$qm.'URL=' : '';
        
        // Protect URLs that are already in [url] pMCode
        $str = preg_replace("/(\[url[^\]]*?\])http/is", '${1}'.$this->http_hidden, str_replace('[url=http', '[url='.$this->http_hidden, $str));
        
        // New version.  Blame Paul if it doesn't work
        // The parentheses on the end attempt to call any content after the URL. 
        // This way we can make sure it is not [url=http://site.com]http://site.com[/url]
		$str = preg_replace_callback("#(^|\s|\(|..\])((http(s?)://)|(www\.))(\w+[^\s\)\<\[]+)#im", array(&$this, 'auto_linker_callback'), $str);

        // Auto link email
        $str = preg_replace("/(^|\s|\(|\>)([a-zA-Z0-9_\.\-]+)@([a-zA-Z0-9\-]+)\.([a-zA-Z0-9\-\.]*)/i", "\\1[email]\\2@\\3.\\4[/email]", $str);
        
         // Clear period(s) from the end of emails
        $str = preg_replace("|(\.+)\[\/email\]|i ", "[/email]\\1", $str);
        
        // UnProtect URLs that are already in [url] pMCode
        $str = str_replace($this->http_hidden, 'http', $str);
 
        return substr($str, 0, -1);  // Removes space added above
    }
    /* END */
    
	/** -------------------------------------
    /**  Callback function used above
    /** -------------------------------------*/
    function auto_linker_callback($matches)
    {
    	global $PREFS;
    	
    	//  If it is in pMCode, then we do not auto link
    	if (strtolower($matches['1']) == 'mg]' OR 
    		strtolower($matches['1']) == 'rl]' OR
    		strtolower(substr(trim($matches[6]), 0, 6)) == '[/url]'
    	   )
		{
			return $matches['0'];
    	}
    	
    	/** -----------------------------------
		/**  Moved the Comment and Period Modification Here
		/** -----------------------------------*/
    	
		$end = '';
		
    	if (preg_match("/^(.+?)([\.\,]+)$/",$matches['6'], $punc_match))
    	{
    		$end = $punc_match[2];
    		$matches[6] = $punc_match[1];
    	}
		
		/** -----------------------------------
		/**  Modified 2006-02-07 to send back pMCode instead of HTML.  Insures correct sanitizing.
		/** -----------------------------------*/
		
		return	$matches['1'].'[url=http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].']http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].'[/url]'.
				$end;
		
		/** -----------------------------------
		/**  Old Way
		/** -----------------------------------*/
		
		$url_core = (REQ == 'CP' || $PREFS->ini('redirect_submitted_links') == 'y') ? urlencode($matches['6']) : $matches['6'];

    	return	$matches['1'].'<a href="'.$this->bounce.'http'.
				$matches['4'].'://'.
				$matches['5'].
				$url_core.'"'.(($this->popup_links == TRUE) ? ' onclick="window.open(this.href); return false;" ' : '').'>http'.
				$matches['4'].'://'.
				$matches['5'].
				$matches['6'].'</a>'.
				$end;
    }
	/* END */


    /** -------------------------------------
    /**  Decode pMcode
    /** -------------------------------------*/

    function decode_pmcode($str)
    {
    	global $FNS, $PREFS;
    	        
        /** -------------------------------------
        /**  Decode pMcode array map
        /** -------------------------------------*/
               
        foreach($this->safe_decode as $key => $val)
        {
			$str = str_replace(array('['.$key.']', '[/'.$key.']'),	array('<'.$val.'>', '</'.$val.'>'),	$str);
        }
        
        /** -------------------------------------
        /**  Decode color tags
        /** -------------------------------------*/
        
        if ($this->use_span_tags == TRUE)
        {
            $str = preg_replace("/\[color=(.*?)\](.*?)\[\/color\]/si", "<span style=\"color:\\1;\">\\2</span>",$str);
        }    
        else
        {
            $str = preg_replace("/\[color=(.*?)\](.*?)\[\/color\]/si", "<font color=\"\\1\">\\2</font>", $str);
        }
        
        /** -------------------------------------
        /**  Decode size tags
        /** -------------------------------------*/

        if ($this->use_span_tags == TRUE)
        {
            $str = preg_replace_callback("/\[size=(.*?)\](.*?)\[\/size\]/si", array($this, "font_matrix"),$str);
        }    
        else
        {
            $str = preg_replace("/\[size=(.*?)\](.*?)\[\/size\]/si", "<font color=\"\\1\">\\2</font>", $str);
        }

        /** -------------------------------------
        /**  Convert [url] tags to links 
        /** -------------------------------------*/
        
        $qm		= ($PREFS->ini('force_query_string') == 'y') ? '' : '?';
        $bounce	= (REQ == 'CP' || $PREFS->ini('redirect_submitted_links') == 'y') ? $FNS->fetch_site_index().$qm.'URL=' : '';
        
        $bad_things	 = array("'",'"', ';', '[', '(', ')', '!', '*', '>', '<', "\t", "\r", "\n", 'document.cookie'); // everything else
        $bad_things2 = array('[', '(', ')', '!', '*', '>', '<', "\t", 'document.cookie'); // style,title attributes
        $exceptions	 = array('http://', 'https://', 'irc://', 'feed://', 'ftp://', 'ftps://', 'mailto:');
        $allowed	 = array('rel', 'title', 'class', 'style', 'target');
        
        if (preg_match_all("/\[url(.*?)\](.*?)\[\/url\]/i", $str, $matches))
        {
        	for($i=0, $s=sizeof($matches['0']), $add=TRUE; $i < $s; ++$i)
        	{
        		$matches['1'][$i] = trim($matches['1'][$i]);
        		
        		$url = ($matches['1'][$i] != '') ? trim($matches['1'][$i]) : $matches['2'][$i];
        		$extra = '';
        		
				// remove all attributes except for the href in "Safe" HTML formatting
				// Also force links output in the CP with the Typography class as "safe" so that
				// any other tag attributes that it might have are not slapped in with the URL
        		if (($this->html_format == 'safe' OR REQ == 'CP') && stristr($matches['1'][$i],' '))
        		{
        			for($a=0, $sa=sizeof($allowed); $a < $sa; ++$a)
        			{
        				if (($p1 = strpos($url, $allowed[$a].'=')) !== FALSE)
        				{
        					$marker = substr($url, $p1 + strlen($allowed[$a].'='), 1);

        					if ($marker != "'" && $marker != '"') continue;
        					
        					$p2	= strpos(substr($url, $p1 + strlen($allowed[$a].'=') + 1), $marker);
        					
        					if ($p2 === FALSE) continue;
        					
        					// Do not make me explain the math here, it gives me a headache - Paul
        					
        					$inside = str_replace((($allowed[$a] == 'style' OR $allowed[$a] == 'title') ? $bad_things2 : $bad_things), 
        										  '', 
        										  substr($url, $p1 + strlen($allowed[$a].'=') + 1, $p2));
        										  
        					$extra .= ' '.$allowed[$a].'='.$marker.$inside.$marker;
        				}
        			}
        			
					// remove everything but the URL up to the first space
       				$url = substr($url, 0, strpos($url, ' '));
        		}
        		
        		// Clean out naughty stuff from URL.
        		$url = ($this->html_format == 'all') ? str_replace($bad_things2, '', $url) : str_replace($bad_things, '', $url);
        		
        		//  Remove the possible equal sign from the beginning of the URL
        		if (substr($url, 0, 1) == '=')
        		{
        			$url = substr($url, 1);
        		}
        		
        		if ($this->html_format == 'all')
				{
					//  Remove the possible quote from the beginning of the URL
					if (substr($url, 0, 1) == '"' OR substr($url, 0, 1) == "'")
					{
						$url = substr($url, 1);
					}
					
					//  Remove the possible quote from the end of the URL
					if (substr($url, -1) == '"' OR substr($url, -1) == "'")
					{
						$url = substr($url, 0, -1);
					}
        		}
        		
        		$add = TRUE;
        		
        		foreach($exceptions as $exception)
        		{
        			if (substr($url, 0, strlen($exception)) == $exception)
        			{
        				$add = FALSE; break;
        			}
        		}
        	
        		if ($add === TRUE)
        		{
        			$url = "http://".$url;
        		}
        		
        		$extra .= (($this->popup_links == TRUE) ? ' onclick="window.open(this.href); return false;" ' : '');
        		
        		if ($bounce != '')
        		{
        			$url = urlencode($url);
        		}
        		
        		$str = str_replace($matches['0'][$i], '<a href="'.$bounce.trim($url).'"'.$extra.'>'.$matches['2'][$i]."</a>", $str);
        	}
        }

        /** -------------------------------------
        /**  Image tags
        /** -------------------------------------*/

        // [img] and [/img]
        
        if ($this->allow_img_url == 'y')
        {        
            $str = preg_replace_callback("/\[img\](.*?)\[\/img\]/i", array($this, "image_sanitize"), $str);
            //$str = preg_replace("/\[img\](.*?)\[\/img\]/i", "<img src=\\1 />", $str);
        }
        elseif($this->auto_links == 'y' && $this->html_format != 'none')
        {
        	if (preg_match_all("/\[img\](.*?)\[\/img\]/i", $str, $matches))
        	{
        		for($i=0, $s=sizeof($matches['0']); $i < $s; ++$i)
        		{
        			$str = str_replace($matches['0'][$i], '<a href="'.$bounce.str_replace($bad_things, '', $matches['1'][$i]).'">'.str_replace($bad_things, '', $matches['1'][$i])."</a>", $str);
        		}
        	}
        }
        else
        {
            $str = preg_replace("/\[img\](.*?)\[\/img\]/i", "\\1", $str);
        }
        
        // Add quotes back to image tag if missing
        
        if (preg_match("/\<img src=[^\"\'].*?\>/i", $str))
        {
			$str = preg_replace("/<img src=([^\"\'\s]+)(.*?)\/\>/i", "<img src=\"\\1\" \\2/>", $str);
        }
        
        /** -------------------------------------
        /**  Style tags
        /** -------------------------------------*/
        
        // [style=class_name]stuff..[/style]  
    
        $str = preg_replace("/\[style=(.*?)\](.*?)\[\/style\]/si", "<span class=\"\\1\">\\2</span>", $str);    


        return $str;
    }
    /* END */
    
    /** -----------------------------------------
    /**  Make images safe
    /** -----------------------------------------*/
    
    // This simply removes parenthesis so that javascript event handlers
    // can't be invoked. 

	function image_sanitize($matches)
	{		
		$url = str_replace(array('(', ')'), '', $matches['1']);
		
		if (preg_match("/\s+alt=(\"|\')([^\\1]*?)\\1/", $matches['1'], $alt_match))
		{
			$url = trim(str_replace($alt_match['0'], '', $url));
			$alt = str_replace(array('"', "'"), '', $alt_match['2']);
		}
		else
		{
			$alt = str_replace(array('"', "'"), '', $url);
			
			if (substr($alt, -1) == '/')
			{
				$alt = substr($alt, 0, -1);
			}
			
			$alt = substr($alt, strrpos($alt, '/')+1);
		}
		
		return "<img src=".$url." alt='".$alt."' />";
	}

    
    /** -----------------------------------------
    /**  Decode and spam protect email addresses
    /** -----------------------------------------*/

    function decode_emails($str)
    {                    
        // [email=your@yoursite]email[/email]

        $str = preg_replace_callback("/\[email=(.*?)\](.*?)\[\/email\]/i", array($this, "create_mailto"),$str);
        
        // [email]joe@xyz.com[/email]

        $str = preg_replace_callback("/\[email\](.*?)\[\/email\]/i", array($this, "create_mailto"),$str);
        
        return $str;
    }
    /* END */
    

    /** -------------------------------------
    /**  Format Email via callback
    /** -------------------------------------*/

    function create_mailto($matches)
    {   
        $title = ( ! isset($matches['2'])) ? $matches['1'] : $matches['2'];
    
        if ($this->encode_email == TRUE)
        {
            return $this->encode_email($matches['1'], $title, TRUE);
        }
        else
        {
            return "<a href=\"mailto:".$matches['1']."\">".$title."</a>";        
        }
    }
    /* END */
    

    /** ----------------------------------------
    /**  Font sizing matrix via callback
    /** ----------------------------------------*/

    function font_matrix($matches)
    {
        switch($matches['1'])
        {
            case 1  : $size = '9px';
                break;
            case 2  : $size = '11px';
                break;
            case 3  : $size = '14px';
                break;
            case 4  : $size = '16px';
                break;
            case 5  : $size = '18px';
                break;
            case 6  : $size = '20px';
                break;
            default : $size = '11px';
                break;
        }
    
        return "<span style=\"font-size:".$size.";\">".$matches['2']."</span>";
    }
    /* END */

    
    
    /** -------------------------------------
    /**  Encode tags
    /** -------------------------------------*/
    
    function encode_tags($str) 
    {  
		return str_replace(array("<", ">"), array("&lt;", "&gt;"), $str);
    }
    /* END */



    /** -------------------------------------
    /**  Strip IMG tags
    /** -------------------------------------*/

    function strip_images($str)
    {    
        $str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
        $str = preg_replace("#<img\s+.*?src\s*=\s*(.+?)\s*\>#", "\\1", $str);
                
        return $str;
    }
    /* END */



    /** -------------------------------------
    /**  Emoticon replacement
    /** -------------------------------------*/

    function emoticon_replace($str)
    {
        if ($this->smiley_array === FALSE OR $this->parse_smileys === FALSE)
        {
            return $str;
        }
        
        $str = ' '.$str;
        
        foreach ($this->smiley_array as $key => $val)
        {        
            $str = preg_replace("|([\s\.\,\>])".preg_quote($key)."|is", "\\1<img src=\"".$this->emoticon_path.$this->smiley_array[$key]['0']."\" width=\"".$this->smiley_array[$key]['1']."\" height=\"".$this->smiley_array[$key]['2']."\" alt=\"".$this->smiley_array[$key]['3']."\" style=\"border:0;\" />", $str);
        }
        
        return ltrim($str);
    }
    /* END */



    /** -------------------------------------
    /**  Word censor
    /** -------------------------------------*/

    function filter_censored_words($str)
    {
    	global $REGX;
    	
        if ($this->word_censor == FALSE)
        {
            return $str;    
        }
        
        $str = ' '.$str.' ';
        
        foreach ($this->censored_words as $badword)
        {
        	// We have entered the high ASCII range, which means it is likely
        	// that this character is a complete word or symbol that is not 
        	// allowed. So, instead of a preg_replace with a word boundary
        	// we simply do a string replace for this bad word.
        	if ((strlen($badword) == 4 OR strlen($badword) == 2) && stristr($badword, '*') === FALSE && ord($badword['0']) > 127 && ord($badword['1']) > 127)
        	{
        		$str = str_replace($badword, (($this->censored_replace != '') ? $this->censored_replace : '#'), $str);
        	}
        	else
        	{
        		if ($this->censored_replace != '')
        		{
        			$str = preg_replace("/\b(".str_replace('\*', '\w*?', preg_quote($badword)).")\b/i", $this->censored_replace, $str);
        		}
        		else
        		{
        			$str = preg_replace("/\b(".str_replace('\*', '\w*?', preg_quote($badword)).")\b/ie", "str_repeat('#', strlen('\\1'))", $str);
        		}
        	}
        }
        
        return trim($str);
    }
    /* END */



    /** -------------------------------------
    /**  Colorize code strings
    /** -------------------------------------*/
		
	function text_highlight($str)
	{		
		// No [code] tags?  No reason to live.  Goodbye cruel world...
		
		if ( ! preg_match_all("/\[code\](.+?)\[\/code\]/si", $str, $matches))
		{      
			return $str;
		}
		
		for ($i = 0; $i < count($matches['1']); $i++)
		{
			$temp = trim($matches['1'][$i]);
			//$temp = $this->decode_pmcode(trim($matches['1'][$i]));
			
			// Turn <entities> back to ascii.  The highlight string function
			// encodes and highlight brackets so we need them to start raw 
			
			$temp = str_replace(array('&lt;', '&gt;'), array('<', '>'), $temp);
			
			// Replace any existing PHP tags to temporary markers so they don't accidentally
			// break the string out of PHP, and thus, thwart the highlighting.
			// While we're at it, convert EE braces
			
			$temp = str_replace(array('<?', '?>', '{', '}', '&#123;', '&#125;', '&#91;', '&#93;', '\\', '&#40;', '&#41;', '</script>'), 
									  array('phptagopen', 'phptagclose', 'braceopen', 'braceclose', 'braceopen', 'braceclose', 'bracketopen', 'bracketeclose', 'backslashtmp', 'parenthesisopen', 'parenthesisclose', 'scriptclose'), 
									  $temp);
				
			
			// The highlight_string function requires that the text be surrounded
			// by PHP tags.  Since we don't know if A) the submitted text has PHP tags,
			// or B) whether the PHP tags enclose the entire string, we will add our
			// own PHP tags around the string along with some markers to make replacement easier later

			$temp = '<?php //tempstart'."\n".$temp.'//tempend ?>'; // <?
			
			// All the magic happens here, baby!

			$temp = highlight_string($temp, TRUE);

			// Prior to PHP 5, the highligh function used icky <font> tags
			// so we'll replace them with <span> tags.
			
			if (abs(phpversion()) < 5)
			{
				$temp = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $temp);
				$temp = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $temp);
			}
			
			// Remove our artificially added PHP, and the syntax highlighting that came with it
			
			$temp = preg_replace("#\<code\>.+?//tempstart\<br />\</span\>#is", "<code>\n", $temp);
			$temp = preg_replace("#\<code\>.+?//tempstart\<br />#is", "<code>\n", $temp);
			$temp = preg_replace("#//tempend.+#is", "</span>\n</code>", $temp);
			$temp = preg_replace("#\<span style=\"color: \#FF8000\"\></span>\n</code>#is", "\n</code>", $temp);
			
			// Replace our markers back to PHP tags.

			$temp = str_replace(array('phptagopen', 'phptagclose', 'braceopen', 'braceclose', 'bracketopen', 'bracketeclose', 'backslashtmp', 'parenthesisopen', 'parenthesisclose', 'scriptclose'), 
									  array('&lt;?', '?&gt;', '&#123;', '&#125;', '&#91;', '&#93;', '\\', '&#40;', '&#41;', '&lt;/script&gt;'), 
									  $temp); //<?
	
			// Cache the code chunk and insert a marker into the original string.
			// we do this so that the auth_xhtml function which gets called later
			// doesn't process our new code chunk
						
			$this->code_chunks[$this->code_counter] = '<div class="codeblock">'.$temp.'</div>';

			$str = str_replace($matches['0'][$i], '{'.$this->code_counter.'yH45k02wsSdrp}', $str);
			
			$this->code_counter++;
		}        

		return $str;
	}
	/* END */
	


    /** -------------------------------------
    /**  NL to <br /> - Except within <pre>
    /** -------------------------------------*/
    
    function nl2br_except_pre($str)
    {
        $ex = explode("pre>",$str);
        $ct = count($ex);
        
        $newstr = "";
        
        for ($i = 0; $i < $ct; $i++)
        {
            if (($i % 2) == 0)
                $newstr .= nl2br($ex[$i]);
            else 
                $newstr .= $ex[$i];
            
            if ($ct - 1 != $i) 
                $newstr .= "pre>";
        }
        
        return $newstr;
    }
    /* END */


    /** -------------------------------------
    /**  Convert ampersands to entities
    /** -------------------------------------*/

    function convert_ampersands($str)
    {
        $str = preg_replace("/&#(\d+);/", "AMP14TX903DVGHY4QW\\1;", $str);
        $str = preg_replace("/&(\w+);/",  "AMP14TX903DVGHY4QT\\1;", $str);
        
        return str_replace(array("&","AMP14TX903DVGHY4QW","AMP14TX903DVGHY4QT"),array("&amp;", "&#","&"), $str);
	}
    /* END */


    /** -------------------------------------------
    /**  Auto XHTML Typography - light version
    /** -------------------------------------------*/
    
    // We use this for weblog entry titles.  It allows us to 
    // format only the various characters without adding <p> tags
    
    function light_xhtml_typography($str)
    {            
		$str = ' '.$str.' ';
        
        $table = array(
        
                        ">\"'"					=> ">&#8220;&#8216;",
                        "'\""					=> "&#8216;&#8220;",
                        "\"' "					=> "&#8221;&#8217; ",
                        "\"'\n"					=> "&#8221;&#8217;\n",

                        "\"' "                  => "&#8221;&#8217; ",
                        "\"'"                   => "&#8220;&#8216;",

                        " \""                   => " &#8220;",
                        " '"                    => " &#8216;",
                        "'"                     => "&#8217;",

                        "\n\""                =>  "\n&#8220;",
                        "\n&#8217;"           =>  "\n&#8216;",
                        "&#8216;\""             => "&#8216;&#8220;",
                    
                        "\" "                   => "&#8221; ",
                        "\"\n"                 => "&#8221;\n",
                        "\"."                   => "&#8221;.",
                        "\","                   => "&#8221;,",
                        "\";"                   => "&#8221;;",
                        "\":"                   => "&#8221;:",
                        "\"!"                   => "&#8221;!",
                        "\"?"                   => "&#8221;?",
                        
                        " -- "                  => "&#8212;",
                        "..."                    => "&#8230;",
                    
                        ".  "                   => ".&nbsp; ",
                        "?  "                   => "?&nbsp; ",
                        "!  "                   => "!&nbsp; ",
                        ":  "                   => ":&nbsp; ",
                        "& "                    => "&amp; "
        );
        
        
        
        foreach ($table as $key => $val)
        {
            $str = str_replace($key, $val, $str);
        }
        
        return substr(substr($str, 0, -1), 1);
    }
    /* END */



    /** -------------------------------------
    /**  Auto XHTML Typography
    /** -------------------------------------*/
    
    // This function makes text into typographically correct.
	// - It turns double spaces into paragraphs.
	// - It adds line breaks where there are single spaces.
	// - It turns single and double quotes into curly quotes.
	// - It turns three dots into ellipsis.
	// - It turns double dashes into em-dashes.
	// Most of this function, however, deals with all the of exceptions to the rules.
	// For example, we don't want quotes within tags to be converted to curly quotes.
	// Or we don't want <blockquote> tags to be surrounded by paragraph tags.

    function xhtml_typography($str)
    {    
        if ($str == '')
            return; 
       	
       	// To make identifying certain things easier we'll add
       	// a tab to the front of the text and a space at
       	// the end.  These will be removed later.
		$str = "\t".$str.' ';
		
		/** -------------------------------------
		/**  Convert ampersands to entities
		/** -------------------------------------*/
                    
        $str = $this->convert_ampersands($str);   
				
		/** -------------------------------------
        /**  Format <blockquote>, <code>, <div>, <span>
        /** -------------------------------------*/
		
		if (preg_match_all("/\[quote\s+(.*?)\]/si", $str, $matches))
		{
			for ($i = 0; $i < count($matches['1']); $i++)
			{			
				$str = str_replace('[quote '.$matches['1'][$i].']', '<blockquote '.$matches['1'][$i].'>', $str);
			}        
		}
		
		// In order to prevent mismatched paragraphs later on we'll do a little formatting
				
		foreach(array('blockquote', 'code', 'div', 'span') as $element)
		{
			if (preg_match_all("/\<".$element."(.*?)\>(.+?)\<\/".$element."\>/si", $str, $matches))
			{
				for ($i = 0; $i < count($matches['1']); $i++)
				{			
					$bq = $matches['2'][$i];
					$p	= '';
					$pc	= '';
					
					/*
					XHTML Strict requires that all <blockquote> tags have their
					content surrounded by another block element.  How annoying is that?
					*/
					
					if ($element == 'blockquote')
					{
						if (substr(trim($bq), 0, 1) != '<' OR ! preg_match("/^<(p|div|pre|h[1-6]|[ou]l).*?>/", trim($bq)))
						{
							$p = '<p>';
						}
						
						if (substr(trim($bq), -1) != '>' OR ! preg_match("/<\/(p|div|pre|h[1-6]|[ou]l).*?>$/", trim($bq)))
						{
							$pc = '</p>';
						}
					}
					
					if (preg_match("/\n\n/", $bq))
					{
						// Either <span>'s or Forum Quotes
						if ($element == 'span' OR ($element == 'blockquote' && (stristr($matches['1'][$i], 'date=') OR stristr($matches['1'][$i], 'author='))))
						{
							$bq = str_replace("\n\n", "\n<br />", $bq);
						}
						else
						{
							$p	= '<p>';
							$pc	= '</p>';
						}
					}
				
					$str = str_replace($matches['0'][$i], '<'.$element.$matches['1'][$i].'>'.$p.$bq.$pc.'</'.$element.'>', $str);
				}        
			}
		}
		

		/** ----------------------------------------
        /**  We need to prevent headings from being wrapped in <p> tags
        /** ----------------------------------------*/

		foreach(array('h1', 'h2', 'h3', 'h4', 'h5', 'h6') as $element)
		{
			$str = preg_replace("/\<(".$element.")(.*?)\>(.+?)\<\/".$element."\>/si", 
								"</p><\\1\\2>\\3</\\1><p>",
								$str);
			/*
			// This caused problems when the same match was used multiple times in a string, rare but it happened
			if (preg_match_all("/\<".$element."(.*?)\>(.+?)\<\/".$element."\>/si", $str, $matches))
			{
				for ($i = 0; $i < count($matches['1']); $i++)
				{							
					$str = str_replace($matches['0'][$i], '</p><'.$element.$matches['1'][$i].'>'.$matches['2'][$i]."</".$element."><p>", $str);
				}        
			}
			*/
		}

		/** ----------------------------------------
        /**  Define temporary markers
        /** ----------------------------------------*/
				
        $nl = 'N848Ff5f9a66a5ffb627cdbDce6N';
        $dq = 'D5ffbFR627fTs3ks0097RHGH5w2D';
        $sq = 'S5GEf899adfqekrFR62700WWde4S';
        $el = 'E57Uhr5IImB03YQwe3X4X50kryuE';
        $pt = 'Y573Bdd7I4DWddQwe3X48dkwiueH';
	
		/** ----------------------------------------
        /**  Convert elipsis to a temporary marker
        /** ----------------------------------------*/

		$str = preg_replace("#([^\.\s])\.\.\.(\s|<br />|</p>)#", "\\1$el\\2", $str);

		/** ----------------------------------------
        /**  Convert quotes within words with marker
        /** ----------------------------------------*/

        $str = preg_replace("/(\S+)\"(\S+?)\"/", "\\1$dq\\2$dq", $str);    
        
		/** ----------------------------------------
        /**  Replace all newlines with markers
        /** ----------------------------------------*/
        
        $str = preg_replace("/(\015\012)|(\015)|(\012)/",$nl, $str);
        
		/** ----------------------------------------
        /**  Replace quotes within tags
        /** ----------------------------------------*/

        // We don't want the auto typography feature to affect the quotes 
        // that appear inside tags so we'll replace all single and double 
        // quotes within tags with temporary markers.
        
		if ($this->allow_js_img_anchors == TRUE)
		{
			$js_greater = '389avaEgjke9eCAJw9j';
			$js_lesser  = '9090anvaERJAK9hjfaj';
			
			$js = array(   
							'onBlur',
							'onChange',
							'onClick',
							'onFocus',
							'onLoad',
							'onMouseOver',
							'onmouseup',
							'onmousedown',
							'onSelect',
							'onSubmit',
							'onUnload',
							'onkeypress',
							'onkeydown',
							'onkeyup',
							'onresize'
						);
			
			
			foreach ($js as $val)
			{
				if (preg_match_all("/<img src\s*=.+?".$val."\s*\=\"(.+?)\".*?\>/i", $str, $matches))
				{
					for ($i = 0; $i < count($matches['1']); $i++)
					{
						$temp[$i] = str_replace(array('<','>'), array($js_lesser,$js_greater), $matches['1'][$i]);
						$str = str_replace($matches['1'][$i], $temp[$i], $str);
					}			
				}
				
				if (preg_match_all("/<a href\s*=.+?".$val."\s*\=\"(.+?)\".*?\>/i", $str, $matches))
				{
					for ($i = 0; $i < count($matches['1']); $i++)
					{
						$temp[$i] = str_replace(array('<','>'), array($js_lesser,$js_greater), $matches['1'][$i]);
						$str = str_replace($matches['1'][$i], $temp[$i], $str);
					}
				}
			}
 		}       

        if (preg_match_all("/\<.+?\>/si", $str, $matches))
        {
			for ($i = 0; $i < count($matches['0']); $i++)
			{
				$temp[$i] = preg_replace("/\"/", $dq, $matches['0'][$i]);
				$temp[$i] = str_replace("'", $sq, $temp[$i]);
				$str = str_replace($matches['0'][$i], $temp[$i], $str);
			}
		}
		
		if ($this->allow_js_img_anchors == TRUE)
		{
			$str = str_replace(array($js_lesser, $js_greater), array('<','>'), $str); 
		}		
        
		/** ----------------------------------------
        /**  Replace quotes within PHP
        /** ----------------------------------------*/
        
        // We also need to prevent curly quotes from appearing within PHP code examples.
        // Since we turn PHP tags into entities by default, we'll run the above 
        // code again, only looking for PHP tags
        
        if (preg_match_all("/&lt;\?.+?\?&gt;/si", $str, $matches))
        {
			for($i = 0; $i < count($matches['0']); $i++)
			{
				$temp[$i] = preg_replace("/\"/", $dq, $matches['0'][$i]);
				$temp[$i] = str_replace("'", $sq, $temp[$i]);
				$str = str_replace($matches['0'][$i], $temp[$i], $str);
			}
		}
		
		/** ----------------------------------------
        /**  Replace <pre> tags with temporary marker
        /** ----------------------------------------*/
                
        $pretags = array();
        
        if (preg_match_all("/\<pre\>.+?\<\/pre\>/si", $str, $matches))
        {
			for($i=0; $i < count($matches['0']); $i++)
			{
				$pretags[$i] = $matches['0'][$i];
				
				$str = preg_replace("/\<pre\>.+?\<\/pre\>/si", $i.$pt, $str, 1);
			}        
        }
        
        // ----------------------------------------
        //  Some people put their next immediately after
        //  the closing blockquote tag, so I have saved them
        //  from themselves and added a line break. - Paul
		// ----------------------------------------

		$str = preg_replace("/(\<\/blockquote\>)([a-z])/i", "\\1".$nl."\\2", $str); 

		/** ----------------------------------------
        /**  Define the translation table
        /** ----------------------------------------*/
		
		// Closing tag with a quote following should have a closing quote
		
		$str = preg_replace("#(<\/[a-z]+>)(\"|$dq)#", "\\1&#8221;", $str);
		
        // Note: The order is very important so don't change it.
            
        $table = array(
        
                        ">$dq'"					=> ">&#8220;&#8216;",
                        "'$dq<"					=> "&#8217;&#8221;<",
                        ">$dq"					=> ">&#8220;",
                        "$dq<"					=> "&#8221;<",
                        "'$dq"					=> "&#8216;&#8220;",
                        "$dq' "					=> "&#8221;&#8217; ",
                        "$dq'$nl"				=> "&#8221;&#8217;$nl",
                        "$dq'<"					=> "&#8221;&#8217;<",
						
						"</em>'"				=> "</em>&#8217;",	// allows <em>Title</em>'s to use apostrophes
                        ">'"					=> ">&#8216;",
                        "'<"					=> "&#8217;<",

                        "\"' "                  => "&#8221;&#8217; ",
                        "\"'"                   => "&#8220;&#8216;",
                        
                        ">\""					=> ">&#8220;",
                        "\"<"					=> "&#8221;<",
                        " \""                   => " &#8220;",
                        " '"                    => " &#8216;",
                        "'"                     => "&#8217;",

                        $nl."\""                =>  $nl."&#8220;",
                        $nl."&#8217;"           =>  $nl."&#8216;",
                        "&#8216;\""             => "&#8216;&#8220;",
                    
                        "\" "                   => "&#8221; ",
                        "\"$nl"                 => "&#8221;".$nl,
                        "\"."                   => "&#8221;.",
                        "\","                   => "&#8221;,",
                        "\";"                   => "&#8221;;",
                        "\":"                   => "&#8221;:",
                        "\"!"                   => "&#8221;!",
                        "\"?"                   => "&#8221;?",
                        
                        " -- "                  => "&#8212;",
                        $el                     => "&#8230;",
                    
                        ".  "                   => ".&nbsp; ",
                        "?  "                   => "?&nbsp; ",
                        "!  "                   => "!&nbsp; ",
                        ":  "                   => ":&nbsp; ",
                        
                        "$nl$nl<blockquote"     => "\n</p>\n<blockquote",
                        "</blockquote>$nl$nl"   => "</blockquote>\n<p>\n",
                        
						"</li>$nl$nl"           => "</li>$nl",
                        
                        "$nl$nl<ul"            => "\n</p>\n<ul",
                        "</ul>$nl$nl"           => "</ul>\n<p>\n",
                        
                        "$nl$nl<ol"            => "\n</p>\n<ol",
                        "</ol>$nl$nl"           => "</ol>\n<p>\n",
                        
                        "$nl$nl<code>"          => "\n</p>\n<code>",
                        "</code>$nl$nl"         => "</code>\n<p>\n",
                        
                        "$nl$nl<pre>"           => "\n</p>\n<pre>",
                        "</pre>$nl$nl"          => "</pre>\n<p>\n",
                            
                        "$nl$nl"                => "\n</p>\n<p>\n",
                        
                        "</p>$nl<p>"      		=> "</p>\n<p>",
                        
                        "</blockquote>$nl"      => "</blockquote>\n<p>\n",
                        
                        "$nl<li>"               => "\n<li>",
                        "$nl</ol>"              => "\n</ol>",
                        "$nl</ul>"              => "\n</ul>"
        );
        
		/** ----------------------------------------
        /**  Do search/replace
        /** ----------------------------------------*/
        
        $str = str_replace(array_keys($table), array_values($table), $str);
        
		/** ----------------------------------------
        /**  Put <pre> tags back into string
        /** ----------------------------------------*/
        
        $i = 0;
        foreach ($pretags as $key => $val)
        {
			$str = str_replace($i.$pt, $pretags[$i], $str);
			$i++;
        }
        
		/** ----------------------------------------
        /**  Convert elipsis back into string
        /** ----------------------------------------*/
        
        $str = str_replace($el, "&#8230;", $str);
       
		/** ----------------------------------------
        /**  Convert newlines to <br /> tags 
        /** ----------------------------------------*/
		
		// ...except within <pre> tags
        
        $newstr = '';
        $ex = explode("pre>", $str);
        $ct = count($ex);
        
        for ($i = 0; $i < $ct; $i++)
        {
            if (($i % 2) == 0)
            {
                $newstr .= str_replace($nl, "\n<br />\n", $ex[$i]);
            }
            else
            { 
                $newstr .= $ex[$i];
            }
        
            if ($ct -1 != $i) 
            {
                $newstr .= "pre>";
            }
        }
        
		/** ----------------------------------------
        /**  Put newlines back
        /** ----------------------------------------*/
        
        $str = str_replace($nl, "\n", $newstr);
        
		/** ----------------------------------------
        /**  Clean up the spaces we added at the beginning
        /** ----------------------------------------*/
		
        $str = substr(substr($str,1), 0, -1);
        
		/** ----------------------------------------
        /**  Add outer opening/closing <p> tags
        /** ----------------------------------------*/
        
        // We only add an opening <p> tag if the first
        // thing in an entry is not a <blockquote>, <pre>, <code>, etc.	
            
        if (substr($str, 0, 5) != "<bloc"  AND
            substr($str, 0, 4) != "<pre"   AND
            substr($str, 0, 5) != "<code"  AND
            substr($str, 0, 4) != "<div"   AND
            substr($str, 0, 2) != "<h"     AND
            substr($str, 0, 3) != "<ol"    AND
            substr($str, 0, 3) != "<ul"	   AND
            substr($str, 0, 3) != "<h1"	   AND
            substr($str, 0, 3) != "<h2"	   AND
            substr($str, 0, 3) != "<h3"	   AND
            substr($str, 0, 3) != "<h4"	   AND
            substr($str, 0, 3) != "<h5"	   AND
            substr(trim($str), 0, 2) != "<p")
        {
			$str = '<p>'.$str;        
        }
        
        if (substr($str, -4) == "</p>")
            $str = substr($str, 0, -4);  
        
        // Add the closing </p> tag at the end of the entry
        // but only if the last thing is not a </blockquote> </pre> etc.
        
        if (substr($str, -6) != "quote>" AND
            substr($str, -4) != "pre>"   AND
            substr($str, -5) != "code>"  AND
            substr($str, -4) != "div>"   AND
            substr($str, -3) != "ol>"    AND
            substr($str, -3) != "ul>"	 AND
            substr($str, -3) != "h1>"	 AND
            substr($str, -3) != "h2>"	 AND
            substr($str, -3) != "h3>"	 AND
            substr($str, -3) != "h4>"	 AND
            substr($str, -3) != "h5>")
        {
			$str = $str."\n</p>";        
        }

		/** ----------------------------------------
        /**  Deal with duplicate <p> tags
        /** ----------------------------------------*/
    
    	// Since we artificially close <p> tags just before any heading (<h3>) in order for them 
    	// not to get wrapped in <p> tags this creates a couple anomalies which we'll correct
        
		$str = preg_replace("/\<p\>\s*?\<\/p\>/si", "", $str);
		$str = preg_replace("/\<p\>\s*?\<p(.*?)\>/si", "<p\\1>", $str);
		$str = preg_replace("/<p([^>]*)>\s*?\<br \/\>/si", "<p\\1>", $str);
        
		/** ----------------------------------------
        /**  Clean up stray paragraph tags.
        /** ----------------------------------------*/
       
		// This loop makes sure we have matching
		// opening/closing <p> tags
		
        $newstr = '';
        $copy = explode("</p>", $str);
        
        for ($i = 0;  $i < count($copy); $i++)
        {
            if (preg_match("|<p(.*?)>|", $copy[$i]))
            {
                $newstr .= $copy[$i]."</p>";
            }
            else
                $newstr .= $copy[$i];
        }
        
        $str = $newstr;
         
		/** ----------------------------------------
        /**  Add quotes back to tags
        /** ----------------------------------------*/
            
        $str = str_replace(array($dq, $sq), array('"', "'"), $str);
                
        // And we're done.  Phew... that was a chore.
            
        return $str;
    }
    /* END */


    /** -------------------------------------
    /**  Encode Email Address
    /** -------------------------------------*/

    function encode_email($email, $title = '', $anchor = TRUE)
    {
		global $TMPL, $LANG;
	
		if (is_object($TMPL) AND isset($TMPL->encode_email) AND $TMPL->encode_email == FALSE)
		{
			return $email;
		}
	
        if ($title == "")
            $title = $email;
        
		if (isset($this->encode_type) AND $this->encode_type == 'noscript')
		{
			$email = str_replace(array('@', '.'), array(' '.$LANG->line('at').' ', ' '.$LANG->line('dot').' '), $email);
			return $email;
		}
		
        $bit = array();
        
        if ($anchor == TRUE)
        { 
            $bit[] = '<'; $bit[] = 'a '; $bit[] = 'h'; $bit[] = 'r'; $bit[] = 'e'; $bit[] = 'f'; $bit[] = '='; $bit[] = '\"'; $bit[] = 'm'; $bit[] = 'a'; $bit[] = 'i'; $bit[] = 'l';  $bit[] = 't'; $bit[] = 'o'; $bit[] = ':';
        }
        
        for ($i = 0; $i < strlen($email); $i++)
        {
            $bit[] .= " ".ord(substr($email, $i, 1));
        }
        
        $temp	= array();
        
        if ($anchor == TRUE)
        {        
            $bit[] = '\"'; $bit[] = '>';
            
            for ($i = 0; $i < strlen($title); $i++)
            {
            	$ordinal = ord($title[$i]);
			
				if ($ordinal < 128)
				{
					$bit[] = " ".$ordinal;            
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
		
						$bit[] = " ".$number;
						$count = 1;
						$temp = array();
					}   
				}
            }
            
            $bit[] = '<'; $bit[] = '/'; $bit[] = 'a'; $bit[] = '>';
       }
        
        $bit = array_reverse($bit);
        ob_start();
        
?><script type="text/javascript">
//<![CDATA[
var l=new Array();
<?php
    
    $i = 0;
    foreach ($bit as $val)
    {
?>l[<?php echo $i++; ?>]='<?php echo $val; ?>';<?php
    }
?>

for (var i = l.length-1; i >= 0; i=i-1){ 
if (l[i].substring(0, 1) == ' ') document.write("&#"+unescape(l[i].substring(1))+";"); 
else document.write(unescape(l[i]));
}
//]]>
</script><?php

        $buffer = ob_get_contents();
        ob_end_clean(); 
        return $buffer;        
    }
    /* END */

}
// END CLASS
?>
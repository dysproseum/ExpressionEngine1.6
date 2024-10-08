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
 File: core.upload.php
-----------------------------------------------------
 Purpose: File uploading class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}



class Upload {

    var $is_image       = 1;
    var $width			= '';
    var $height			= '';
    var $imgtype		= '';
    var $size_str		= '';
    var $mime			= '';
    var $max_size		= 0;
    var $max_width		= 0;
    var $max_height		= 0;
    var $remove_spaces	= 1;
    var $allowed_types	= "img";  // img or all
    var $file_temp		= "";
    var $file_name		= "";
    var $file_type		= "";
    var $file_size		= "";
    var $new_name		= "";
    var $allowed_mimes	= array();
    var $img_mimes		= array();
    var $upload_path	= "../uploads/";
    var $temp_prefix	= "temp_file_";
    var $message		= '';
    var $file_exists	= FALSE;
	var $xss_clean		= TRUE;
    var $error_msg		= '';



    /** -------------------------------------
    /**  Constructor
    /** -------------------------------------*/

    function Upload()
    {
        global $LANG, $PREFS, $SESS;
    
        $LANG->fetch_language_file('upload');
        
        $this->xss_clean = ($PREFS->ini('xss_clean_uploads') == 'n'  OR $SESS->userdata('group_id') == 1) ? FALSE : TRUE;
        
        include_once(PATH_LIB.'mimes.php');
        
        $this->allowed_mimes = $mimes;
        
        $this->img_mimes = array(
								'image/gif',
								'image/jpg', 
								'image/jpe',
								'image/jpeg', 
								'image/pjpeg',
								'image/png',
								'image/x-png', // shakes fist at IE
								'image/bmp'
								);
    }
    /* END */
    

    /** -------------------------------------
    /**  Upload file
    /** -------------------------------------*/

    function upload_file()
    {
        global $IN, $FNS, $DB;  
					
		if ( ! is_uploaded_file($_FILES['userfile']['tmp_name'])) 
		{
            $error = ( ! isset($_FILES['userfile']['error'])) ? 4 : $_FILES['userfile']['error'];

            switch($error)
            { 
                case 1  :   $this->error_msg = 'file_exceeds_ini_limit';
                    break;
                case 3  :   $this->error_msg = 'file_partially_uploaded';
                    break;
                case 4  :   $this->error_msg = 'no_file_selected';
                    break;
                default :   $this->error_msg = 'file_upload_error';
                    break;
            }
            
            return FALSE;
		}
        
		$this->file_temp = $_FILES['userfile']['tmp_name'];		
		$this->file_name = $_FILES['userfile']['name'];
		$this->file_size = $_FILES['userfile']['size'];		
		$this->file_type = preg_replace("/^(.+?);.*$/", "\\1", $_FILES['userfile']['type']);

        /** -------------------------------------
        /**  Determine if the file is an image
        /** -------------------------------------*/
        
        $this->validate_image();

        /** -------------------------------------
        /**  Is the filetype allowed?
        /** -------------------------------------*/
        
        if ( ! $this->allowed_filetype())
        {
			$this->error_msg = 'invalid_filetype';
			
			return FALSE;
        }
      
        /** -------------------------------------
        /**  Is the file size allowed?
        /** -------------------------------------*/
        
        if ( ! $this->allowed_filesize())
        {
			$this->error_msg = 'invalid_filesize';
	
			return FALSE;    
        }
        
        /** -------------------------------------
        /**  Are the dimensions allowed?
        /** -------------------------------------*/
        
        // Note:  If the server has a very restrictive open_basedir this
        // function will fail and thus not set the width/height properties.

        if ( ! $this->allowed_dimensions())
        {
			$this->error_msg = 'invalid_dimensions';
	
			return FALSE;    
        }
        
        /** -------------------------------------
        /**  Set image properties
        /** -------------------------------------*/

		$this->set_properties();        
        
        /** -------------------------------------
        /**  Remove white space in file name
        /** -------------------------------------*/

        if ($this->remove_spaces == 1)
        {
            $this->file_name = preg_replace("/\s+/", "_", $this->file_name);
        }
        
        $this->file_name = $FNS->filename_security($this->file_name);
        
        
        /** -------------------------------------
        /**  Does file already exist?
        /** -------------------------------------*/
        
        // If so we'll give the file a temporary name so we can upload it.
        // The file will be renamed in a different step depending on whether
        // the user wants to overwrite the existing file or use a different name.
        
        // Note:  If $this->new_name is already set it overrides the normal test
        
        if ($this->new_name != '')
        {
			$this->new_name = $this->upload_path.$this->new_name;
        }
        else
        {
			if (file_exists($this->upload_path.$this->file_name))
			{
				$this->new_name = $this->upload_path.$this->temp_prefix.$this->file_name;
				$this->file_exists = TRUE;
			}
			else
			{
				$this->new_name = $this->upload_path.$this->file_name;
				$this->file_exists = FALSE;
			}
        }
        
        /** ---------------------------------------------------
        /**  Move the uploaded file to the final destination
        /** ---------------------------------------------------*/
        
		if ( ! @copy($this->file_temp, $this->new_name))
		{                            
			if ( ! @move_uploaded_file($this->file_temp, $this->new_name))
			{
				 $this->error_msg = 'upload_error';
				 
				 return FALSE;
			}
		}
		
		/** -------------------------------------
        /**  Set Image Properties
        /** -------------------------------------*/
        
        // Note: We called this function earlier but it might have
        // failed if the server is running an open_basedir restriction
        // since we can't access the "tmp" directory above the root.
        // For that reason we will run this function again on the
        // uploaded image in its final location.
        
        /* 
        	If this is an image and getimagesize() returns FALSE, then PHP does
        	not think this file is a valid image, so we delete the image and 
        	throw an error.
        */
        
        if ($this->is_image === 1 && $this->set_properties($this->new_name) === FALSE)
		{
			$this->error_msg = 'invalid_filecontent';
			
			unlink($this->new_name);
			
			return FALSE;
		}
		
		// The $this->mime variable will always return image/jpeg for a jpeg and image/png for a png,
		// but IE 6/7 will sometimes send a different JPEG or PNG MIME during upload so we have to 
		// do a quick conversion before testing. - Paul
		
		$png_mimes  = array('image/x-png');
		$jpeg_mimes = array('image/jpg', 'image/jpe', 'image/jpeg', 'image/pjpeg');
		
		if (in_array($this->file_type, $png_mimes))
		{
			$this->file_type = 'image/png';
		}
		
		if (in_array($this->file_type, $jpeg_mimes))
		{
			$this->file_type = 'image/jpeg';
		}
		
		// We get $this->mime from getimagesize()'s mime value, which sadly was not available until
		// PHP 4.3.2.  So, if it is not set, we let them pass this test because there is no way to check
		if ($this->is_image === 1 && $this->mime != $this->file_type && $this->mime != '')
		{
			$this->error_msg = 'invalid_filecontent';
			
			unlink($this->new_name);
		
			return FALSE;
		}

        /** -------------------------------------
        /**  XSS Clean the file
        /** -------------------------------------*/
 
 		if ($this->do_xss_clean() === FALSE)
 		{
 			$this->error_msg = 'invalid_filecontent';
			
			unlink($this->new_name);
		
			return FALSE;
 		}
		
		/** -------------------------------------
        /**  Permissions
        /** -------------------------------------*/
        
        // Bug fix. Some servers don't honor the upload folder's file permissions
        
		@chmod($this->new_name, 0777);
		
		/** -------------------------------------
        /**  MySQL Timeout Check?
        /** -------------------------------------*/
        
        // If MySQL has a low timeout value, then the connection might have been lost
        // So, we make sure we are still connected and proceed.
        
		$DB->reconnect();
		
		return TRUE;
    }
    /* END */
    
  

    /** -------------------------------------
    /**  Validate image
    /** -------------------------------------*/

    function validate_image()
    {
        $this->is_image = (in_array($this->file_type, $this->img_mimes)) ? 1 : 0;
    }
    /* END */
    

    /** -------------------------------------
    /**  Verify filetype
    /** -------------------------------------*/

    function allowed_filetype()
    {
    	if ( ! strpos($this->file_name, '.'))
    	{
    		return FALSE;
    	}
    
        if ($this->allowed_types == 'img')
        {
            if ($this->is_image == 1)
            {   
                return TRUE;                
            }
            else
            {
                return FALSE;
            }        
        }
        else
        {
        	$ext = $this->fetch_extension();
        
        	if ( ! isset($this->allowed_mimes[$ext]))
        	{
        		return FALSE;
        	}
        	else
        	{
        		return TRUE;
        	}
        }
    }
    /* END */


    /** -------------------------------------
    /**  Set upload directory path
    /** -------------------------------------*/

    function set_upload_path($path)
    {    
        if ( ! @is_dir($path))
        {
			$this->error_msg = 'path_does_not_exist';
			
			return FALSE;
        }
        
		$this->upload_path = $path;
		
		return TRUE;
    }
    /* END */


    /** -------------------------------------
    /**  Set maximum filesize
    /** -------------------------------------*/

    function set_max_filesize($n, $kb = FALSE)
    {
    	if ($kb == TRUE)
    	{
    		$n = $n * 1024;
    	}
  
        $this->max_size = ( ! preg_match("/^[[:digit:]]+$/i", $n)) ? 0 : $n;
    }
    /* END */


    /** -------------------------------------
    /**  Set maximum width
    /** -------------------------------------*/

    function set_max_width($n)
    {    
        $this->max_width = ( ! preg_match("/^[[:digit:]]+$/i", $n)) ? 0 : $n;
    }
    /* END */


    /** -------------------------------------
    /**  Set maximum height
    /** -------------------------------------*/

    function set_max_height($n)
    {
        $this->max_height = ( ! preg_match("/^[[:digit:]]+$/i", $n)) ? 0 : $n;
    }
    /* END */


    /** -------------------------------------
    /**  Set allowed filetypes
    /** -------------------------------------*/

    function set_allowed_types($types)
    {
        $options = array('img', 'all');
    
        if ($types == '' OR ! in_array($types, $options))
            $types = 'img';
		    
        $this->allowed_types = $types; 
    }
    /* END */


    /** -------------------------------------
    /**  Verify filesize
    /** -------------------------------------*/

    function allowed_filesize()
    {
        if ($this->max_size != 0  AND  $this->file_size > $this->max_size)
        {
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }
    /* END */


    /** -------------------------------------
    /**  Verify image dimensions
    /** -------------------------------------*/

    function allowed_dimensions()
    {
        if ($this->is_image != 1)
        {
            return TRUE;    
        }
    
        if (function_exists('getimagesize')) 
        {
            $D = @getimagesize($this->file_temp);
            
            if ($this->max_width > 0 AND $D['0'] > $this->max_width)
            {
                return FALSE;
            }

            if ($this->max_height > 0 AND $D['1'] > $this->max_height)
            {
                return FALSE;
            }
                       
            return TRUE;
        }

        return TRUE;
    }
    /* END */


    /** -------------------------------------
    /**  Set image properties
    /** -------------------------------------*/

    function set_properties($path = '')
    {
        if ($this->is_image != 1)
        {
            return;    
        }
        
        if ($path == '')
        	$path = $this->file_temp;
    
        if (function_exists('getimagesize')) 
        {
            $D = @getimagesize($path);
            
            // Invalid image!
            if ($D === FALSE OR ($D['0'] == 1 && $D['1'] == 1))
            {
            	//return FALSE;
            }
            
			$this->width    = $D['0'];
			$this->height   = $D['1'];
			$this->imgtype  = $D['2'];
			$this->size_str = $D['3'];  // string containing height and width
			$this->mime		= (isset($D['mime'])) ? $D['mime'] : '';
                       
            return TRUE;
        }

        return TRUE;
    }
    /* END */
	
	
    /** -------------------------------------
    /**  Fetch file extension
    /** -------------------------------------*/

	function fetch_extension()
	{	
		$x = explode('.', $this->file_name);
		return strtolower(end($x));
	}	
	/* END */
	

    
    /** -------------------------------------
    /**  File overwrite 
    /** -------------------------------------*/

    function file_overwrite($orig = '', $new = '', $type_match=TRUE)
    {        
        global $IN;   
        
        $original_file = ($orig != '') ? $orig : $IN->GBL('original_file');
        
        $this->file_name = ($new != '') ? $new : $IN->GBL('file_name');
        
        // If renaming a file, it should have same file type suffix as the original
        
        if ($type_match === TRUE)
        {
        	if (sizeof(explode('.', $this->file_name)) == 1 OR (array_pop(explode('.', $this->file_name)) != array_pop(explode('.', $original_file))))
        	{
        		$this->error_msg = 'invalid_filetype';
			
				return FALSE;
        	}
		}
		
		if ($this->remove_spaces == 1)
        {
            $this->file_name = preg_replace("/\s+/", "_", $this->file_name);
            $original_file = preg_replace("/\s+/", "_", $original_file);
        }
 
        if ( ! @copy($this->upload_path.$this->temp_prefix.$original_file, $this->upload_path.$this->file_name))
		{
			$this->error_msg = 'copy_error';
			
			return FALSE;
        }			
        
		unlink ($this->upload_path.$this->temp_prefix.$original_file);

		return TRUE;    		
    }
    /* END */
 
    /** -------------------------------------
    /**  Run the file through XSS clean
    /** -------------------------------------*/
 
	function do_xss_clean()
	{
		global $REGX;
		
		if ($this->xss_clean !== TRUE)
		{
			return TRUE;
		}
								
		if (filesize($this->new_name) == 0) 
		{
			return FALSE;
		}
		
		/*
		 * Allocate a bit more memory for the XSS Cleaning check as for large images it
		 * has a habit of using it up rather quickly, alas.
		 */
		
		if (function_exists('memory_get_usage') && memory_get_usage() && ini_get('memory_limit') != '')
		{
			$current = ini_get('memory_limit') * 1024 * 1024;
			ini_set('memory_limit', ceil(filesize($this->new_name) + $current)); // When an integer is used, the value is measured in bytes. - PHP.net
        }
		
		if (($data = @file_get_contents($this->new_name)) === FALSE)
		{
			return FALSE;
		}
		
		return $REGX->xss_clean($data, TRUE);
		
		/** -------------------------------------
		/**  Old Code, No Longer Used.  If the XSS Clean (Image) check fails, we simply kill the file 
		/**  instead of saving the modified contents, which still has the "naughty" file on the server.
		/** -------------------------------------*/
		
		if ( ! $fp = @fopen($this->new_name, 'r+b'))
		{
			return FALSE;
		}
			
		flock($fp, LOCK_EX);
		fwrite($fp, $REGX->xss_clean($data, TRUE));
		flock($fp, LOCK_UN);
		fclose($fp);
	}

    /** -------------------------------------
    /**  Show Error Message
    /** -------------------------------------*/

	function show_error($msg = '')
	{
		global $LANG, $DSP;		
		
		if ($this->error_msg == '')
		{
			$this->error_msg = 'file_upload_error';
		}
		
		if ($msg != '')
		{
			$this->error_msg = $msg;
		}
		
		return $DSP->error_message($LANG->line($this->error_msg));
	}
	/* END */

}
// END CLASS
?>

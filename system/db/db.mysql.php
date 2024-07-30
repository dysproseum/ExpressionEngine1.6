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
 File: db.mysql.php
-----------------------------------------------------
 Purpose: SQL database abstraction: MySQL
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


//---------------------------------------    
//    DB Cache Class
//---------------------------------------

// This object gets serialized and cached.
// It provides a simple mechanism to store queries
// that are portable as objects

class DB_Cache {

    var $result   = array();
    var $row      = array();
    var $num_rows = 0;
    var $q_count  = 0;
    var $mysqli;
}
// END CLASS



//---------------------------------------    
//	DB Class
//---------------------------------------


class DB {

    // Public variables

    var $hostname       	= 'localhost';
    var $username      		= 'root';
    var $password      		= '';
    var $database       	= '';
    var $prefix         	= 'exp_';       // Table prefix
    var $conntype       	= 1;            // 1 = persistent.  0 = non
    var $cache_dir      	= 'db_cache/';  // Cache directory/path with trailing slash.
    var $debug          	= 0;            // Manually turns on debugging
    var $enable_cache   	= TRUE;         // true/false Enables query caching
	var $error_footer		= '';			// This is used by the update script
	var $error_header		= '';

    // Private variables. 

    var $exp_prefix     	= 'exp_';
    var $cache_path     	= '';
    var $cache_file     	= '';
    var $sql_table      	= '';
    var $insert_id      	= '';
    var $q_count        	= 0;
    var $affected_rows  	= 0;
    var $conn_id        	= FALSE;
    var $query_id       	= FALSE;
    var $fetch_fields   	= FALSE;
    var $field_names    	= array();
    var $tables_list		= array();
    var $show_queries		= FALSE;		// Enables queries to be shown for debugging
    var $queries			= array();		// Stores the queries
    var $server_info		= '';			// MySQL Server Info, like version


    /** ---------------------------------------    
    /**  Constructor
    /** ---------------------------------------*/

    function DB($settings)
    {
        global $PREFS;
        
		$db_settings = array(
								'hostname', 
								'username',
								'password',
								'database',
								'conntype',
								'prefix',
								'debug',
								'show_queries',
								'enable_cache'
							);
       
		foreach ($db_settings as $item)
		{
			if (isset($settings[$item]))
			{
				$this->$item = $settings[$item];
			}
		}
                
		if ( ! preg_match("/_$/", $this->prefix))
		{
            $this->prefix .= '_';
		}
        
        if ($this->enable_cache == TRUE)
        {
			$this->cache_dir = PATH_CACHE.$this->cache_dir; 
			
			if ( ! preg_match('/$', $this->cache_dir))
			{
				$this->cache_dir .= '/';
			}
			
			// We limit the total number of cache files in order to
			// keep some sanity with large sites or ones that get
			// hit by overambitious crawlers.
			if ($dh = @opendir($this->cache_dir))
			{
				$i = 0;
				while (false !== (readdir($dh)))
				{
					$i++;
				}
				
			 	$max = ( ! $PREFS->ini('max_caches') OR ! is_numeric($PREFS->ini('max_caches')) OR $PREFS->ini('max_caches') > 1000) ? 100 : $PREFS->ini('max_caches');
	
				if ($i > 150)
				{
					$this->delete_directory($this->cache_dir);
				}
			}   
	  
        }
    }
    /* END */


    /** ---------------------------------------    
    /**  Forces a Reconnect On Next Query
    /** ---------------------------------------*/
    
    function reconnect()
    {    
		if (function_exists('mysql_ping'))
		{
			if (mysql_ping($this->conn_id) === FALSE)
			{
				$this->conn_id = FALSE;
			}
		}
    }
    /* END */
    
    
	/** ---------------------------------------    
    /**  Connect to database
    /** ---------------------------------------*/
    
    function db_connect($select_db = TRUE)
    {    
        $this->conn_id = ($this->conntype == 0) ?
          $this->mysqli = @mysqli_connect ($this->hostname, $this->username, $this->password, $this->database):
          @mysql_pconnect($this->hostname, $this->username, $this->password);
        
        if ( ! $this->conn_id)
        {            
            return FALSE;        
        }
        
        if ($select_db == TRUE)
        {
			if ( ! $this->select_db())
			{
				return FALSE;	
			}
        }
        
        $this->server_info = @mysqli_get_server_info($this->mysqli);
        
        return TRUE;
    }
    /* END */


    /** ---------------------------------------    
    /**  Select database
    /** ---------------------------------------*/

    function select_db()
    {
	// This is handled by mysqli connect.
        return TRUE;
    }
    /* END */


    /** ---------------------------------------    
    /**  Close database connection
    /** ---------------------------------------*/

    function db_close()
    {
        if ($this->conn_id)
            mysqli_close($this->mysqli);
    }         
    /* END */
    

    /** ---------------------------------------    
    /**  DB Query
    /** ---------------------------------------*/
    
    function query($sql)
    { 
		if ($sql == '')
			return;

		$sql = trim($sql);
		$this->affected_rows	= 0;
		$this->insert_id 		= 0;

		// Store the query for debugging
        
        if ($this->show_queries == TRUE)
        {
        	$this->queries[] = $sql;
        }
           
        // Verify table prefix and replace if necessary.
            
        if ($this->prefix != $this->exp_prefix)
        { 
           $sql = preg_replace("/(\W)".$this->exp_prefix."(\S+?)/", "\\1".$this->prefix."\\2", $sql);
        }
                        
        if ($this->enable_cache == TRUE)
        {
        	global $IN;
        
			// The URI being requested will become the name of the cache directory
					
			$this->cache_path = ($IN->URI == '') ? $this->cache_dir.md5('index').'/' : $this->cache_path = $this->cache_dir.md5($IN->URI).'/';
					
			// Convert the SQL query into a hash.  This will become the cache file name.
		
			$this->cache_file = md5($sql);
	
			// Is this query a read type?  
			// If so, return the previously cached data if it exists and bail out.
			
			if (stristr($sql, 'SELECT'))
			{
				if (FALSE !== ($cache = $this->get_cache()))
				{
					return $cache;
				}
			}
		}
        
        // Connect to the DB if we haven't done so on a previous query
        
        if ( ! $this->conn_id)    
        {        
			if ( ! $this->db_connect(0))
			{
				exit("Database Error:  Unable to connect to your database. Your database appears to be turned off or the database connection settings in your config file are not correct. Please contact your hosting provider if the problem persists.");
			}
			
			if ( ! $this->select_db())
			{
				exit("Database Error:  Unable to select your database");
			}
        }

        // Execute the query
        if ( ! $this->query_id = mysqli_query($this->mysqli, $sql))
        {
            if ($this->debug)
            {
                return $this->db_error("MySQL ERROR:", $this->conn_id, $sql);
            }
          
			return FALSE;
        }

        // Increment the query counter
        
        $this->q_count++;

        // Determine if the query is one of the 'write' types. If so, gather the
        // affected rows and insert ID, and delete the existing cache file.

        $qtypes = array('INSERT', 'UPDATE', 'DELETE', 'CREATE', 'ALTER', 'DROP', 'REPLACE', 'GRANT', 'REVOKE', 'LOCK', 'UNLOCK', 'TRUNCATE');
                
        foreach ($qtypes as $type)
        {
            if (preg_match("/^$type/i", $sql))
            {  
                $this->affected_rows = mysqli_affected_rows($this->mysqli);
                
                if ($type == 'INSERT' || $type == 'REPLACE')
                {
                    $this->insert_id = mysqli_insert_id($this->mysqli);
                }
                
                // Delete the cache file since the data in it is no longer current.
                
                if ($this->enable_cache == TRUE)
                {
                    $this->delete_cache();
                }

				// Bail out.  We are done
                if ($type == 'INSERT' OR $type == 'UPDATE' OR $type == 'DELETE')
                {
               		return ($this->affected_rows == 0 AND $this->insert_id == 0) ? FALSE : TRUE;     
               	}
               	else
               	{
               		return TRUE;
               	}
            }
        }
        // Fetch the field names, but only if explicitly requested
        // We use this in our SQL utilities functions
        
        if ($this->fetch_fields == TRUE)
        { 
            $this->field_names = array();
            
            while ($field = $this->query_id->fetch_field())
            {
                $this->field_names[] = $field->name;       
            }
         }

        // Fetch the result of the query and assign it to an array.
        // I know, the result *is* an array.  But we want our own
        // numerically indexed array so we can cache it.
        $i = 0;
        $result = array();
        while ($row = mysqli_fetch_array($this->query_id))
        {                                    
            $result[$i] = $row;
            $i++;
        }

        // Free the result.  Optional with MySQL, but might as well be thorough
        
        mysqli_free_result($this->query_id);
        // Instantiate the cache super-class and assign the data 
        // to it if a subsequent query hasn't already done so
        
		$DBC = new DB_Cache;
		$DBC->result   = $result;
		$DBC->row      = (isset($result['0'])) ? $result['0'] : array();
		$DBC->num_rows = $i;

        // Serialize the class and store it in a cache file
        
        if ($this->enable_cache == TRUE)
        {
            $this->store_cache(serialize($DBC));
        }
            
        // Assign the query count to the super-class.  
        // The query count only applies to non-cached queries,
        // so we add it after the class has already been cached.
        
        $DBC->q_count = $this->q_count;
        $DBC->fields  = $this->field_names;
        
        // Return it    
        return $DBC;        
    }
    /* END */


    /** ---------------------------------------    
    /**  Fetch SQL tables
    /** ---------------------------------------*/

    function fetch_tables()
    {      
    	if (sizeof($this->tables_list) > 0)
    	{	
    		return $this->tables_list;	
    	}
    
        if ( ! $this->conn_id)    
        {
			if ( ! $this->db_connect(0))
			{
				exit("Database Error:  Unable to connect to your database. Your database appears to be turned off or the database connection settings in your config file are not correct. Please contact your hosting provider if the problem persists.");
			}
			
			if ( ! $this->select_db())
			{
				exit("Database Error:  Unable to select your database");
			}
        }
        
        // mysql_list_tables() was depreciated, so we switched to using
        // this query, which should work. -Paul
        
        $query = $this->query("SHOW TABLES FROM `{$this->database}` LIKE '{$this->exp_prefix}%'"); // We use $this->exp_prefix as query() will convert it
        
        if ($query->num_rows > 0)
        {
        	foreach($query->result as $row)
        	{
        		$this->tables_list[]  = array_shift($row);
        	}
        }
        
        return $this->tables_list;
    }
    /* END */
    
    
    /** ---------------------------------------    
    /**  Determine if a table exists
    /** ---------------------------------------*/

    function table_exists($table_name)
    {
		if ($this->prefix != $this->exp_prefix)
        { 
			$table_name = preg_replace("/".$this->exp_prefix."(\S+?)/", $this->prefix."\\1", $table_name);
        }
    
		if ( ! in_array($table_name, $this->fetch_tables()))
		{
			return FALSE;
		}
		
		return TRUE;
	}
    /* END */


    /** ---------------------------------------    
    /**  Cache a query
    /** ---------------------------------------*/

    function store_cache($object)
    {
        $dirs = array(PATH_CACHE.'db_cache', substr($this->cache_path, 0, -1));
        
        foreach ($dirs as $dir)
        {       
			if ( ! @is_dir($dir))
			{
				if ( ! @mkdir($dir, 0777))
				{
					return;
				}
				
				@chmod($dir, 0777);            
			}
        }
	      
        if ( ! $fp = @fopen($this->cache_path.$this->cache_file, 'wb'))
            return;

        flock($fp, LOCK_EX);
        fwrite($fp, $object);
        flock($fp, LOCK_UN);
        fclose($fp);
        
		@chmod($this->cache_path.$this->cache_file, 0777);            
    }
    /* END */


    /** ---------------------------------------    
    /**  Retreive a cached query
    /** ---------------------------------------*/

    function get_cache()
    {            
        if ( ! @is_dir($this->cache_path))
            return false;    
        
        if ( ! file_exists($this->cache_path.$this->cache_file))
            return false;
        
        if ( ! $fp = @fopen($this->cache_path.$this->cache_file, 'rb'))
            return false;

        flock($fp, LOCK_SH);
        
        $cachedata = @fread($fp, filesize($this->cache_path.$this->cache_file));
        
        flock($fp, LOCK_UN);
        fclose($fp);
        
        if ( ! is_string($cachedata)) return FALSE;
        
		return unserialize($cachedata);            
    }
    /* END */
    

    /** ---------------------------------------    
    /**  Delete cache files
    /** ---------------------------------------*/

    function delete_cache()
    {    
        if ( ! @is_dir($this->cache_path))
            return FALSE;
    
        if ( ! $fp = @opendir($this->cache_path)) 
        { 
			return FALSE;
        } 
        
        while (false !== ($file = @readdir($fp))) 
        {
             if ($file != "."  AND  $file != "..")
             {
                if ( ! @unlink($this->cache_path.$file))
                {
					return FALSE;
                }
            }
        }
        
		if ( ! @rmdir($this->cache_path))
		{
			return FALSE;
		}
                
        closedir($fp); 
    }
    /* END */


    /** -----------------------------------------
    /**  Delete Direcories
    /** -----------------------------------------*/

    function delete_directory($path, $del_root = FALSE)
    {
        if ( ! $current_dir = @opendir($path))
        {
        	return;
        }
        
        while($filename = @readdir($current_dir))
        {        
            if (@is_dir($path.'/'.$filename) and ($filename != "." and $filename != ".."))
            {
                $this->delete_directory($path.'/'.$filename, TRUE);
            }
            elseif($filename != "." and $filename != "..")
            {
                @unlink($path.'/'.$filename);
            }
        }
        
        @closedir($current_dir);
        
        if ($del_root == TRUE)
        {
            @rmdir($path);
        }
    }
    /* END */

    /** ---------------------------------------    
    /**  MySQL escape string
    /** ---------------------------------------*/

    function escape_str($str)    
    {    
    	if (is_array($str))
    	{
    		foreach($str as $key => $val)
    		{
    			$str[$key] = $this->escape_str($val);
    		}
    		
    		return $str;
    	}
    
        if (function_exists('mysqli_real_escape_string'))
    	{
		if (!isset($this->mysqli)) {
			$this->mysqli = @mysqli_connect ($this->hostname, $this->username, $this->password, $this->database);
		}
			return $this->mysqli->real_escape_string(stripslashes($str));
		}
		else
		{
        	return addslashes(stripslashes($str));
    	}
    }
    /* END */
    

    /** ---------------------------------------    
    /**  Error Message
    /** ---------------------------------------*/
    
    function db_error($msg, $id="", $sql="") 
    {    
        if ($this->error_header != '')
        {
        	$msg = $this->error_header.$msg;
        }    
    
        if ($id) 
        { 
            $msg .= "<br /><br />";
            $msg .= "Error Number: " . mysqli_errno($this->mysqli);
            $msg .= "<br /><br />";
            $msg .= "Description: "  . mysqli_error($this->mysqli);
        }
        
        if ($sql)
            $msg .= "<br /><br />Query: ".$sql;
         
        if ($this->error_footer != '')
        {
        	$msg .= $this->error_footer;
        }
        
        exit($msg);
    }    
  
  
    /** ---------------------------------------    
    /**  Write an INSERT string
    /** ---------------------------------------*/

    // This function simplifies the process of writing database inserts.  
    // It returns a correctly formatted SQL insert string.
    //
    // Example:
    //
    //  $data = array('name' => $name, 'email' => $email, 'url' => $url);
    //
    //  $str = $DB->insert_string('exp_weblog', $data);
    //
    //  Produces:  INSERT INTO exp_weblog (name, email, url) VALUES ('Joe', 'joe@joe.com', 'www.joe.com')

    function insert_string($table, $data, $addslashes = FALSE)
    {
        $fields = '';      
        $values = '';
        
        if (stristr($table, '.'))
        {
        	$x = explode('.', $table, 3);
        	$table = $x['0'].'`.`'.$x['1'];
        }
        
        foreach($data as $key => $val) 
        {
            $fields .= '`' . $key . '`, ';
            $val = ($addslashes === TRUE) ? addslashes($val) : $val;
            $values .= "'".$this->escape_str($val)."'".', ';
        }
        
        $fields = preg_replace( "/, $/" , "" , $fields);
        $values = preg_replace( "/, $/" , "" , $values);

        return 'INSERT INTO `'.$table.'` ('.$fields.') VALUES ('.$values.')';
    }    
    /* END */


    /** ---------------------------------------    
    /**  Write an UPDATE string
    /** ---------------------------------------*/

    // This function simplifies the process of writing database updates.  
    // It returns a correctly formatted SQL update string.
    //
    // Example:
    //
    //  $data = array('name' => $name, 'email' => $email, 'url' => $url);
    //
    //  $str = $DB->update_string('exp_weblog', $data, "author_id = '1'");
	//
    //  Produces:  UPDATE exp_weblog SET name = 'Joe', email = 'joe@joe.com', url = 'www.joe.com' WHERE author_id = '1'

    function update_string($table, $data, $where)
    {
        if ($where == '')
            return false;
    
        $str  = '';
        $dest = '';
        
        if (stristr($table, '.'))
        {
        	$x = explode('.', $table, 3);
        	$table = $x['0'].'`.`'.$x['1'];
        }
        
        foreach($data as $key => $val) 
        {
            $str .= '`'.$key."` = '".$this->escape_str($val)."', ";
        }

        $str = preg_replace( "/, $/" , "" , $str);
        
        if (is_array($where))
        {
            foreach ($where as $key => $val)
            {
                $dest .= $key." = '".$this->escape_str($val)."' AND ";
            }
            
            $dest = preg_replace( "/AND $/" , "" , $dest);
        }
        else
            $dest = $where;

        return 'UPDATE `'.$table.'` SET '.$str.' WHERE '.$dest;        
    }    
    /* END */


}
// END CLASS
?>

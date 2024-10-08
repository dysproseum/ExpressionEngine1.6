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
 File: core.xmlrpc.php
-----------------------------------------------------
 Purpose: XML-RPC class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

if ( ! function_exists('xml_parser_create'))
{	
    exit('Your PHP installation does not support XML');
}


class XML_RPC {

	// Some of this could be elsewhere, but I left it up here for easy access
	
	var $xmlrpcI4		= 'i4'; 
	var $xmlrpcInt		= 'int';
	var $xmlrpcBoolean	= 'boolean';
	var $xmlrpcDouble	= 'double';	
	var $xmlrpcString	= 'string';
	var $xmlrpcDateTime	= 'dateTime.iso8601';
	var $xmlrpcBase64	= 'base64';
	var $xmlrpcArray	= 'array';
	var $xmlrpcStruct	= 'struct';
	
	var $xmlrpcTypes	= array();
	var $valid_parents	= array();
	var $xmlrpcerr		= array();	// Response numbers
	var $xmlrpcstr		= array();  // Response strings
	var $debug			= FALSE; 	// Debugging on or off
	
	var $xmlrpc_defencoding = 'UTF-8';
	var $xmlrpcName			= 'XML-RPC for ';
	var $xmlrpcVersion		= '1.1';
	var $xmlrpcerruser		= 800; // Start of user errors
	var $xmlrpcerrxml		= 100; // Start of XML Parse errors
	var $xmlrpc_backslash	= ''; // formulate backslashes for escaping regexp


	/** -------------------------------------
    /**  VALUES THAT MULTIPLE CLASSES NEED
    /** -------------------------------------*/

	function XML_RPC () {
		
		global $PREFS;
		
		$this->xmlrpcName 		= $this->xmlrpcName.APP_NAME;
		$this->xmlrpc_backslash = chr(92).chr(92);
		
		// if ($PREFS->ini('debug') == 1) $this->debug = true;
	
		// Types for info sent back and forth
		$this->xmlrpcTypes = array(
			$this->xmlrpcI4       => '1',
			$this->xmlrpcInt      => '1',
			$this->xmlrpcBoolean  => '1',
			$this->xmlrpcString   => '1',
			$this->xmlrpcDouble   => '1',
			$this->xmlrpcDateTime => '1',
			$this->xmlrpcBase64   => '1',
			$this->xmlrpcArray    => '2',
			$this->xmlrpcStruct   => '3'
			);
			
		// Array of Valid Parents for Various XML-RPC elements
		$this->valid_parents = array('BOOLEAN'			=> array('VALUE'),
									 'I4'				=> array('VALUE'),
									 'INT'				=> array('VALUE'),
									 'STRING'			=> array('VALUE'),
									 'DOUBLE'			=> array('VALUE'),
									 'DATETIME.ISO8601'	=> array('VALUE'),
									 'BASE64'			=> array('VALUE'),
									 'ARRAY'			=> array('VALUE'),
									 'STRUCT'			=> array('VALUE'),
									 'PARAM'			=> array('PARAMS'),
									 'METHODNAME'		=> array('METHODCALL'),
									 'PARAMS'			=> array('METHODCALL', 'METHODRESPONSE'),
									 'MEMBER'			=> array('STRUCT'),
									 'NAME'				=> array('MEMBER'),
									 'DATA'				=> array('ARRAY'),
									 'FAULT'			=> array('METHODRESPONSE'),
									 'VALUE'			=> array('MEMBER', 'DATA', 'PARAM', 'FAULT')
									 );
			
			
		// XML-RPC Responses
		$this->xmlrpcerr['unknown_method']= '1';
		$this->xmlrpcstr['unknown_method']='Unknown method';
		$this->xmlrpcerr['invalid_return']= '2';
		$this->xmlrpcstr['invalid_return']='Invalid return payload: enabling debugging to examine incoming payload';
		$this->xmlrpcerr['incorrect_params']= '3';
		$this->xmlrpcstr['incorrect_params']='Incorrect parameters passed to method';
		$this->xmlrpcerr['introspect_unknown']= '4';
		$this->xmlrpcstr['introspect_unknown']="Can't introspect: method unknown";
		$this->xmlrpcerr['http_error']= '5';
		$this->xmlrpcstr['http_error']="Didn't receive 200 OK from remote server.";
		$this->xmlrpcerr['no_data']= '6';
		$this->xmlrpcstr['no_data']='No data received from server.';
		$this->xmlrpcerr['no_ssl']= '7';
		$this->xmlrpcstr['no_ssl']='No SSL support compiled in.';
		$this->xmlrpcerr['curl_fail']= '8';
		$this->xmlrpcstr['curl_fail']='CURL error';
		$this->xmlrpcerr['multicall_notstruct'] = '9';
		$this->xmlrpcstr['multicall_notstruct'] = 'system.multicall expected struct';
		$this->xmlrpcerr['multicall_nomethod']  = '10';
		$this->xmlrpcstr['multicall_nomethod']  = 'missing methodName';
		$this->xmlrpcerr['multicall_notstring'] = '11';
		$this->xmlrpcstr['multicall_notstring'] = 'methodName is not a string';
		$this->xmlrpcerr['multicall_recursion'] = '12';
		$this->xmlrpcstr['multicall_recursion'] = 'recursive system.multicall forbidden';
		$this->xmlrpcerr['multicall_noparams']  = '13';
		$this->xmlrpcstr['multicall_noparams']  = 'missing params';
		$this->xmlrpcerr['multicall_notarray']  = '14';
		$this->xmlrpcstr['multicall_notarray']  = 'params is not an array';
		$this->xmlrpcerr['multicall_notarray']  = '15';
		$this->xmlrpcstr['multicall_notarray']  = 'Invalid Request Payload';
		
	} /* END */
	
	
	
    /** -------------------------------------
    /**  Weblogs.com Type Ping
    /** -------------------------------------*/
    
    // Might move this elsewhere, but it works fine here too...
    
    function weblogs_com_ping($server,$port=80,$name, $blog_url, $rss_url='')
    {
		global $PREFS;
		
		if (stristr($server, 'ping.pmachine.com') !== FALSE)
		{
			$server = str_replace('ping.pmachine.com', 'ping.expressionengine.com', $server);
		}
				
		// $server = "rpc.weblogs.com/RPC2/";
		if (substr($server, 0, 4) != "http") $server = "http://".$server; 
		
		$parts = parse_url($server);
		
		// Weblogs.com Fixeroo
		if (isset($parts['path']) && $parts['path'] == "/RPC2/")
		{
			$path = str_replace('/RPC2/', '/RPC2', $parts['path']);
		}
		else
		{
			$path = (!isset($parts['path'])) ? '/' : $parts['path'];
		}
		
		if (isset($parts['query']) && $parts['query'] != '')
		{
			$path .= '?'.$parts['query'];
		}	
		 
		$client = new XML_RPC_Client($path, $parts['host'], $port);
		$client->timeout = 5;
		
		if (stristr($parts['host'], 'ping.expressionengine.com') === FALSE)
		{
			if ($rss_url != '')
			{
				$message = new XML_RPC_Message('weblogUpdates.extendedPing',array(
												new XML_RPC_Values($name),
												new XML_RPC_Values($blog_url),
												new XML_RPC_Values($PREFS->ini('site_index')),
												new XML_RPC_Values($rss_url)));
						
				if ( ! $result = $client->send($message) OR ! $result->value())
				{
					$message = new XML_RPC_Message('weblogUpdates.ping',
													array(
													new XML_RPC_Values($name),
													new XML_RPC_Values($blog_url)));
				}
				else
				{
					if ( ! $result->value())
					{
						return $result->errstr;
					}
					else
					{
						return TRUE;
					}
				}
			}
			else
			{
				$message = new XML_RPC_Message('weblogUpdates.ping',
												array(
												new XML_RPC_Values($name),
												new XML_RPC_Values($blog_url)));
			}
		}
		else
		{
			if ( ! $license = $PREFS->ini('license_number'))
			{
				return 'Invalid License';
			}
			
			$message = new XML_RPC_Message('ExpressionEngine.ping',
											array(
											new XML_RPC_Values($name),
											new XML_RPC_Values($blog_url),
											new XML_RPC_Values($license)));
		}
    
   	 	if ( ! $result = $client->send($message)) return $result->errstr;
    	
    	if ( ! $result->value())
		{
			return $result->errstr;
		}
		else
		{
			return TRUE;
		}
    }
	
	
} // END XML_RPC Class

	
	
////////////
///////////
///////////


class XML_RPC_Client extends XML_RPC
{
	var $path			= '';
	var $server			= '';
	var $port			= 80;
	var $errno			= '';
	var $errstring		= '';
	var $timeout		= 5;
	var $no_multicall	= false;

	function XML_RPC_Client($path, $server, $port=80)
	{
		global $PREFS;
		
		parent::XML_RPC();
		
		$this->port = $port; 
		$this->server = $server; 
		$this->path = $path;
	}
	
	function send($msg)
	{
		if (is_array($msg))
		{
			// Multi-call disabled
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['multicall_recursion'],$this->xmlrpcstr['multicall_recursion']);
			return $r;
		}

		return $this->sendPayload($msg);
	}

	function sendPayload($msg)
	{	
		$fp = @fsockopen($this->server, $this->port,$this->errno, $this->errstr, $this->timeout);
		
		if (! is_resource($fp))
		{
			error_log($this->xmlrpcstr['http_error']);
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['http_error'],$this->xmlrpcstr['http_error']);
			return $r;
		}
		
		if(empty($msg->payload))
		{
			// $msg = XML_RPC_Messages
			$msg->createPayload();
		}
		
		$r = "\r\n";
		$op  = "POST {$this->path} HTTP/1.0$r";
		$op .= "Host: {$this->server}$r";
		$op .= "Content-Type: text/xml$r";
		$op .= "User-Agent: {$this->xmlrpcName}$r";
		$op .= "Content-Length: ".strlen($msg->payload). "$r$r";
		$op .= $msg->payload;
		

		if (!fputs($fp, $op, strlen($op)))
		{
			error_log($this->xmlrpcstr['http_error']);
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['http_error'], $this->xmlrpcstr['http_error']);
			return $r;
		}
		$resp = $msg->parseResponse($fp);
		fclose($fp);
		return $resp;
	}

} // end class XML_RPC_Client


///////////
///////////
///////////



class XML_RPC_Response
{
	var $val = 0;
	var $errno = 0;
	var $errstr = '';
	var $headers = array();

	function XML_RPC_Response($val, $code = 0, $fstr = '')
	{
		if ($code != 0)
		{
			// error
			$this->errno = $code;
			$this->errstr = htmlentities($fstr); 
		}
		else if (!is_object($val))
		{
			// programmer error, not an object
			error_log("Invalid type '" . gettype($val) . "' (value: $val) passed to XML_RPC_Response.  Defaulting to empty value.");
			$this->val = new XML_RPC_Values();
		}
		else
		{
			$this->val = $val;
		}
	}

	function faultCode()
	{
		return $this->errno;
	}

	function faultString()
	{
		return $this->errstr;
	}

	function value()
	{
		return $this->val;
	}
	
	function prepare_response()
	{
		$result = "<methodResponse>\n";
		if ($this->errno)
		{
			$result .= '<fault>
	<value>
		<struct>
			<member>
				<name>faultCode</name>
				<value><int>' . $this->errno . '</int></value>
			</member>
			<member>
				<name>faultString</name>
				<value><string>' . $this->errstr . '</string></value>
			</member>
		</struct>
	</value>
</fault>';
		}
		else
		{
			$result .= "<params>\n<param>\n" .
					$this->val->serialize_class() . 
					"</param>\n</params>";
		}
		$result .= "\n</methodResponse>";
		return $result;
	}
	
	function decode($array=FALSE)
	{
		global $REGX;
		
		if ($array !== FALSE && is_array($array))
		{
			while (list($key) = each($array))
			{
				if (is_array($array[$key]))
				{
					$array[$key] = $this->decode($array[$key]);
				}
				else
				{
					$array[$key] = $REGX->xss_clean($array[$key]);
				}
			}
			
			$result = $array;
		}
		else
		{
			$result = $this->xmlrpc_decoder($this->val);
			
			if (is_array($result))
			{
				$result = $this->decode($result);
			}
			else
			{
				$result = $REGX->xss_clean($result);
			}
		}
		
		return $result;
	}

	
	
	/** -------------------------------------
	/**  XML-RPC Object to PHP Types
	/** -------------------------------------*/

	function xmlrpc_decoder($xmlrpc_val)
	{
		$kind = $xmlrpc_val->kindOf();

		if($kind == 'scalar')
		{
			return $xmlrpc_val->scalarval();
		}
		elseif($kind == 'array')
		{
			reset($xmlrpc_val->me);
			list($a,$b) = each($xmlrpc_val->me);
			$size = sizeof($b);
			
			$arr = array();

			for($i = 0; $i < $size; $i++)
			{
				$arr[] = $this->xmlrpc_decoder($xmlrpc_val->me['array'][$i]);
			}
			return $arr; 
		}
		elseif($kind == 'struct')
		{
			reset($xmlrpc_val->me['struct']);
			$arr = array();

			while(list($key,$value) = each($xmlrpc_val->me['struct']))
			{
				$arr[$key] = $this->xmlrpc_decoder($value);
			}
			return $arr;
		}
	}
	
	
	/** -------------------------------------
	/**  ISO-8601 time to server or UTC time
	/** -------------------------------------*/

	function iso8601_decode($time, $utc=0)
	{
		// return a timet in the localtime, or UTC
		$t = 0;
		if (ereg("([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})", $time, $regs))
		{
			if ($utc == 1)
				$t = gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
			else
				$t = mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
		} 
		return $t;
	}
	
} // End Response Class




////////////
////////////
////////////


class XML_RPC_Message extends XML_RPC
{
	var $payload;
	var $method_name;
	var $params			= array();
	var $xh 			= array();

	function XML_RPC_Message($method, $pars=0)
	{
		parent::XML_RPC();
		
		$this->method_name = $method;
		if (is_array($pars) && sizeof($pars) > 0)
		{
			for($i=0; $i<sizeof($pars); $i++)
			{
				// $pars[$i] = XML_RPC_Values
				$this->params[] = $pars[$i];
			}
		}
	}
	
	/** -------------------------------------
	/**  Create Payload to Send
	/** -------------------------------------*/
	
	function createPayload()
	{
		$this->payload = "<?xml version=\"1.0\"?".">\r\n<methodCall>\r\n";
		$this->payload .= '<methodName>' . $this->method_name . "</methodName>\r\n";
		$this->payload .= "<params>\r\n";
		
		for($i=0; $i<sizeof($this->params); $i++)
		{
			// $p = XML_RPC_Values
			$p = $this->params[$i];
			$this->payload .= "<param>\r\n".$p->serialize_class()."</param>\r\n";
		}
		
		$this->payload .= "</params>\r\n</methodCall>\r\n";
	}
	
	/** -------------------------------------
	/**  Parse External XML-RPC Server's Response
	/** -------------------------------------*/
	
	function parseResponse($fp)
	{
		$data = '';
		
		while($datum = fread($fp, 4096))
		{
			$data .= $datum;
		}
		
		/** -------------------------------------
		/**  DISPLAY HTTP CONTENT for DEBUGGING
		/** -------------------------------------*/
		
		if ($this->debug)
		{
			echo "<pre>";
			echo "---DATA---\n" . htmlspecialchars($data) . "\n---END DATA---\n\n";
			echo "</pre>";
		}
		
		/** -------------------------------------
		/**  Check for data
		/** -------------------------------------*/

		if($data == "")
		{
			error_log($this->xmlrpcstr['no_data']);
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['no_data'], $this->xmlrpcstr['no_data']);
			return $r;
		}
		
		
		/** -------------------------------------
		/**  Check for HTTP 200 Response
		/** -------------------------------------*/
		
		if(ereg("^HTTP",$data) && !ereg("^HTTP/[0-9\.]+ 200 ", $data))
		{
			$errstr= substr($data, 0, strpos($data, "\n")-1);
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['http_error'], $this->xmlrpcstr['http_error']. ' (' . $errstr . ')');
			return $r;
		}
		
		/** -------------------------------------
		/**  Create and Set Up XML Parser
		/** -------------------------------------*/
	
		$parser = xml_parser_create($this->xmlrpc_defencoding);

		$this->xh[$parser]				 = array();
		$this->xh[$parser]['isf']		 = 0;
		$this->xh[$parser]['ac']		 = '';
		$this->xh[$parser]['headers'] 	 = array();
		$this->xh[$parser]['stack']		 = array();
		$this->xh[$parser]['valuestack'] = array();
		$this->xh[$parser]['isf_reason'] = 0;

		xml_set_object($parser, $this);
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
		xml_set_element_handler($parser, 'open_tag', 'closing_tag');
		xml_set_character_data_handler($parser, 'character_data');
		//xml_set_default_handler($parser, 'default_handler');


		/** -------------------------------------
		/**  GET HEADERS
		/** -------------------------------------*/
		
		$lines = explode("\r\n", $data);
		while (($line = array_shift($lines)))
		{
			if (strlen($line) < 1)
			{
				break;
			}
			$this->xh[$parser]['headers'][] = $line;
		}
		$data = implode("\r\n", $lines);
		
		
		/** -------------------------------------
		/**  PARSE XML DATA
		/** -------------------------------------*/

		if (!xml_parse($parser, $data, sizeof($data)))
		{
			$errstr = sprintf('XML error: %s at line %d',
					xml_error_string(xml_get_error_code($parser)),
					xml_get_current_line_number($parser));
			//error_log($errstr);
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return']);
			xml_parser_free($parser);
			return $r;
		}
		xml_parser_free($parser);
		
		/** ---------------------------------------
		/**  Got Ourselves Some Badness, It Seems
		/** ---------------------------------------*/
		
		if ($this->xh[$parser]['isf'] > 1)
		{
			if ($this->debug)
			{
				echo "---Invalid Return---\n";
				echo $this->xh[$parser]['isf_reason'];
				echo "---Invalid Return---\n\n";
			}
				
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'],$this->xmlrpcstr['invalid_return'].' '.$this->xh[$parser]['isf_reason']);
			return $r;
		}
		elseif ( ! is_object($this->xh[$parser]['value']))
		{
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'],$this->xmlrpcstr['invalid_return'].' '.$this->xh[$parser]['isf_reason']);
			return $r;
		}
		
		/** -------------------------------------
		/**  DISPLAY XML CONTENT for DEBUGGING
		/** -------------------------------------*/
		
		if ($this->debug)
		{
			echo "<pre>";
			
			if (count($this->xh[$parser]['headers'] > 0))
			{
				echo "---HEADERS---\n";
				foreach ($this->xh[$parser]['headers'] as $header)
				{
					echo "$header\n";
				}
				echo "---END HEADERS---\n\n";
			}
			
			echo "---DATA---\n" . htmlspecialchars($data) . "\n---END DATA---\n\n";
			
			echo "---PARSED---\n" ;
			var_dump($this->xh[$parser]['value']);
			echo "\n---END PARSED---</pre>";
		}
		
		/** -------------------------------------
		/**  SEND RESPONSE
		/** -------------------------------------*/
		
		$v = $this->xh[$parser]['value'];
			
		if ($this->xh[$parser]['isf'])
		{
			$errno_v = $v->me['struct']['faultCode'];
			$errstr_v = $v->me['struct']['faultString'];
			$errno = $errno_v->scalarval();

			if ($errno == 0)
			{
				// FAULT returned, errno needs to reflect that
				$errno = -1;
			}

			$r = new XML_RPC_Response($v, $errno, $errstr_v->scalarval());
		}
		else
		{
			$r = new XML_RPC_Response($v);
		}

		$r->headers = $this->xh[$parser]['headers'];
		return $r;
	}
	
	/** ------------------------------------
	/**  Begin Return Message Parsing section
	/** ------------------------------------*/
	
	// quick explanation of components:
	//   ac - used to accumulate values
	//   isf - used to indicate a fault
	//   lv - used to indicate "looking for a value": implements
	//        the logic to allow values with no types to be strings
	//   params - used to store parameters in method calls
	//   method - used to store method name
	//	 stack - array with parent tree of the xml element,
	//			 used to validate the nesting of elements

	/** -------------------------------------
	/**  Start Element Handler
	/** -------------------------------------*/

	function open_tag($the_parser, $name, $attrs)
	{
		// If invalid nesting, then return
		if ($this->xh[$the_parser]['isf'] > 1) return;
		
		// Evaluate and check for correct nesting of XML elements
		
		if (count($this->xh[$the_parser]['stack']) == 0)
		{
			if ($name != 'METHODRESPONSE' && $name != 'METHODCALL')
			{
				$this->xh[$the_parser]['isf'] = 2;
				$this->xh[$the_parser]['isf_reason'] = 'Top level XML-RPC element is missing';
				return;
			}
		}
		else
		{
			// not top level element: see if parent is OK
			if (!in_array($this->xh[$the_parser]['stack'][0], $this->valid_parents[$name]))
			{
				$this->xh[$the_parser]['isf'] = 2;
				$this->xh[$the_parser]['isf_reason'] = "XML-RPC element $name cannot be child of ".$this->xh[$the_parser]['stack'][0];
				return;
			}
		}
		
		switch($name)
		{
			case 'STRUCT':
			case 'ARRAY':
				// Creates array for child elements
				
				$cur_val = array('value' => array(),
								 'type'	 => $name);
								 
				array_unshift($this->xh[$the_parser]['valuestack'], $cur_val);
			break;
			case 'METHODNAME':
			case 'NAME':
				$this->xh[$the_parser]['ac'] = '';
			break;
			case 'FAULT':
				$this->xh[$the_parser]['isf'] = 1;
			break;
			case 'PARAM':
				$this->xh[$the_parser]['value'] = null;
			break;
			case 'VALUE':
				$this->xh[$the_parser]['vt'] = 'value';
				$this->xh[$the_parser]['ac'] = '';
				$this->xh[$the_parser]['lv'] = 1;
			break;
			case 'I4':
			case 'INT':
			case 'STRING':
			case 'BOOLEAN':
			case 'DOUBLE':
			case 'DATETIME.ISO8601':
			case 'BASE64':
				if ($this->xh[$the_parser]['vt'] != 'value')
				{
					//two data elements inside a value: an error occurred!
					$this->xh[$the_parser]['isf'] = 2;
					$this->xh[$the_parser]['isf_reason'] = "'Twas a $name element following a ".$this->xh[$the_parser]['vt']." element inside a single value";
					return;
				}
				
				$this->xh[$the_parser]['ac'] = '';
			break;
			case 'MEMBER':
				// Set name of <member> to nothing to prevent errors later if no <name> is found
				$this->xh[$the_parser]['valuestack'][0]['name'] = '';
				
				// Set NULL value to check to see if value passed for this param/member
				$this->xh[$the_parser]['value'] = null;
			break;
			case 'DATA':
			case 'METHODCALL':
			case 'METHODRESPONSE':
			case 'PARAMS':
				// valid elements that add little to processing
			break;
			default:
				/// An Invalid Element is Found, so we have trouble
				$this->xh[$the_parser]['isf'] = 2;
				$this->xh[$the_parser]['isf_reason'] = "Invalid XML-RPC element found: $name";
			break;
		}
		
		// Add current element name to stack, to allow validation of nesting
		array_unshift($this->xh[$the_parser]['stack'], $name);

		if ($name != 'VALUE') $this->xh[$the_parser]['lv'] = 0;
	}
	/* END */


	/** -------------------------------------
	/**  End Element Handler
	/** -------------------------------------*/

	function closing_tag($the_parser, $name)
	{
		if ($this->xh[$the_parser]['isf'] > 1) return;
		
		// Remove current element from stack and set variable
		// NOTE: If the XML validates, then we do not have to worry about
		// the opening and closing of elements.  Nesting is checked on the opening
		// tag so we be safe there as well.
		
		$curr_elem = array_shift($this->xh[$the_parser]['stack']);
	
		switch($name)
		{
			case 'STRUCT':
			case 'ARRAY':
				$cur_val = array_shift($this->xh[$the_parser]['valuestack']);
				$this->xh[$the_parser]['value'] = ( ! isset($cur_val['values'])) ? array() : $cur_val['values'];
				$this->xh[$the_parser]['vt']	= strtolower($name);
			break;
			case 'NAME':
				$this->xh[$the_parser]['valuestack'][0]['name'] = $this->xh[$the_parser]['ac'];
			break;
			case 'BOOLEAN':
			case 'I4':
			case 'INT':
			case 'STRING':
			case 'DOUBLE':
			case 'DATETIME.ISO8601':
			case 'BASE64':
				$this->xh[$the_parser]['vt'] = strtolower($name);
				
				if ($name == 'STRING')
				{
					$this->xh[$the_parser]['value'] = $this->xh[$the_parser]['ac'];
				}
				elseif ($name=='DATETIME.ISO8601')
				{
					$this->xh[$the_parser]['vt']    = $this->xmlrpcDateTime;
					$this->xh[$the_parser]['value'] = $this->xh[$the_parser]['ac'];
				}
				elseif ($name=='BASE64')
				{
					$this->xh[$the_parser]['value'] = base64_decode($this->xh[$the_parser]['ac']);
				}
				elseif ($name=='BOOLEAN')
				{
					// Translated BOOLEAN values to TRUE AND FALSE
					if ($this->xh[$the_parser]['ac'] == '1')
					{
						$this->xh[$the_parser]['value'] = TRUE;
					}
					else
					{
						$this->xh[$the_parser]['value'] = FALSE;
					}
				}
				elseif ($name=='DOUBLE')
				{
					// we have a DOUBLE
					// we must check that only 0123456789-.<space> are characters here
					if (!ereg("^[+-]?[eE0123456789 \\t\\.]+$", $this->xh[$the_parser]['ac']))
					{
						$this->xh[$the_parser]['value'] = 'ERROR_NON_NUMERIC_FOUND';
					}
					else
					{
						$this->xh[$the_parser]['value'] = (double)$this->xh[$the_parser]['ac'];
					}
				}
				else
				{
					// we have an I4/INT
					// we must check that only 0123456789-<space> are characters here
					if (!ereg("^[+-]?[0123456789 \\t]+$", $this->xh[$the_parser]['ac']))
					{
						$this->xh[$the_parser]['value'] = 'ERROR_NON_NUMERIC_FOUND';
					}
					else
					{
						$this->xh[$the_parser]['value'] = (int)$this->xh[$the_parser]['ac'];
					}
				}
				$this->xh[$the_parser]['ac'] = '';
				$this->xh[$the_parser]['lv'] = 3; // indicate we've found a value
			break;
			case 'VALUE':
				// This if() detects if no scalar was inside <VALUE></VALUE>
				if ($this->xh[$the_parser]['vt']=='value')
				{
					$this->xh[$the_parser]['value']	= $this->xh[$the_parser]['ac'];
					$this->xh[$the_parser]['vt']	= $this->xmlrpcString;
				}
				
				// build the XML-RPC value out of the data received, and substitute it
				$temp = new XML_RPC_Values($this->xh[$the_parser]['value'], $this->xh[$the_parser]['vt']);
				
				if (count($this->xh[$the_parser]['valuestack']) && $this->xh[$the_parser]['valuestack'][0]['type'] == 'ARRAY')
				{
					// Array
					$this->xh[$the_parser]['valuestack'][0]['values'][] = $temp;
				}
				else
				{
					// Struct
	    			$this->xh[$the_parser]['value'] = $temp;
				}
			break;
			case 'MEMBER':
				$this->xh[$the_parser]['ac']='';
				
				// If value add to array in the stack for the last element built
				if ($this->xh[$the_parser]['value'])
				{
					$this->xh[$the_parser]['valuestack'][0]['values'][$this->xh[$the_parser]['valuestack'][0]['name']] = $this->xh[$the_parser]['value'];
				}
			break;
			case 'DATA':
				$this->xh[$the_parser]['ac']='';
			break;
			case 'PARAM':
				if ($this->xh[$the_parser]['value'])
				{
					$this->xh[$the_parser]['params'][] = $this->xh[$the_parser]['value'];
				}
			break;
			case 'METHODNAME':
				$this->xh[$the_parser]['method'] = ereg_replace("^[\n\r\t ]+", '', $this->xh[$the_parser]['ac']);
			break;
			case 'PARAMS':
			case 'FAULT':
			case 'METHODCALL':
			case 'METHORESPONSE':
				// We're all good kids with nuthin' to do
			break;
			default:
				// End of an Invalid Element.  Taken care of during the opening tag though
			break;
		}
	}

	/** -------------------------------------
	/**  Parses Character Data
	/** -------------------------------------*/

	function character_data($the_parser, $data)
	{
		if ($this->xh[$the_parser]['isf'] > 1) return; // XML Fault found already
		
		// If a value has not been found
		if ($this->xh[$the_parser]['lv'] != 3)
		{
			if ($this->xh[$the_parser]['lv'] == 1)
			{
				$this->xh[$the_parser]['lv'] = 2; // Found a value
			}
				
			if( ! @isset($this->xh[$the_parser]['ac']))
			{
				$this->xh[$the_parser]['ac'] = '';
			}
				
			$this->xh[$the_parser]['ac'] .= $data;
		}
	}
	
	
	function addParam($par) { $this->params[]=$par; }
	
	function output_parameters($array=FALSE)
	{
		global $REGX;
		
		if ($array !== FALSE && is_array($array))
		{
			while (list($key) = each($array))
			{
				if (is_array($array[$key]))
				{
					$array[$key] = $this->output_parameters($array[$key]);
				}
				else
				{
					/* 'bits is for the MetaWeblog API image bits */
					$array[$key] = ($key == 'bits') ? $array[$key] : $REGX->xss_clean($array[$key]);
				}
			}
			
			$parameters = $array;
		}
		else
		{
			$parameters = array();
		
			for ($i = 0; $i < sizeof($this->params); $i++)
    		{
    			$a_param = $this->decode_message($this->params[$i]);
    			
    			if (is_array($a_param))
    			{
    				$parameters[] = $this->output_parameters($a_param);
    			}
    			else
    			{
    				$parameters[] = $REGX->xss_clean($a_param);
    			}
    		}	
    	}
    	
    	return $parameters;
	}
	
	
	function decode_message($param)
	{
		$kind = $param->kindOf();

		if($kind == 'scalar')
		{
			return $param->scalarval();
		}
		elseif($kind == 'array')
		{
			reset($param->me);
			list($a,$b) = each($param->me);
			
			$arr = array();

			for($i = 0; $i < sizeof($b); $i++)
			{
				$arr[] = $this->decode_message($param->me['array'][$i]);
			}
			
			return $arr; 
		}
		elseif($kind == 'struct')
		{
			reset($param->me['struct']);
			
			$arr = array();

			while(list($key,$value) = each($param->me['struct']))
			{
				$arr[$key] = $this->decode_message($value);
			}
			
			return $arr;
		}
	}
	
} // End XML_RPC_Messages class



//////////
//////////
//////////


class XML_RPC_Values extends XML_RPC
{
	var $me 	= array();
	var $mytype	= 0;

	function XML_RPC_Values($val=-1, $type='')
	{	
		parent::XML_RPC();
		
		if ($val != -1 || $type != '')
		{
			$type = $type == '' ? 'string' : $type;
			
			if ($this->xmlrpcTypes[$type] == 1)
			{
				$this->addScalar($val,$type);
			}
			elseif ($this->xmlrpcTypes[$type] == 2)
			{
				$this->addArray($val);
			}
			elseif ($this->xmlrpcTypes[$type] == 3)
			{
				$this->addStruct($val);
			}
		}
	}

	function addScalar($val, $type='string')
	{
		$typeof = $this->xmlrpcTypes[$type];
		
		if ($this->mytype==1)
		{
			echo '<strong>XML_RPC_Values</strong>: scalar can have only one value<br />';
			return 0;
		}
		
		if ($typeof != 1)
		{
			echo '<strong>XML_RPC_Values</strong>: not a scalar type (${typeof})<br />';
			return 0;
		}

		if ($type == $this->xmlrpcBoolean)
		{
			if (strcasecmp($val,'true')==0 || $val==1 || ($val==true && strcasecmp($val,'false')))
			{
				$val = 1;
			}
			else
			{
				$val=0;
			}
		}

		if ($this->mytype == 2)
		{
			// adding to an array here
			$ar = $this->me['array'];
			$ar[] = new XML_RPC_Values($val, $type);
			$this->me['array'] = $ar;
		}
		else
		{
			// a scalar, so set the value and remember we're scalar
			$this->me[$type] = $val;
			$this->mytype = $typeof;
		}
		return 1;
	}

	function addArray($vals)
	{
		if ($this->mytype != 0)
		{
			echo '<strong>XML_RPC_Values</strong>: already initialized as a [' . $this->kindOf() . ']<br />';
			return 0;
		}

		$this->mytype = $this->xmlrpcTypes['array'];
		$this->me['array'] = $vals;
		return 1;
	}

	function addStruct($vals)
	{
		if ($this->mytype != 0)
		{
			echo '<strong>XML_RPC_Values</strong>: already initialized as a [' . $this->kindOf() . ']<br />';
			return 0;
		}
		$this->mytype = $this->xmlrpcTypes['struct'];
		$this->me['struct'] = $vals;
		return 1;
	}

	function kindOf()
	{
		switch($this->mytype)
		{
			case 3:
				return 'struct';
				break;
			case 2:
				return 'array';
				break;
			case 1:
				return 'scalar';
				break;
			default:
				return 'undef';
		}
	}

	function serializedata($typ, $val)
	{
		$rs = '';
		
		switch($this->xmlrpcTypes[$typ])
		{
			case 3:
				// struct
				$rs .= "<struct>\n";
				reset($val);
				while(list($key2, $val2) = each($val))
				{
					$rs .= "<member>\n<name>{$key2}</name>\n";
					$rs .= $this->serializeval($val2);
					$rs .= "</member>\n";
				}
				$rs .= '</struct>';
			break;
			case 2:
				// array
				$rs .= "<array>\n<data>\n";
				for($i=0; $i < sizeof($val); $i++)
				{
					$rs .= $this->serializeval($val[$i]);
				}
				$rs.="</data>\n</array>\n";
				break;
			case 1:
				// others
				switch ($typ)
				{
					case $this->xmlrpcBase64:
						$rs .= "<{$typ}>" . base64_encode($val) . "</{$typ}>\n";
					break;
					case $this->xmlrpcBoolean:
						$rs .= "<{$typ}>" . ($val ? '1' : '0') . "</{$typ}>\n";
					break;
					case $this->xmlrpcString:
						$rs .= "<{$typ}>" . htmlspecialchars($val). "</{$typ}>\n";
					break;
					default:
						$rs .= "<{$typ}>{$val}</{$typ}>\n";
					break;
				}
			default:
			break;
		}
		return $rs;
	}

	function serialize_class()
	{
		return $this->serializeval($this);
	}

	function serializeval($o)
	{
		
		$ar = $o->me;
		reset($ar);
		
		list($typ, $val) = each($ar);
		$rs = "<value>\n".$this->serializedata($typ, $val)."</value>\n";
		return $rs;
	}
	
	function scalarval()
	{
		reset($this->me);
		list($a,$b) = each($this->me);
		return $b;
	}


	/** -------------------------------------
	/**  Encode time in ISO-8601 form.
	/** -------------------------------------*/
	
	// Useful for sending time in XML-RPC

	function iso8601_encode($time, $utc=0)
	{	
		if ($utc == 1)
		{
			$t = strftime("%Y%m%dT%H:%M:%S", $time);
		}
		else
		{
			if (function_exists('gmstrftime'))
				$t = gmstrftime("%Y%m%dT%H:%M:%S", $time);
			else
				$t = strftime("%Y%m%dT%H:%M:%S", $time - date('Z'));
		}
		return $t;
	}
	
}
// END XML_RPC_Values Class

?>
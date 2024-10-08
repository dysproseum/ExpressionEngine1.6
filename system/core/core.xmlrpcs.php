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
 File: core.xmlrpcs.php
-----------------------------------------------------
 Purpose: XML-RPC Server Class
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

class XML_RPC_Server extends XML_RPC
{
	var $methods		= array(); 	//array of methods mapped to function names and signatures
	var $debug_msg		= '';		// Debug Message
	var $system_methods = array(); // XML RPC Server methods


	/** -------------------------------------
	/**  Constructor, more or less
	/** -------------------------------------*/

	function XML_RPC_Server($functions='', $auto=true)
	{	
		parent::XML_RPC();
		$this->set_system_methods();
	
		if (is_array($functions) && $auto)
		{
			$this->methods = $functions;
			$this->serve();
		}
	}
	
	/** -------------------------------------
	/**  Setting of System Methods
	/** -------------------------------------*/
	
	function set_system_methods ()
	{
		$system_methods = array(
		'system.listMethods' => array(
			'function' => 'this.listMethods',
			'signature' => array(array($this->xmlrpcArray, $this->xmlrpcString), array($this->xmlrpcArray)),
			'docstring' => 'Returns an array of available methods on this server'),
		'system.methodHelp' => array(
			'function' => 'this.methodHelp',
			'signature' => array(array($this->xmlrpcString, $this->xmlrpcString)),
			'docstring' => 'Returns a documentation string for the specified method'),
		'system.methodSignature' => array(
			'function' => 'this.methodSignature',
			'signature' => array(array($this->xmlrpcArray, $this->xmlrpcString)),
			'docstring' => 'Returns an array describing the return type and required parameters of a method'),
		'system.multicall' => array(
			'function' => 'this.multicall',
			'signature' => array(array($this->xmlrpcArray, $this->xmlrpcArray)),
			'docstring' => 'Combine multiple RPC calls in one request. See http://www.xmlrpc.com/discuss/msgReader$1208 for details')
		);
	}


	/** -------------------------------------
	/**  Main Server Function
	/** -------------------------------------*/
	
	function serve()
	{
		$r = $this->parseRequest();
		$payload  = '<?xml version="1.0" encoding="'.$this->xmlrpc_defencoding.'"?'.'>'."\n";
		$payload .= $this->debug_msg;
		$payload .= $r->prepare_response();
		
		header("Content-Type: text/xml");
		header("Content-Length: ".strlen($payload));
		echo $payload;
	}

	/** -------------------------------------
	/**  Add Method to Class
	/** -------------------------------------*/
	
	function add_to_map($methodname,$function,$sig,$doc)
	{
		$this->methods[$methodname] = array(
			'function'  => $function,
			'signature' => $sig,
			'docstring' => $doc
		);
	}


	/** -------------------------------------
	/**  Parse Server Request
	/** -------------------------------------*/
	
	function parseRequest($data='')
	{
		global $HTTP_RAW_POST_DATA, $IN;
		
		/** -------------------------------------
		/**  Get Data
		/** -------------------------------------*/

		if ($data == '')
		{
			//$data = $IN->clean_input_data($HTTP_RAW_POST_DATA);
			$data = $HTTP_RAW_POST_DATA;
		}


		/** -------------------------------------
		/**  Set up XML Parser
		/** -------------------------------------*/
		
		$parser = xml_parser_create($this->xmlrpc_defencoding);
		$parser_object = new XML_RPC_Message("filler");
		
		$parser_object->xh[$parser]					= array();
		$parser_object->xh[$parser]['isf']			= 0;
		$parser_object->xh[$parser]['isf_reason']	= '';
		$parser_object->xh[$parser]['params']		= array();
		$parser_object->xh[$parser]['stack']		= array();
		$parser_object->xh[$parser]['valuestack']	= array();
		$parser_object->xh[$parser]['method']		= '';

		xml_set_object($parser, $parser_object);
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
		xml_set_element_handler($parser, 'open_tag', 'closing_tag');
		xml_set_character_data_handler($parser, 'character_data');
		//xml_set_default_handler($parser, 'default_handler');
		
		
		/** -------------------------------------
		/**  PARSE + PROCESS XML DATA
		/** -------------------------------------*/
		
		if ( ! xml_parse($parser, $data, 1))
		{
			// return XML error as a faultCode
			$r = new XML_RPC_Response(0,
			$this->xmlrpcerrxml + xml_get_error_code($parser),
			sprintf('XML error: %s at line %d',
				xml_error_string(xml_get_error_code($parser)),
				xml_get_current_line_number($parser)));
			xml_parser_free($parser);
		}
		elseif($parser_object->xh[$parser]['isf'])
		{
			return new XML_RPC_Response(0,
										$this->xmlrpcerr['invalid_return'],
										$this->xmlrpcstr['invalid_retrun']);
		}
		else
		{
			xml_parser_free($parser);
			
			$m = new XML_RPC_Message($parser_object->xh[$parser]['method']);
			$plist='';
			
			for($i=0; $i < sizeof($parser_object->xh[$parser]['params']); $i++)
			{
				$plist .= "$i - " .  print_r(get_object_vars($parser_object->xh[$parser]['params'][$i]), TRUE). ";\n";
				
				$m->addParam($parser_object->xh[$parser]['params'][$i]);
			}
			
			if ($this->debug)
			{
				echo "<pre>";
				echo "---PLIST---\n" . $plist . "\n---PLIST END---\n\n";
				echo "</pre>";
			}
			
			$r = $this->execute($m);
		}
		
		/** -------------------------------------
		/**  SET DEBUGGING MESSAGE
		/** -------------------------------------*/
		
		if ($this->debug) 
		{
			$this->debug_msg = "<!-- DEBUG INFO:\n\n".$plist."\n END DEBUG-->\n";
		}
		
		return $r;
	}

	/** -------------------------------------
	/**  Executes the Method
	/** -------------------------------------*/
	
	function execute($m)
	{
		$methName = $m->method_name;
		
		// Check to see if it is a system call
		// If so, load the system_methods
		$sysCall = ereg("^system\.", $methName);
		$methods = $sysCall ? $this->system_methods : $this->methods;
		
		/** -------------------------------------
		/**  Check for Function
		/** -------------------------------------*/
		
		if (!isset($methods[$methName]['function']))
		{
			return new XML_RPC_Response(0,
				$this->xmlrpcerr['unknown_method'],
				$this->xmlrpcstr['unknown_method']);
		}
		else
		{
			// See if we are calling function in an object
			
			$method_parts = explode(".",$methods[$methName]['function']);
			$objectCall = (isset($method_parts['1']) && $method_parts['1'] != "") ? true : false;
			
			if ($objectCall && !is_callable(array($method_parts['0'],$method_parts['1'])))
			{
				return new XML_RPC_Response(0,
				$this->xmlrpcerr['unknown_method'],
				$this->xmlrpcstr['unknown_method']);
			}
			elseif (!$objectCall && !is_callable($methods[$methName]['function']))
			{
				return new XML_RPC_Response(0,
					$this->xmlrpcerr['unknown_method'],
					$this->xmlrpcstr['unknown_method']);
			}		
		}

		/** -------------------------------------
		/**  Checking Methods Signature
		/** -------------------------------------*/
		
		if (isset($methods[$methName]['signature']))
		{
			$sig = $methods[$methName]['signature'];
			for($i=0; $i<sizeof($sig); $i++)
			{
				$current_sig = $sig[$i];
		
				if (sizeof($current_sig) == sizeof($m->params)+1)
				{
					for($n=0; $n < sizeof($m->params); $n++)
					{
						$p = $m->params[$n];
						$pt = ($p->kindOf() == 'scalar') ? $p->scalartyp() : $p->kindOf();
						
						if ($pt != $current_sig[$n+1])
						{
							$pno = $n+1;
							$wanted = $current_sig[$n+1];
							
							return new XML_RPC_Response(0,
								$this->xmlrpcerr['incorrect_params'],
								$this->xmlrpcstr['incorrect_params'] . 
								": Wanted {$wanted}, got {$pt} at param {$pno})");
						}
					}
				}
			}
		}

		/** -------------------------------------
		/**  Calls the Function
		/** -------------------------------------*/

		if ($objectCall)
		{
			if ($method_parts['1'] == "this")
			{
				return call_user_func(array($this,$method_parts['0']), $m);
			}
			else
			{
				$class = new $method_parts['0'];
				return $class->$method_parts['1']($m);
				//return call_user_func(array(&$method_parts['0'],$method_parts['1']), $m);
			}
		}
		else
		{
			return call_user_func($methods[$methName]['function'], $m);
		}
	}
	
	
	/** -------------------------------------
	/**  Server Function:  List Methods
	/** -------------------------------------*/
	
	function listMethods($m)
	{
		$v = new XML_RPC_Values();
		$output = array();
		foreach($this->$methods as $key => $value)
		{
			$output[] = new XML_RPC_Values($key, 'string');
		}
		
		foreach($this->system_methods as $key => $value)
		{
			$output[]= new XML_RPC_Values($key, 'string');
		}

		$v->addArray($output);
		return new XML_RPC_Response($v);
	}
	
	/** -------------------------------------
	/**  Server Function:  Return Signature for Method
	/** -------------------------------------*/
		
	function methodSignature($m)
	{
		$methName = $m->getParam(0);
		$method_name = $methName->scalarval();
		
		$methods = ereg("^system\.", $method_name) ? $this->system_methods : $this->methods;
		
		if (isset($methods[$method_name]))
		{
			if ($methods[$method_name]['signature'])
			{
				$sigs = array();
				$signature = $methods[$method_name]['signature'];
				
				for($i=0; $i < sizeof($signature); $i++)
				{
					$cursig = array();
					$inSig = $signature[$i];
					for($j=0; $j<sizeof($inSig); $j++)
					{
						$cursig[]= new XML_RPC_Values($inSig[$j], 'string');
					}
					$sigs[]= new XML_RPC_Values($cursig, 'array');
				}
				$r = new XML_RPC_Response(new XML_RPC_Values($sigs, 'array'));
			}
			else
			{
				$r = new XML_RPC_Response(new XML_RPC_Values('undef', 'string'));
			}
		}
		else
		{
			$r = new XML_RPC_Response(0,$this->xmlrpcerr['introspect_unknown'], $this->xmlrpcstr['introspect_unknown']);
		}
		return $r;
	}
	
	/** -------------------------------------
	/**  Server Function:  Doc String for Method
	/** -------------------------------------*/
	
	function methodHelp($m)
	{
		$methName = $m->getParam(0);
		$method_name = $methName->scalarval();
		
		$methods = ereg("^system\.", $method_name) ? $this->system_methods : $this->methods;
	
		if (isset($methods[$methName]))
		{
			$docstring = isset($methods[$method_name]['docstring']) ? $methods[$method_name]['docstring'] : '';
			$r = new XML_RPC_Response(new XML_RPC_Values($docstring, 'string'));
		}
		else
		{
			$r = new XML_RPC_Response(0, $this->xmlrpcerr['introspect_unknown'], $this->xmlrpcstr['introspect_unknown']);
		}
		return $r;
	}

	/** -------------------------------------
	/**  Server Function:  Multi-call
	/** -------------------------------------*/

	function multicall($m)
	{
		$calls = $m->getParam(0);
		list($a,$b)=each($calls->me);
		$result = array();

		for ($i = 0; $i < sizeof($b); $i++)
		{
			$call = $calls->me['array'][$i];
			$result[$i] = $this->do_multicall($call);
		}

		return new XML_RPC_Response(new XML_RPC_Values($result, 'array'));
	}
	
	
	/** -------------------------------------
	/**  Multi-call Function:  Error Handling
	/** -------------------------------------*/

	function multicall_error($err)
	{
		$str  = is_string($err) ? $this->xmlrpcstr["multicall_${err}"] : $err->faultString();
		$code = is_string($err) ? $this->xmlrpcerr["multicall_${err}"] : $err->faultCode();
		
		$struct['faultCode'] = new XML_RPC_Values($code, 'int');
		$struct['faultString'] = new XML_RPC_Values($str, 'string');
	
		return new XML_RPC_Values($struct, 'struct');
	}
	
	
	/** -------------------------------------
	/**  Multi-call Function:  Processes method
	/** -------------------------------------*/
	
	function do_multicall($call)
	{
		if ($call->kindOf() != 'struct')
			return $this->multicall_error('notstruct');
		elseif (!$methName = $call->me['struct']['methodName'])
			return $this->multicall_error('nomethod');
		
		list($scalar_type,$scalar_value)=each($methName->me);
		$scalar_type = $scalar_type == $this->xmlrpcI4 ? $this->xmlrpcInt : $scalar_type;
			
		if ($methName->kindOf() != 'scalar' || $scalar_type != 'string') 
			return $this->multicall_error('notstring');
		elseif ($scalar_value == 'system.multicall')
			return $this->multicall_error('recursion');
		elseif (!$params = $call->me['struct']['params'])
			return $this->multicall_error('noparams');
		elseif ($params->kindOf() != 'array')
			return $this->multicall_error('notarray');
			
		list($a,$b)=each($params->me);
		$numParams = sizeof($b);

		$msg = new XML_RPC_Message($scalar_value);
		for ($i = 0; $i < $numParams; $i++)
		{
			$msg->params[] = $params->me['array'][$i];
		}

		$result = $this->execute($msg);

		if ($result->faultCode() != 0)
		{
			return $this->multicall_error($result);
		}

		return new XML_RPC_Values(array($result->value()), 'array');
	}	
	
}
// END XML_RPC_Server class
?>
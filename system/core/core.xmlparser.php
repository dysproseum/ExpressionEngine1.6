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
 File: core.xmlparser.php
-----------------------------------------------------
 Purpose: XML parsing functions
=====================================================
*/

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

/* -------------------------------------
/*  XML_Cache class - holds parsed XML object
/* -------------------------------------*/

class XML_Cache {
	var $tag;
	var $attributes;
	var $value;
	var $children;
}

/* -------------------------------------
/*  XMLparser class contains all of the
/*  methods for handling XML data
/* -------------------------------------*/

class EE_XMLparser {
	
	var $tagdata;
	var $index;
	var $errors		= array();
	var $encoding 	= '';		// 'ISO-8859-1', 'UTF-8', or 'US-ASCII' - empty string should auto-detect
	
	/** -------------------------------------
	/**  Parse the XML data into an array
	/** -------------------------------------*/
	
	function parse_xml($xml)
	{
		// PHP's XML array structures stink so make our own
		if ($this->parse_into_struct($xml) === FALSE)
		{
			error_log('Unable to parse XML data');
			return FALSE;
		}
		else
		{
			$elements = array();
			$child = array();
			foreach ($this->tagdata as $item)
			{
				$current = count($elements);

				if ($item['type'] == 'open' OR $item['type'] == 'complete')
				{
					$elements[$current] = new XML_Cache;
					$elements[$current]->tag		= $item['tag'];
					$elements[$current]->attributes	= (array_key_exists('attributes', $item)) ? $item['attributes'] : '';
					$elements[$current]->value		= (array_key_exists('value', $item)) ? $item['value'] : '';
					
					/** -------------------------------------
					/**  Create a new child layer for 'open'
					/** -------------------------------------*/

					if ($item['type'] == "open")
					{
						$elements[$current]->children = array();
						$child[count($child)] = &$elements;
						$elements = &$elements[$current]->children;
					}
				}
				
				/** -------------------------------------
				/**  Put child layer into root object
				/** -------------------------------------*/
				
				elseif ($item['type'] == 'close')
				{
					$elements = &$child[count($child) - 1];
					unset($child[count($child) - 1]);
				}
			}	
		}
		return $elements[0];
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Deprecated function for converting delimited data to XML
	/** -------------------------------------*/
	
	function data2xml($data, $structure, $root = "root", $element = "element", $delimiter = "\t", $enclosure = '')
	{	
		if ( ! is_string($data) OR ! is_array($structure) OR count($structure) == 0)
		{
			$this->errors[] = "Data or structure improperly defined";
			return FALSE;
		}
		
		$params = array(
							'data'			=> $data,
							'structure'		=> $structure,
							'root'			=> $root,
							'element'		=> $element,
							'delimiter'		=> $delimiter,
							'enclosure'		=> $enclosure
						);
		
		return $this->delimited_to_xml($params);
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Convert delimited text to XML
	/** -------------------------------------*/
	
	function delimited_to_xml($params)
	{
		global $REGX;
		
		if ( ! is_array($params))
		{
			return FALSE;
		}
		
		$defaults = array (
							'data'			=>	NULL,
							'structure'		=>	array(),
							'root'			=>	'root',
							'element'		=>	'element',
							'delimiter'		=>	"\t",
							'enclosure'		=>	''
							);
		
		foreach ($defaults as $key => $val)
		{
			if ( ! isset($params[$key]))
			{
				$params[$key] = $val;
			}
		}
		
		extract($params);
		
		/*
		  $data 		- string containing delimited data
		  $structure 	- array providing a key for $data elements
		  $root			- the root XML document tag name
		  $element		- the tag name for the element used to enclose the tag data
		  $delimiter	- the character delimiting the text, default is \t (tab)
		  $enclosure	- character used to enclose the data, such as " in the case of $data = '"item", "item2", "item3"';
		*/
		
		if ($data === NULL OR ! is_array($structure) OR count($structure) == 0)
		{
			return FALSE;
		}
		
		/** -------------------------------------
		/**  Convert delimited text to array
		/** -------------------------------------*/

		$data_arr 	= array();
		$data 		= preg_replace("/(\015\012)|(\015)|(\012)/", "\n", $data); 
		$lines		= explode("\n", $data);
		
		if (empty($lines))
		{
			$this->errors[] = "No data to work with";
			return FALSE;
		}
		
		if ($enclosure == '')
		{
			foreach ($lines as $key => $val)
			{
				if ( ! empty($val))
					$data_arr[$key] = explode($delimiter, $val);
			}			
		}
		else  // values are enclosed by a character, e.g.: "value","value2","value3"
		{
			foreach ($lines as $key => $val)
			{
				if ( ! empty($val))
				{
					preg_match_all("/".preg_quote($enclosure)."(.*?)".preg_quote($enclosure)."/si", $val, $matches);
					$data_arr[$key] = $matches[1];
					
					if (empty($data_arr[$key]))
					{
						$this->errors[] = 'Structure mismatch, skipping line: '.$val;
						unset($data_arr[$key]);
					}
				}
			}
		}

		/** -------------------------------------
		/**  Construct the XML
		/** -------------------------------------*/
		
		$xml = "<{$root}>\n";
		
		foreach ($data_arr as $datum)
		{
			if ( ! empty($datum) AND count($datum) == count($structure))
			{
				$xml .= "\t<{$element}>\n";
		
				foreach ($datum as $key => $val)
				{
					$xml .= "\t\t<{$structure[$key]}>".$REGX->xml_convert($val)."</{$structure[$key]}>\n";
				}
		
				$xml .= "\t</{$element}>\n";				
			}
			else
			{
				$details = '';
				
				foreach ($datum as $val)
				{
					$details .= "{$val}, ";
				}
		
				$this->errors[] = 'Line does not match structure: '.substr($details, 0, -2);
			}
		}
		
		$xml .= "</{$root}>\n";  
		
		if ( ! stristr($xml, "<{$element}>"))
		{
			$this->errors[] = "No valid elements to build XML";
			return FALSE;
		}
		
		return $xml;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Parse XML into PHP's array structures
	/** -------------------------------------*/
	
	function parse_into_struct($xml, $case = FALSE)
	{
		// use an empty string to trick PHP into doing what it's supposed to do and auto-detect the encoding
		$parser = ($this->encoding == '') ? xml_parser_create('') : xml_parser_create($this->encoding);
		if ($case === FALSE)
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		
		$entities = $this->fetch_entity_definitions($xml);
		$xml = ($entities === FALSE) ? $xml : $this->replace_entities($xml, $entities);

		if (xml_parse_into_struct($parser, $xml, $this->tagdata, $this->index) === 0)
		{
			xml_parser_free($parser);
			return FALSE;
		}
		
		xml_parser_free($parser);
		return TRUE;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Read XML DTD entity definitions
	/** -------------------------------------*/
	
	function fetch_entity_definitions($xml)
	{
		$entities = array();
		
		preg_match_all("/\<\!ENTITY\s*([\w-]+)\s*\"(.+)\"/siU", $xml, $matches);

		if (isset($matches[0][0]))
		{
			$entities[0] = $matches[1];
			$entities[1] = $matches[2];
			return $entities;
		}
		
		return FALSE;
	}
	/* END */
	
	
	/** -------------------------------------
	/**  Replace DTD entities in XML
	/** -------------------------------------*/
	
	function replace_entities($xml, $entities)
	{	
		foreach ($entities[0] as $key => $val)
		{
			$xml = str_replace('&'.$entities[0][$key].';', $entities[1][$key], $xml);
		}

		return $xml;
	}
	/* END */
	
}
/* END */

?>
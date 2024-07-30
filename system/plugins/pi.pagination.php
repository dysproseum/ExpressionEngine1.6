<?php

$plugin_info = array(
  'pi_name' => 'Simple Pagination',
  'pi_version' => '1.1',
  'pi_author' => 'Dom Stubbs',
  'pi_author_url' => 'http://www.vayadesign.net',
  'pi_description' => 'A simple means of paginating weblog entries without using multiple fields.',
  'pi_usage' => Pagination::usage()
);

class Pagination
{

var $return_data = "";
  
  function Pagination()
  {
	global $TMPL, $SESS, $FNS;
	$page = '';

	// Search for {pagination} tags in case the user wants prev / next page links
	$pagination_regex = '!{simplepaginate}(.*){&#47;simplepaginate}!Usi';
	$pagination_enabled = preg_match_all($pagination_regex, $TMPL->tagdata, $pagination_content, PREG_PATTERN_ORDER);
	if($pagination_enabled == 0) {
		// The user has added no {pagination} tags so there's no need to do anything here.
	}
	else {
		// Remove {pagination} tags from the markup
		$TMPL->tagdata = preg_replace($pagination_regex, '', $TMPL->tagdata);
	}
	
	// Create an array of the pages
	$data = explode('{pagebreak}', $TMPL->tagdata);
	$number_pages = count($data);
	
	// No pagebreaks? Return the data as-is
	if($number_pages == 1) {
		$this->return_data = $data[0];
		return;
	}
	
	// Determine which page to display based on current url
	
	// Start by determining the last segment of the url
	$trackerdata = trim($SESS->tracker['0'], '/'); // remove beginning and trailing slashes
	$location = explode('/', $trackerdata);
	$url_segments = count($location);
	$final_segment = $location[$url_segments - 1]; // determine the last segment of the url
	
	// Does the last segment refer to a specific page?
	if( preg_match('/^[P][0-9]+$/i', $final_segment) ) {
		// We need a specific page - which?
		$selected_page = substr($final_segment, 1);
		if( array_key_exists($selected_page - 1, $data) ) {
			// EE confuses pagebreaks with paragraphs, so some unwanted p tags may need to go		
			$selected_page_data = $this->PageTidy($data[$selected_page - 1]);
			$page .= $selected_page_data;
		}
		else {
			$this->return_data = "<strong>Error: This page does not appear to exist.</strong>\n";
			return;
		}
		// Determine the base URL of the overall document
		$pagenav_url = '';
		foreach($location as $key => $val) {
			if($val != $final_segment) {
				$pagenav_url .= $val . "/";
			}
		}
		$document_root = $FNS->create_url( "/".$pagenav_url );		
		// What would the next and previous page's URL be?	
		$nextpagenum = $selected_page + 1;
		$prevpagenum = $selected_page - 1;
		$nextpage = $FNS->create_url( "/".$pagenav_url."P".$nextpagenum."/" );
		if($prevpagenum > 1) { // other pages need the number appended
			$prevpage = $document_root."P".$prevpagenum."/";
		}
		else { // no need for 'p1' to be appended to the url
			$prevpage = $document_root;
		}
	}
	else {
		// No page requested in the url; print page 1
		$selected_page = 1;
		$page .= $this->PageTidy($data[0]);
		// What would the next and previous page's URL be?
		$document_root = $FNS->create_url($SESS->tracker['0']);
		$nextpage = $document_root."P2/";
		$prevpage = '';
	}
	
	// Print pagination links if requested
	if($pagination_enabled) {
		$pagination_data = '';
		$pagination_links = '';
		foreach($pagination_content[1] as $key => $val) {
			// Replace variables 
			$total_pages = count($data);
			$val = str_replace('{current_page}', $selected_page, $val);
			$val = str_replace('{total_pages}', $total_pages, $val);
			$val = str_replace('{previous_auto_path}', $prevpage, $val);
			$val = str_replace('{next_auto_path}', $nextpage, $val);
			// Do we need to generate complex pagination links? (i.e. « First < 11 12 13 14 15 > Last »)
			// (Replace this with a single function in the future)
			if(strpos($val, '{pagination_links}')) {
				if($selected_page > 1 AND $total_pages > 3) { // Show first page link
					$pagination_links .= "<a href=\"".$document_root."\">&laquo; First</a>\n";
				}
				$previous_page_num = $selected_page - 1;
				if( $previous_page_num > 0 ) { // Previous page link
					$pagination_links .= "<a href=\"".$document_root."P".$previous_page_num."/\">&lt;</a>\n";
				}
				$two_pages_back_num = $selected_page - 2;
				if( $two_pages_back_num > 0 ) { // Selected page - 2 link
					$pagination_links .= "<a href=\"".$document_root."P".$two_pages_back_num."/\">".$two_pages_back_num."</a>\n";
				}
				if( $previous_page_num > 0 ) { // Selected page - 1 link
					$pagination_links .= "<a href=\"".$document_root."P".$previous_page_num."/\">".$previous_page_num."</a>\n";
				}
				// Current page
				$pagination_links .= "<strong>".$selected_page."</strong>\n";				
				$next_page_num = $selected_page + 1;
				if( $next_page_num <= $total_pages ) { // Selected page + 1 link
					$pagination_links .= "<a href=\"".$document_root."P".$next_page_num."/\">".$next_page_num."</a>\n";
				}
				$two_pages_forward_num = $selected_page + 2;
				if( $two_pages_forward_num <= $total_pages ) { // Selected page + 2 link
					$pagination_links .= "<a href=\"".$document_root."P".$two_pages_forward_num."/\">".$two_pages_forward_num."</a>\n";
				}
				if( $next_page_num <= $total_pages ) { // Next page link
					$pagination_links .= "<a href=\"".$document_root."P".$next_page_num."/\">&gt;</a>\n";
				}
				if($selected_page < $total_pages AND $total_pages > 3) { // Final page link
					$pagination_links .= "<a href=\"".$document_root."P".$total_pages."/\">Last &raquo;</a>\n";
				}
				$val = str_replace('{pagination_links}', $pagination_links, $val);				
			}		
			if($selected_page < count($data)) {
				// the next page link should appear if the markup is there
				$val = preg_replace('!{if next_page}(.*){&#47;if}!Usi', '\\1', $val);
			}
			if($selected_page > 1) {
				// show the previous page link
				$val = preg_replace('!{if previous_page}(.*){&#47;if}!Usi', '\\1', $val);				
			}
			$pagination_data .= $val;
		}
		// Place the pagination links in the markup
		switch( $TMPL->fetch_param('paginate') ) {
			case '': // defaults to bottom
			case 'bottom':
				$page = $page . $val;
				break;
			case 'top':
				$page = $val . $page;
				break;
			case 'both':
				$page = $val . $page . $val;
		}		
	}
		
	$this->return_data = $page;
  }
  
  // An internal function which removes unwanted paragraph tags from pages
  function PageTidy($data) {
	$data = trim($data);
	if( substr($data, -3, 3) == '<p>' ) { // remove trailing <p> tag
		$data = substr($data, 0, strlen($data) - 3);
	}
	if( substr($data, 0, 4) == '</p>' ) { // remove </p> prefix
		$data = substr($data, 4, strlen($data));
	}
	return $data;
  }

  // ----------------------------------------
  //  Plugin Usage
  // ----------------------------------------

  function usage()
  {
  ob_start(); 
  ?>
This plugin makes it possible to break up weblog entries into multiple pages by using the {pagebreak} tag. Entries can be divided into as many or few pages as necessary. To implement the plugin into your templates see the usage info below.

Usage example with next and previous links:

{exp:pagination}
{body}
{simplepaginate}

<div class="pagination"><p class="status">Page {current_page} of {total_pages}</p>

{if previous_page}
<p class="prev_page">&laquo; <a href="{previous_auto_path}">Previous Page</a></p>
{/if}

{if next_page}
<p class="next_page"><a href="{next_auto_path}">Next Page</a> &raquo;</p>
{/if}

</div>

{/simplepaginate}
{/exp:pagination}

-------------------------------------------------

Usage example with automatically generated {pagination_links}:

{exp:pagination}
  {body}
  {simplepaginate}
    <p>Page {current_page} of {total_pages} pages {pagination_links}</p>
  {/simplepaginate}
{/exp:pagination}

-------------------------------------------------

You can select where in your entry the pagination links appear with the 'paginate parameter. Available settings are:

{exp:pagination paginate="bottom"}
{exp:pagination paginate="top"}
{exp:pagination paginate="both"}

If you don't set a value then the links will appear at the bottom by default.

-------------------------------------------------

The features are almost an exact replica of those used by EE however some of the variable names do vary slightly to avoid conflicts. For additional info see the pagination details in the EE manual:

http://expressionengine.com/docs/modules/weblog/pagination_page.html
<?php
  $buffer = ob_get_contents();
	
  ob_end_clean(); 

  return $buffer;
  }
  // END

}

?>
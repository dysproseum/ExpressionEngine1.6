<?php

//---------------------------------
//  plugin to show a video player
//
//  embeds a flash player using
//  vids hosted on brightcove
//---------------------------------

$plugin_info = array(
	'pi_name' => 'Video Player',
	'pi_version' => '1.0',
	'pi_author' => 'MindComet',
	'pi_author_url' => 'http://mindcomet.com/',
	'pi_description' => 'Embeds a Brightcove video player for SPEEDtv.com',
	'pi_usage' => Brightcove_video_player::usage()
	);

class Brightcove_video_player {

	var $small_player_id = 340430050;
	var $large_player_id = 340473122;

       function usage()
	{
		ob_start();
		?>
The Video player shows a flash player on the page.

{exp:video_player:small}
{exp:video_player:large}

		<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

	function large()
	{
		ob_start();
		?>

<!-- Start Brightcove Player -->
<script src="http://admin.brightcove.com/js/experience_util.js" type="text/javascript"></script>
<script type="text/javascript">
	// By use of this code snippet, I agree to the Brightcove Publisher T and C 
	// found at http://www.brightcove.com/publishertermsandconditions.html. 

	var config = new Array();

	/* 
	* feel free to edit these configurations
	* to modify the player experience
	*/
	config["videoId"] = null; //the default video loaded into the player
	config["videoRef"] = null; //the default video loaded into the player by ref id specified in console
	config["lineupId"] = null; //the default lineup loaded into the player
	config["playerTag"] = null; //player tag used for identifying this page in brightcove reporting
	config["autoStart"] = false; //tells the player to start playing video on load
	config["preloadBackColor"] = "#FFFFFF"; //background color while loading the player

	/* do not edit these config items */
	config["playerId"] = <?php echo $this->large_player_id; ?>;
	config["width"] = 988;
	config["height"] = 550;
	config["adServerURL"] = "http://ad.doubleclick.net/pfadx/speedtv.tmus/";

	createExperience(config, 8);
</script>
<!-- End of Brightcove Player -->

		<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

	function small()
	{
		ob_start();
		?>

<!-- Start Brightcove Player -->
<script src="http://speedtv.com/_assets/js/renderSWF.js" type="text/javascript"></script>
<script type="text/javascript">
	AC_FL_RunContent("width","371","height","262","allowScriptAccess","always","movie","http://speedtv.com/_assets/swf/homepage_videoplayer3?playerId=<?php echo $this->small_player_id; ?>","menu","false","quality","high","bgcolor","#cccccc","id","homepage_videoplayer3","name","homepage_videoplayer3","codebase","http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0","pluginspage","http://www.macromedia.com/go/getflashplayer","type","application/x-shockwave-flash","classid","clsid:d27cdb6e-ae6d-11cf-96b8-444553540000","src","http://speedtv.com/_assets/swf/homepage_videoplayer3?playerId=<?php echo $this->small_player_id; ?>");
</script>

	<script type="text/javascript">RenderSWF('<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" class="bcPlayerObj" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="371" height="262" id="homepage_videoplayer3" align="middle">'+
	'<param name="allowscriptaccess" value="always" />'+
	'<param name="movie" value="http://speedtv.com/_assets/swf/homepage_videoplayer3.swf?playerId=<?php echo $this->small_player_id; ?>" />'+
	'<param name="menu" value="false" />'+
	'<param name="wmode" value="transparent">'+
	'<param name="quality" value="high" />'+
	'<param name="bgcolor" value="#cccccc" />'+
	'<embed src="http://speedtv.com/_assets/swf/homepage_videoplayer3.swf?playerId=<?php echo $this->small_player_id; ?>" menu="false" wmode="transparent" quality="high" bgcolor="#cccccc" width="371" height="262" name="homepage_videoplayer3" align="middle" allowscriptaccess="always" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />'+
	'</object>');
</script>

<noscript>
	<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" class="bcPlayerObj" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="371" height="262" id="homepage_videoplayer3" align="middle">
		<param name="allowscriptaccess" value="always" />
		<param name="movie" value="http://speedtv.com/_assets/swf/homepage_videoplayer3.swf?playerId=<?php echo $this->small_player_id; ?>" />
		<param name="menu" value="false" />
		<param name="wmode" value="transparent">
		<param name="quality" value="high" />
		<param name="bgcolor" value="#cccccc" />
		<embed src="http://speedtv.com/_assets/swf/homepage_videoplayer3.swf?playerId=<?php echo $this->small_player_id; ?>" menu="false" wmode="transparent" quality="high" bgcolor="#cccccc" width="371" height="262" name="homepage_videoplayer3" align="middle" allowscriptaccess="always" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
	</object>
</noscript>
<!-- End of Brightcove Player -->

		<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;

	}

} //END CLASS

?>
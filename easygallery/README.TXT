
INSTALLATION OF EASY GALLERY: (tested on Build: 20070708)

The package is setup in a way where the files could just be copied to your website's document root and skip to step #7, other wise just follow the steps below.

1) Copy "images/easy_gallery/" directory to "images/" directory

2) Copy "jquery/" directory to the your document root

3) Copy "system/extensions/ext.easy_gallery.php" to your "system/extensions/" directory

4) Copy "system/modules/easy_gallery" to your "system/modules" directory

5) Copy "system/language/english" to your "system/language" directory

6) CHMOD "images/easy_gallery/" to 777

7) HOOK HACK: Edit File "system/cp/cp.publish.php" add the code below (line 333) 

ADD LINE
	# Enable $entry_id (not sure if this is a bug but $entry_id hook is always empty)
	$entry_id = $IN->GBL('entry_id');

AFTER
        /** ----------------------------------------------
        /**  Security check
        /** ---------------------------------------------*/
       
	if ( ! in_array($weblog_id, $assigned_weblogs))
        {
            return $DSP->no_access_message($LANG->line('unauthorized_for_this_blog'));
        }

8) Configure the "jquery/easy_config.php", add your database settings of the EE installation. (this is for uploading images via flash)  I tried to use the module functions but apparently I can't send the session variable properly from FLASH to the Easy Gallery Module.  Spent a day and gave up.

9) Use the "upload_test.html" to test the "jquery/upload.php" script if its fully functioning.  ("jquery/upload.php" is most difficult part to get working that's what I created it in a way where this "upload_test.html" will report to you the problems encountered in running "jquery/upload.php" by editing the "jquery/config.inc.php")

10) Enable the "Easy Gallery Extension" on the "Admin > Utilities > Extension" and the "Easy Gallery Module" on the "Modules > Easy Gallery"

11) Configure the "Files URL", "Files Path", "jQuery URL" and "System URL" and any other settings of the Easy Gallery by going to "Modules > Easy Gallery > Settings"


FEEDBACK, COMMENTS AND BUGS REPORTING:

If you have any comments, feedback, or bugs please post them at the EE Discussion Forum on the Easy Gallery Thread: http://expressionengine.com/forums/viewthread/58393/


FAST SAMPLE TEST CODE:

to check if your installation is fully working on the template side.  Place the code below on one of the templates.  I usually place this code near the {body} tag on the "index.php template

#############################

{exp:easy_gallery:images cover="Yes" entry_id="{entry_id}"}
<br />
<img src="{url_image_large}">
<br />
<strong>filename:</strong> {filename}<br />
<strong>keywords:</strong>  {keywords}<br />
<strong>caption:</strong>  {caption}<br />
<strong>date_stamp:</strong>  {date_stamp}<br />
<strong>date_modified:</strong>  {date_modified}<br />
<br />
<strong>url_image_small:</strong>  {url_image_small}<br />
<strong>url_image_medium:</strong>  {url_image_medium}<br />
<strong>url_image_large:</strong>  {url_image_large}<br />
<br />
{/exp:easy_gallery:images}

###############################

{exp:easy_gallery:images entry_id="{entry_id}" paginate="Yes" limit="3" page="{segment_3}"}
<div style="float:left; padding-left:5px; padding-bottom:5px; text-align:center; width:125px; height:125px;">
<a href="{url_image_large}" target="_blank"><img border="0" src="{url_image_small}"></a><br />
<strong>{filename}</strong>
</div>
{easy_paginate}
<div style="clear:both; height:1px;"></div>
{if page_previous}<a href="/index.php/site/index/P{page_previous}">Previous</a> - {/if} Total Pages: {page_total} {if page_next} - <a href="/index.php/site/index/P{page_next}">Next</a><br />{/if}
{/easy_paginate}
{/exp:easy_gallery:images}

###############################


BUGS:

* Currently will clash with other Extensions that use Publish / Edit "TABS".
* Keywords are currently not being used
* No way to show the "Caption" when Editing Images


TROUBLESHOOTING

1) If you receive a Error Code: HTTP Error, Message: "404" dialog box then follow the directions below

This is related to the mod_security bug:

If you are using Apache with mod_security this will not work, you need to put the following in your .htaccess file to disable mod_security:

SecFilterEngine Off
SecFilterScanPOST Off 

2) If the Gallery Tab generates a blank page then try disabling TinyMCE Extension and try chcking the Gallery Tab again.  The TinyMCE extension unfotunately is missing the code that avoids clashing with other extensions that use the same extension hooks.


THANK YOU:

Please make a donation if you find this useful at http://www.laxa.ca/ee_home.html and click on the "Make Donation" link on the very bottom.  I will make sure that this script will be fully featured, useful and easy to use but ofcourse that takes time and some motivation. :D

Thanks for the patience all the feedback.
  wiL.Laxa (Laxa Inc.)
  http://www.laxa.ca/ee_home.html
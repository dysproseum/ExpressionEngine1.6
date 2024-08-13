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
 File: ud_php.php
-----------------------------------------------------
 Purpose: Perform database updates for PHP7/MySQL 5.7
=====================================================
*/


if ( ! defined('EXT'))
{
    exit('Invalid file request');
}


class Updater {


	function do_update()
	{
		global $DB, $UD;
		
		$defaults = array(
			'exp_sites' => array(
				"`site_description` text NOT NULL default ''",
				"`site_system_preferences` TEXT NOT NULL default ''",
				"`site_mailinglist_preferences` TEXT NOT NULL default ''",
				"`site_member_preferences` TEXT NOT NULL default ''",
				"`site_template_preferences` TEXT NOT NULL default ''",
				"`site_weblog_preferences` TEXT NOT NULL default ''",
			),
			'exp_members' => array(
				"authcode varchar(10) NOT NULL DEFAULT ''",
				"url varchar(75) NOT NULL DEFAULT ''",
				"location varchar(50) NOT NULL DEFAULT ''",
				"occupation varchar(80) NOT NULL DEFAULT ''",
				"interests varchar(120) NOT NULL DEFAULT ''",
				"bday_d int(2) NOT NULL DEFAULT 1",
				"bday_m int(2) NOT NULL DEFAULT 1",
				"bday_y int(4) NOT NULL DEFAULT 1970",
				"aol_im varchar(50) NOT NULL DEFAULT ''",
				"yahoo_im varchar(50) NOT NULL DEFAULT ''",
				"msn_im varchar(50) NOT NULL DEFAULT ''",
				"icq varchar(50) NOT NULL DEFAULT ''",
				"bio text NOT NULL DEFAULT ''",
				"signature text NOT NULL DEFAULT ''",
				"avatar_filename varchar(120) NOT NULL DEFAULT ''",
				"avatar_width int(4) unsigned NOT NULL DEFAULT 32",
				"avatar_height int(4) unsigned NOT NULL DEFAULT 32",
				"photo_filename varchar(120) NOT NULL DEFAULT ''",
				"photo_width int(4) unsigned NOT NULL DEFAULT 320",
				"photo_height int(4) unsigned NOT NULL DEFAULT 320",
				"sig_img_filename varchar(120) NOT NULL DEFAULT ''",
				"sig_img_width int(4) unsigned NOT NULL DEFAULT 32",
				"sig_img_height int(4) unsigned NOT NULL DEFAULT 32",
				"ignore_list text NOT NULL DEFAULT ''",
				"language varchar(50) NOT NULL default ''",
				"timezone varchar(8) NOT NULL default ''",
				"cp_theme varchar(32) NOT NULL DEFAULT ''",
				"profile_theme varchar(32) NOT NULL DEFAULT ''",
				"forum_theme varchar(32) NOT NULL DEFAULT ''",
				"tracker text NOT NULL DEFAULT ''",
				"notepad text NOT NULL DEFAULT ''",
				"quick_links text NOT NULL DEFAULT ''",
				"quick_tabs text NOT NULL DEFAULT ''",
			),
			'exp_weblogs' => array(
				"blog_description varchar(225) NOT NULL default ''",
				"deft_category varchar(60) NOT NULL default ''",
				"weblog_max_chars int(5) unsigned NOT NULL default 3",
				"weblog_notify_emails varchar(255) NOT NULL default ''",
				"comment_notify_emails varchar(255) NOT NULL default ''",
				"rss_url varchar(80) NOT NULL default ''",
				"default_entry_title varchar(100) NOT NULL default ''",
				"url_title_prefix varchar(80) NOT NULL default ''",
			),
			'exp_weblog_titles' => array(
				"forum_topic_id int(10) unsigned NOT NULL DEFAULT 0",
				"edit_date bigint(6)",
				"recent_comment_date int(10) NOT NULL default '0'",
				"sent_trackbacks text NOT NULL default ''",
				"recent_trackback_date int(10) NOT NULL default '0'",
			),
			'exp_field_groups' => array(
				"field_instructions TEXT NOT NULL default ''",
				"field_pre_blog_id int(6) unsigned NOT NULL default 3",
				"field_pre_field_id int(6) unsigned NOT NULL default 3",
				"field_related_id int(6) unsigned NOT NULL default 3",
				"field_related_max smallint(4) NOT NULL default 3",
				"field_maxl smallint(3) NOT NULL default 3",
			'exp_weblog_data' => array(
				"field_id_1 text NOT NULL default ''",
				"field_id_2 text NOT NULL default ''",
				"field_id_3 text NOT NULL default ''",
				"field_id_4 int(10) not null default 0",
				"field_dt_4 varchar(8) not null default ''",
				"field_id_5 text not null default ''",
				"field_id_6 text not null default ''",
			),
			'exp_comments' => array(
				"edit_date timestamp(6)",
			),
			'exp_category_groups' => array(
				"`can_edit_categories` TEXT NOT NULL DEFAULT 'n'",
				"`can_delete_categories` TEXT NOT NULL DEFAULT 'n'",
			),
			'exp_categories' => array(
				"cat_description text NOT NULL DEFAULT ''",
				"cat_image varchar(120) NOT NULL DEFAULT ''",
				"cat_order int(4) unsigned NOT NULL DEFAULT 3",
			),
			'exp_templates' => array(
				"template_data mediumtext NOT NULL DEFAULT ''",
				"template_notes text NOT NULL DEFAULT ''",
				"edit_date int(6) NOT NULL DEFAULT 0",
				"refresh int(6) unsigned NOT NULL DEFAULT 0",
				"no_auth_bounce varchar(50) NOT NULL DEFAULT ''",
				"hits int(10) unsigned NOT NULL DEFAULT 0",
			),
			'exp_upload_prefs' => array(
				"max_size varchar(16) NOT NULL default 0",
				"max_height varchar(6) NOT NULL default 0",
				"max_width varchar(6) NOT NULL default 0",
				"properties varchar(120) NOT NULL default ''",
				"pre_format varchar(120) NOT NULL default ''",
				"post_format varchar(120) NOT NULL default ''",
				"file_properties varchar(120) NOT NULL default ''",
				"file_pre_format varchar(120) NOT NULL default ''",
				"file_post_format varchar(120) NOT NULL default ''",
			),
			'exp_relationships' => array(
				"reverse_rel_data mediumtext not null default ''",
			),
		);
	
		// Define the table changes
		
		foreach ($defaults as $table => $column) {
			$Q[] = "ALTER TABLE $table MODIFY COLUMN $column";
		}
		
		// Run the queries
		
		foreach ($Q as $sql)
		{
			$DB->query($sql);
		}
			
		return TRUE;

	}
	
}	
// END CLASS

<?php
/*
 * Plug-in Name: QuickMerge for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.wildcardsworld.com
 *
 * this file contains data used by classes/installer.php
 */

$tables = array
(
	"quick_merge_threads" => array
	(
		"id" => 'INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY',
		"tid" => 'INT(10) NOT NULL',
		"title" => 'TEXT',
		"display_order" => 'INT(10) NOT NULL',
		"dateline" => 'INT(10)'
	),
	"quick_merge_cache" => array
	(
		"id" => 'INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY',
		"cache_data" => 'TEXT',
		"dateline" => 'INT(10)'
	)
);

$settings = array
(
	"quick_merge_settings" => array
	(
		"group" => array
		(
			"name" => "quick_merge_settings",
			"title" => $lang->quick_merge,
			"description" => $lang->quick_merge_setting_group_desc,
			"disporder" => "101",
			"isdefault" => "no"
		),
		"settings" => array
		(
			"quick_merge_groups" => array
			(
				"sid" => "NULL",
				"name" => 'quick_merge_groups',
				"title" => $lang->quick_merge_groups_title,
				"description" => $lang->quick_merge_groups_desc,
				"optionscode" => 'text',
				"value" => '4',
				"disporder" => '10'
			),
			"quick_merge_max_replies" => array
			(
				"sid" => "NULL",
				"name" => 'quick_merge_max_replies',
				"title" => $lang->quick_merge_max_replies_title,
				"description" => $lang->quick_merge_max_replies_desc,
				"optionscode" => 'text',
				"value" => '4',
				"disporder" => '20'
			),
			"quick_merge_title_length" => array
			(
				"sid" => "NULL",
				"name" => 'quick_merge_title_length',
				"title" => $lang->quick_merge_title_length_title,
				"description" => $lang->quick_merge_title_length_desc,
				"optionscode" => 'text',
				"value" => '32',
				"disporder" => '30'
			)
		)
	)
);

$templates = array
(
	"qm_form" => <<<EOF

<form style="float: left; margin-left: 20px; padding: 10px;" action="moderation.php" method="post">
<select name="quick_merge_dest">
	<option>{\$lang->quick_merge_action_text}</option>
{\$options}
</select>
<input name="action" type="hidden" value="quick_merge"/>
<input name="quick_merge_tid" type="hidden" value="{\$thread['tid']}"/>
</form>
EOF
	,
	"qm_thread_row" => <<<EOF
<option value="{\$tid}" onclick="this.up('form').submit(); return true;">{\$title}</option>

EOF
);

?>

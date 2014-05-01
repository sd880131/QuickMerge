<?php
/*
 * Plug-in Name: QuickMerge for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.wildcardsworld.com
 *
 * the main plug-in file; splits forum and ACP scripts to decrease footprint
 */

// disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

require_once MYBB_ROOT . "inc/plugins/quick_merge/classes/malleable.php";
require_once MYBB_ROOT . "inc/plugins/quick_merge/classes/storable.php";
require_once MYBB_ROOT . "inc/plugins/quick_merge/classes/data_cache.php";

// load the install/admin routines only if in ACP.
if(defined("IN_ADMINCP"))
{
	require_once MYBB_ROOT . "inc/plugins/quick_merge/acp.php";
}
else
{
	require_once MYBB_ROOT . "inc/plugins/quick_merge/forum.php";
}

?>

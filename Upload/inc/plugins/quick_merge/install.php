<?php
/*
 * Plug-in Name: QuickMerge for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.wildcardsworld.com
 *
 * the install routines
 */

/* quick_merge_info()
 *
 * Information about the plug-in used by MyBB for display as well as to connect with updates
 */
function quick_merge_info()
{
	global $mybb, $lang;

	if(!$lang->quick_merge)
	{
		$lang->load('quick_merge');
	}

	$settings_link = quick_merge_build_settings_link();
	if($settings_link)
	{
		$extra_links = <<<EOF
<ul>
	<li style="list-style-image: url(styles/default/images/icons/custom.gif)">
		{$settings_link}
	</li>
</ul>
EOF;
	}
	else
	{
		$extra_links = "<br />";
	}

	$button_pic = $mybb->settings['bburl'] . '/inc/plugins/quick_merge/images/donate.gif';
	$border_pic = $mybb->settings['bburl'] . '/inc/plugins/quick_merge/images/pixel.gif';
	$quick_merge_description = <<<EOF
<table width="100%">
	<tbody>
		<tr>
			<td>
				{$lang->quick_merge_description}{$extra_links}
			</td>
			<td style="text-align: center;">
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="VA5RFLBUC4XM4">
					<input type="image" src="{$button_pic}" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="{$border_pic}" width="1" height="1">
				</form>
			</td>
		</tr>
	</tbody>
</table>
EOF;

	$name = <<<EOF
<span style="font-familiy: arial; font-size: 1.5em; color: #a0a; text-shadow: 2px 2px 2px #a8a;">{$lang->quick_merge}</span>
EOF;
	$author = <<<EOF
</a></small></i><a href="http://www.rantcentralforums.com" title="Rant Central"><span style="font-family: Courier New; font-weight: bold; font-size: 1.2em; color: #0e7109;">Wildcard</span></a><i><small><a>
EOF;

	// This array returns information about the plug-in, some of which was prefabricated above based on whether the plugin has been installed or not.
	return array
	(
		"name" => $name,
		"description" => $quick_merge_description,
		"website" => "https://github.com/WildcardSearch/QuickMerge",
		"author" => $author,
		"authorsite" => "http://www.rantcentralforums.com",
		"version" => "1.0",
		"compatibility" => "16*",
		"guid" => "870e9163e2ae9b606a789d9f7d4d2462",
	);
}

/*
 * quick_merge_is_installed()
 *
 * returns true if installed, false if not
 */
function quick_merge_is_installed()
{
	return quick_merge_get_settingsgroup();
}

/*
 * quick_merge_install()
 *
 * standard function called by the core on install
 */
function quick_merge_install()
{
	global $lang;

	if(!$lang->quick_merge)
	{
		$lang->load('quick_merge');
	}

	// settings tables, templates, groups and setting groups
	require_once MYBB_ROOT . 'inc/plugins/quick_merge/classes/installer.php';
	$installer = new WildcardPluginInstaller(MYBB_ROOT . 'inc/plugins/quick_merge/install_data.php');
	$installer->install();
}

/*
 * quick_merge_activate()
 *
 * standard function called by the core on activate
 */
function quick_merge_activate()
{
	global $templates;

	// change the permissions to on by default
	change_admin_permission('config', 'quick_merge');

	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
	find_replace_templatesets('showthread', "#" . preg_quote('{$newreply}') . "#i", '{$quick_merge}{$newreply}');

	quick_merge_set_cache_version();
}

/*
 * quick_merge_deactivate()
 *
 * standard function called by the core on deactivate
 */
function quick_merge_deactivate()
{
	// remove the permissions
	change_admin_permission('config', 'quick_merge', -1);

	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
	find_replace_templatesets('showthread', "#" . preg_quote('{$quick_merge}') . "#i", '');
}

/*
 * quick_merge_uninstall()
 *
 * standard function called by the core on uninstall
 */
function quick_merge_uninstall()
{
	// settings tables, templates, groups and setting groups
	require_once MYBB_ROOT . 'inc/plugins/quick_merge/classes/installer.php';
	$installer = new WildcardPluginInstaller(MYBB_ROOT . 'inc/plugins/quick_merge/install_data.php');
	$installer->uninstall();
}

/*
 * settings
 */

/*
 * quick_merge_get_settingsgroup()
 *
 * retrieves the plug-in's settings group gid if it exists
 * attempts to cache repeat calls
 */
function quick_merge_get_settingsgroup()
{
	static $quick_merge_settings_gid;

	// if we have already stored the value
	if(isset($quick_merge_settings_gid))
	{
		// don't waste a query
		$gid = (int) $quick_merge_settings_gid;
	}
	else
	{
		global $db;

		// otherwise we will have to query the db
		$query = $db->simple_select("settinggroups", "gid", "name='quick_merge_settings'");
		$gid = (int) $db->fetch_field($query, 'gid');
	}
	return $gid;
}

/*
 * quick_merge_build_settings_url()
 *
 * builds the url to modify plug-in settings if given valid info
 *
 * @param - $gid is an integer representing a valid settings group id
 */
function quick_merge_build_settings_url($gid)
{
	if($gid)
	{
		return "index.php?module=config-settings&amp;action=change&amp;gid=" . $gid;
	}
}

/*
 * quick_merge_build_settings_link()
 *
 * builds a link to modify plug-in settings if it exists
 */
function quick_merge_build_settings_link()
{
	global $lang;

	if(!$lang->quick_merge)
	{
		$lang->load('quick_merge');
	}

	$gid = quick_merge_get_settingsgroup();

	// does the group exist?
	if($gid)
	{
		// if so build the URL
		$url = quick_merge_build_settings_url($gid);

		// did we get a URL?
		if($url)
		{
			// if so build the link
			return "<a href=\"{$url}\" title=\"{$lang->quick_merge_plugin_settings}\">{$lang->quick_merge_plugin_settings}</a>";
		}
	}
	return false;
}

/*
 * versioning
 */

/*
 * quick_merge_get_cache_version()
 *
 * check cached version info
 *
 * derived from the work of pavemen in MyBB Publisher
 */
function quick_merge_get_cache_version()
{
	global $cache;

	// get currently installed version, if there is one
	$quick_merge = $cache->read('quick_merge');
	if($quick_merge['version'])
	{
        return $quick_merge['version'];
	}
    return 0;
}

/*
 * quick_merge_set_cache_version()
 *
 * set cached version info
 *
 * derived from the work of pavemen in MyBB Publisher
 *
 */
function quick_merge_set_cache_version()
{
	global $cache;

	// get version from this plug-in file
	$quick_merge_info = quick_merge_info();

	// update version cache to latest
	$quick_merge = $cache->read('quick_merge');
	$quick_merge['version'] = $quick_merge_info['version'];
	$cache->update('quick_merge', $quick_merge);
    return true;
}

/*
 * quick_merge_unset_cache_version()
 *
 * remove cached version info
 *
 * derived from the work of pavemen in MyBB Publisher
 */
function quick_merge_unset_cache_version()
{
	global $cache;

	$quick_merge = $cache->read('quick_merge');
	$quick_merge = null;
	$cache->update('quick_merge', $quick_merge);
    return true;
}

?>

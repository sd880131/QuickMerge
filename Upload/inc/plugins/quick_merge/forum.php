<?php
/*
 * Plug-in Name: QuickMerge for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.wildcardsworld.com
 *
 * the forum-side functions and hook implementations
 */

quick_merge_initialize();

/*
 * quick_merge_showthread_start()
 *
 * displays the Quick Merge option select box if appropriate
 */
function quick_merge_showthread_start()
{
	global $mybb, $thread, $qm_cache;

	if($thread['replies'] >= $mybb->settings['quick_merge_max_replies'] || !quick_merge_check_user_permissions($mybb->settings['quick_merge_groups']))
	{
		return;
	}

	QuickMergeCache::load_cache();
	$threads = $qm_cache->read('threads');
	if(empty($threads))
	{
		return;
	}

	$tid_list = array_keys($threads);
	if(in_array($thread['tid'], $tid_list))
	{
		return;
	}

	global $lang, $quick_merge, $templates;
	if(!$lang->quick_merge)
	{
		$lang->load('quick_merge');
	}

	$thread_count = 0;
	$options = '';
	foreach($threads as $tid => $title)
	{
		if($tid == $thread['tid'])
		{
			continue;
		}
		if(strlen($title) > ($mybb->settings['quick_merge_title_length'] - 3))
		{
			$title = substr($title, 0, $mybb->settings['quick_merge_title_length']) . '...';
		}
		++$thread_count;
		eval("\$options .= \"" . $templates->get('qm_thread_row') . "\";");
	}

	// only show the Quick Merge selector if there is at least one thread to choose from
	if($thread_count)
	{
		// build the quick merge select box
		eval("\$quick_merge = \"" . $templates->get('qm_form') . "\";");
	}
}

/*
 * quick_merge_moderation_start()
 *
 * called when the tool is used
 */
function quick_merge_moderation_start()
{
	global $mybb, $lang;

	if(!$lang->quick_merge)
	{
		$lang->load('quick_merge');
	}

	// we are hooking into moderation.php so if it isn't our action do nothing
	if($mybb->input['action'] != 'quick_merge' || !isset($mybb->input['quick_merge_dest']))
	{
		return;
	}

	// store the info
	$to_tid = (int) $mybb->input['quick_merge_dest'];
	$from_tid = (int) $mybb->input['quick_merge_tid'];

	// get the thread title
	$dest_thread = get_thread($to_tid);
	$title = $dest_thread['subject'];

	// make sure we have a valid Moderation object
	require_once MYBB_ROOT."inc/class_moderation.php";
	$moderation = new Moderation;

	// do the merge
	if($moderation->merge_threads($from_tid, $to_tid, $title))
	{
		// redirect to the dest thread, last post
		moderation_redirect(get_thread_link($to_tid, 0, 'lastpost'), $lang->quick_merge_modredirect_message, $lang->quick_merge_modredirect_title);
	}
	// redirect back, error
	moderation_redirect(get_thread_link($from_tid), $lang->quick_merge_modredirect_error_message, $lang->quick_merge_modredirect_title);
}

/*
 * quick_merge_initialize()
 *
 * add the appropriate hooks and templates
 */
function quick_merge_initialize()
{
	global $plugins, $templatelist;
	switch(THIS_SCRIPT)
	{
		case 'showthread.php':
			$plugins->add_hook('showthread_start', 'quick_merge_showthread_start');
			require_once MYBB_ROOT . 'inc/plugins/quick_merge/install_data.php';
			$templates = ',' . implode(',', array_keys($templates));
			$templatelist .= $templates;
			break;
		case 'moderation.php':
			$plugins->add_hook('moderation_start', 'quick_merge_moderation_start');
			break;
	}
}

/*
 * quick_merge_check_user_permissions($good_groups)
 *
 * standard check of all user groups against an allowable list
 */
function quick_merge_check_user_permissions($good_groups)
{
	// no groups = all groups says wildy
	if(empty($good_groups))
	{
		return true;
	}

	// array-ify the list if necessary
	if(!is_array($good_groups))
	{
		$good_groups = explode(',', $good_groups);
	}

	global $mybb;
	if($mybb->user['uid'] == 0)
	{
		// guests don't require as much work ;-)
		return in_array(0, $good_groups);
	}

	// get all the user's groups in one array
	$users_groups = array($mybb->user['usergroup']);
	if($mybb->user['additionalgroups'])
	{
		$adtl_groups = explode(',', $mybb->user['additionalgroups']);
		$users_groups = array_merge($users_groups, $adtl_groups);
	}

	/*
	 * if any overlaps occur then they will be in $test_array,
	 * empty returns true/false so !empty = true for allow and false for disallow
	 */
	$test_array = array_intersect($users_groups, $good_groups);
	return !empty($test_array);
}

?>

<?php
/*
 * Plug-in Name: QuickMerge for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.wildcardsworld.com
 *
 * the admin functions
 */

define('QUICK_MERGE_URL', 'index.php?module=config-quick_merge');
require_once MYBB_ROOT . "inc/plugins/quick_merge/install.php";

/*
 * quick_merge_admin_load()
 *
 * the ACP page router
 */
$plugins->add_hook('admin_load', 'quick_merge_admin_load');
function quick_merge_admin_load()
{
	// globalize as needed to save wasted work
	global $page;
	if($page->active_action != 'quick_merge')
	{
		// not our turn
		return false;
	}

	// now load up, this is our time
	global $mybb, $lang, $html;
	if(!$lang->quick_merge)
	{
		$lang->load('quick_merge');
	}

	// URL, link and image markup generator
	require_once MYBB_ROOT . "inc/plugins/quick_merge/classes/html_generator.php";
	require_once MYBB_ROOT . "inc/plugins/quick_merge/classes/quick_merge_thread.php";
	$html = new HTMLGenerator(QUICK_MERGE_URL);
	QuickMergeCache::load_cache();

	// if there is an existing function for the action
	$page_function = 'quick_merge_admin_' . $mybb->input['action'];
	if(function_exists($page_function))
	{
		// run it
		$page_function();
	}
	else
	{
		// default to the main page
		quick_merge_admin_main();
	}
	// get out
	exit();
}

/*
 * quick_merge_admin_main()
 *
 * the main ACP page
 */
function quick_merge_admin_main()
{
	global $page, $lang, $mybb, $db, $html, $qm_cache;

	// adding a dest TID
	if($mybb->request_method == 'post')
	{
		if($mybb->input['mode'] == 'add')
		{
			// get the info
			$tid = (int) $mybb->input['tid'];
			$query = $db->simple_select('threads', 'subject', "tid='{$tid}'");
			
			if($db->num_rows($query) == 0)
			{
				flash_message($lang->quick_merge_error_invalid_thread, 'error');
				admin_redirect($html->url());
			}

			// already exists?
			$dup_query = $db->simple_select('quick_merge_threads', 'tid', "tid='{$tid}'");
			if($db->num_rows($dup_query) > 0)
			{
				flash_message($lang->quick_merge_error_thread_exists, 'error');
				admin_redirect($html->url());
			}

			$order_query = $db->simple_select('quick_merge_threads');
			$display_order = ($db->num_rows($order_query) + 1) * 10;

			// save the dest thread
			$merge_thread = new MergeThread();
			$merge_thread->set('title', $db->fetch_field($query, 'subject'));
			$merge_thread->set('tid', $tid);
			$merge_thread->set('display_order', $display_order);
			if(!$merge_thread->save())
			{
				flash_message($lang->quick_merge_error_save_fail, 'error');
				admin_redirect($html->url());
			}
			$qm_cache->update('has_changed', true);
			flash_message($lang->quick_merge_success_add, 'success');
			admin_redirect($html->url());
		}
		elseif($mybb->input['mode'] == 'order')
		{
			if(is_array($mybb->input['disp_order']) && !empty($mybb->input['disp_order']))
			{
				foreach($mybb->input['disp_order'] as $id => $order)
				{
					$merge_thread = new MergeThread($id);
					$merge_thread->set('display_order', $order);
					$merge_thread->save();
				}
				flash_message($lang->quick_merge_success_order, 'success');
			}
			else
			{
				flash_message($lang->quick_merge_error_order_fail, 'error');
			}
			admin_redirect($html->url());
		}
	}

	// delete
	if($mybb->input['mode'] == 'delete')
	{
		$merge_thread = new MergeThread($mybb->input['id']);
		if(!$merge_thread->is_valid())
		{
			flash_message($lang->quick_merge_error_delete_fail, 'error');
			admin_redirect($html->url());
		}
		if(!$merge_thread->remove())
		{
			flash_message($lang->quick_merge_error_delete_fail, 'error');
			admin_redirect($html->url());
		}
		$qm_cache->update('has_changed', true);
		flash_message($lang->quick_merge_success_delete, 'success');
		admin_redirect($html->url());
	}

	// page start
	$page->add_breadcrumb_item($lang->quick_merge, $html->url);
	$page->output_header($lang->quick_merge);

	$form = new Form($html->url(array("mode" => 'order')), 'post');
	$form_container = new FormContainer($lang->quick_merge_manage_threads);

	$form_container->output_row_header($lang->quick_merge_tid_title, array("width" => '10%'));
	$form_container->output_row_header($lang->quick_merge_subject_title, array("width" => '70%'));
	$form_container->output_row_header($lang->quick_merge_display_order_title, array("width" => '10%'));
	$form_container->output_row_header($lang->quick_merge_controls_title, array("width" => '10%'));

	$query = $db->simple_select('quick_merge_threads', '*', '', array("order_by" => 'display_order', "order_dir" => 'ASC'));
	if($db->num_rows($query) > 0)
	{
		while($thread = $db->fetch_array($query))
		{
			$form_container->output_cell($thread['tid']);
			$form_container->output_cell("<strong>{$thread['title']}</strong>");
			$form_container->output_cell($form->generate_text_box("disp_order[{$thread['id']}]", $thread['display_order'], array("style" => 'width: 50px;')));
			$form_container->output_cell($html->link($html->url(array("mode" => 'delete', "id" => $thread['id'])), 'Delete', array("style" => 'font-weight: bold;')));
			$form_container->construct_row();
		}
	}
	else
	{
		$form_container->output_cell("<span style=\"color: gray;\">{$lang->quick_merge_no_threads}</span>", array("colspan" => 4));
		$form_container->construct_row();
	}
	$form_container->end();
	$buttons = array($form->generate_submit_button($lang->quick_merge_order_title, array('name' => 'order')));
	$form->output_submit_wrapper($buttons);
	$form->end();

	// display add form
	$form = new Form($html->url(array("mode" => 'add')), "post");
	$form_container = new FormContainer($lang->quick_merge_add_threads);
	$form_container->output_row('', '', $form->generate_text_box('tid'));
	$form_container->end();
	$buttons = array($form->generate_submit_button($lang->quick_merge_add_title, array('name' => 'add_thread_submit')));
	$form->output_submit_wrapper($buttons);
	$form->end();

	// be done
	$page->output_footer();
	exit();
}

/*
 * quick_merge_admin_config_action_handler(&$action)
 *
 * @param - &$action is an array containing the list of selectable items on the config tab
 */
$plugins->add_hook('admin_config_action_handler', 'quick_merge_admin_config_action_handler');
function quick_merge_admin_config_action_handler(&$action)
{
	$action['quick_merge'] = array('active' => 'quick_merge');
}

/*
 * quick_merge_admin_config_menu()
 *
 * Add an entry to the ACP Config page menu
 *
 * @param - &$sub_menu is the menu array we will add a member to.
 */
$plugins->add_hook('admin_config_menu', 'quick_merge_admin_config_menu');
function quick_merge_admin_config_menu(&$sub_menu)
{
	global $lang;

	if(!$lang->quick_merge)
	{
		$lang->load('quick_merge');
	}

	end($sub_menu);
	$key = (key($sub_menu)) + 10;
	$sub_menu[$key] = array
	(
		'id' 		=> 'quick_merge',
		'title' 	=> $lang->quick_merge,
		'link' 		=> QUICK_MERGE_URL
	);
}

/*
 * quick_merge_admin_config_permissions()
 *
 * Add an entry to admin permissions list
 *
 * @param - &$admin_permissions is the array of permission types we are adding an element to
 */
$plugins->add_hook('admin_config_permissions', 'quick_merge_admin_config_permissions');
function quick_merge_admin_config_permissions(&$admin_permissions)
{
	global $lang;

	if(!$lang->quick_merge)
	{
		$lang->load('quick_merge');
	}
	$admin_permissions['quick_merge'] = $lang->quick_merge_admin_permissions_desc;
}

?>

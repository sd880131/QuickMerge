<?php
/*
 * Plug-in Name: QuickMerge for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.wildcardsworld.com
 *
 * this file contains an object wrapper for merge destination threads
 */

class MergeThread extends StorableObject
{
	protected $tid = 0;
	protected $title = '';
	protected $display_order = 0;

	/*
	 * function __construct()
	 *
	 * @param - $data - (mixed) an integer ID or an associative array of script data
	 */
	public function __construct($data = '')
	{
		$this->table_name = 'quick_merge_threads';
		parent::__construct($data);
	}
}

?>

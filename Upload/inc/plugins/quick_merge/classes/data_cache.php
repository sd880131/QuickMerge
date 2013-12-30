<?php
/*
 * Wildcard Helper Classes
 * ACP - MyBB Personal Cache
 *
 * a stand-alone cache to be used exclusively by a single plugin
 */

// base class (needs concrete class to work)
abstract class WildcardPluginCache extends StorableObject
{
	protected $cache_data = array();
	protected $has_changed = false;

	/*
	 * __construct()
	 *
	 * create a new cache wrapper instance
	 *
	 * @param - $table - (string) the db table name
	 */
	public function __construct()
	{
		$this->no_store[] = 'has_changed';

		global $db;
		$query = $db->simple_select($this->table_name, '*', '', array("limit" => 1));
		if($db->num_rows($query) == 1)
		{
			$this->valid = parent::__construct($db->fetch_array($query));
		}
	}

	/*
	 * __destruct()
	 *
	 * remove and save a new cache wrapper instance
	 */
	public function __destruct()
	{
		if($this->has_changed)
		{
			$this->save();
		}
	}

	/*
	 * load()
	 *
	 * a child method used to decode the cache into an associative array
	 *
	 * @param - $data - (mixed) an (int) id or an associative (array) of data
	 */
	public function load($data)
	{
		if(parent::load($data))
		{
			if($this->cache_data)
			{
				$this->cache_data = json_decode($this->cache_data, true);
			}
			return true;
		}
		return false;
	}

	/*
	 * read()
	 *
	 *	retrieve an individual cache entry
	 *
	 * @param - $key - (string) the name of the entry
	 */
	public function read($key)
	{
		if(isset($this->cache_data[$key]))
		{
			return $this->cache_data[$key];
		}
		return false;
	}

	/*
	 * update()
	 *
	 * update the value of a single cache entry
	 *
	 * @param - $key (string) the name of the entry
	 * @param - $val (mixed) the value of the entry
	 * @param - $store - (bool) true [default] to update the entire cache in the db
	 */
	public function update($key, $val, $store = true)
	{
		$this->cache_data[$key] = $val;
		$this->has_changed = true;
		if($store)
		{
			$this->save();
			$this->has_changed = false;
		}
	}
}

// concrete cache for QuickMerge
class QuickMergeCache extends WildcardPluginCache
{

	/*
	 * load_cache()
	 *
	 * ensures the global object is loaded
	 */
	public static function load_cache()
	{
		global $qm_cache;
		if($qm_cache instanceof QuickMergeCache != true)
		{
			$qm_cache = new QuickMergeCache;
		}
	}

	/*
	 * __construct()
	 *
	 * create a new cache wrapper instance
	 */
	public function __construct()
	{
		$this->table_name = 'quick_merge_cache';
		parent::__construct();
	}

	/*
	 * read($key)
	 *
	 * before parent method, check if thread cache needs to be rebuilt
	 */
	public function read($key)
	{
		if($key == 'threads' && $this->cache_data['has_changed'])
		{
			$this->build_thread_cache();
		}
		return parent::read($key);
	}

	/*
	 * build_thread_cache()
	 *
	 * rebuild the cache of merge destination threads used in forum-side
	 */
	public function build_thread_cache()
	{
		global $db;

		$this->cache_data['threads'] = array();

		// only load threads that exist
		$query = $db->query
		("
			SELECT
				qm.*
			FROM
				{$db->table_prefix}quick_merge_threads qm
			INNER JOIN
				{$db->table_prefix}threads t ON (qm.tid = t.tid)
			ORDER BY
				qm.display_order ASC;
		");

		if($db->num_rows($query) > 0)
		{
			$id_list = $sep = '';
			while($data = $db->fetch_array($query))
			{
				$this->cache_data['threads'][$data['tid']] = $data['title'];
				$id_list .= "{$sep}{$data['id']}";
				$sep = ',';
			}

			// delete any merge destination thread which does not point to a valid thread
			if($id_list)
			{
				$db->delete_query('quick_merge_threads', "NOT id IN({$id_list})");
			}
		}

		// reset the change flag and save the entire cache
		$this->cache_data['has_changed'] = false;
		$this->save();
	}
}

?>

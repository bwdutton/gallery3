<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database query wrapper.
 * 
 * $Id: Database_Query.php 4679 2009-11-10 01:45:52Z isaiah $
 * 
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Database_Query_Core implements \Stringable {

	protected $params;
	protected $ttl = FALSE;

	public function __construct(protected $sql = NULL)
    {
    }

	public function __toString(): string
	{
		// Return the SQL of this query
		return (string) $this->sql;
	}

	public function sql($sql)
	{
		$this->sql = $sql;

		return $this;
	}

	public function value($key, $value)
	{
		$this->params[$key] = $value;

		return $this;
	}

	public function bind($key, & $value)
	{
		$this->params[$key] =& $value;

		return $this;
	}

	public function execute($db = 'default')
	{
		if ( ! is_object($db))
		{
			// Get the database instance
			$db = Database::instance($db);
		}

		// Import the SQL locally
		$sql = $this->sql;

		if ( ! empty($this->params))
		{
			// Quote all of the values
			$params = array_map([$db, 'quote'], $this->params);

			// Replace the values in the SQL
			$sql = strtr($sql, $params);
		}

		if ($this->ttl !== FALSE)
		{
			// Load the result from the cache
			return $db->query_cache($sql, $this->ttl);
		}
		else
		{
			// Load the result (no caching)
			return $db->query($sql);
		}
	}

	/**
	 * Set caching for the query
	 *
	 * @param  mixed  Time-to-live (FALSE to disable, NULL for Cache default, seconds otherwise)
	 * @return Database_Query
	 */
	public function cache($ttl = NULL)
	{
		$this->ttl = $ttl;

		return $this;
	}

} // End Database_Query
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Ip_to_nation_data extends CI_Model {

	private $table = 'ip2nation';
	private $c_table = 'ip2nation_countries';

	// ----------------------------------------------------------------------

	/**
	 * Get a country by ip address
	 */
	function find($ip)
	{
		$BIN = $this->to_binary($ip);
		
		// If IP contains 39 or 92, we end up with ASCII quote or backslash
		// Let's be sure to escape!
		$BIN = $this->db->escape_str($BIN);

		$query = $this->db
			->select('country')
			->where("ip_range_low <= '{$BIN}'", '', FALSE)
			->where("ip_range_high >= '{$BIN}'", '', FALSE)
			->order_by('ip_range_low', 'desc')
			->limit(1, 0)
			->get($this->table);

		if ( ! $query->num_rows())
		{
			return FALSE;
		}

		return $query->row('country');
	}

	// ----------------------------------------------------------------------

	/**
	 * Replace IP data with that of all the files in `$dir`
	 */
	function load_dir($dir)
	{
		$dir = rtrim($dir, '/');
		return $this->load(
			glob($dir.'/*.csv')
		);
	}

	// ----------------------------------------------------------------------

	/**
	 * Replace IP data with that in `$files`
	 */
	function load($files)
	{
		// get old banned info
		$banned = $this->db
			->select('code')
			->get_where($this->c_table, array('banned' => 'y'))
			->result_array();

		// all we care about is if it's set
		$banned = array_map('array_pop', $banned);
		$banned = array_flip($banned);

		// remove all data
		$this->db->truncate($this->table);
		$this->db->truncate($this->c_table);


		// populate the db with file data
		$files = (array) $files;
		$countries = array();

		foreach ($files as $file)
		{
			$add_countries = $this->_read_and_insert($file);
			$countries = array_merge($countries, $add_countries);
		}

		// sort the new ones for a pretty db
		sort($countries);
		$countries = array_unique($countries);
		$countries = array_map('strtolower', $countries);

		// merge with the banned and insert
		foreach ($countries as &$c)
		{
			$c = array(
				'code' => $c,
				'banned' => isset($banned[$c]) ? 'y' : 'n'
			);
		}

		$this->db->insert_batch($this->c_table, $countries);
	}

	// ----------------------------------------------------------------------

	/**
	 * Ban a countries by their country code
	 */
	function ban($countries)
	{
		// Set all countries to unbanned
		$this->db->update($this->c_table, array('banned' => 'n'));

		// And then reban those we know about
		if (count($countries))
		{
			$this->db
				->where_in('code', $countries)
				->update($this->c_table, array('banned' => 'y'));
		}
	}

	// ----------------------------------------------------------------------

	/**
	 * Convert an IP address to its IPv6 packed format
	 */
	function to_binary($addr)
	{
		// all IPv4 go to IPv6 mapped
		if (strpos($addr, ':') === FALSE && strpos($addr, '.') !== FALSE)
		{
			$addr = '::'.$addr;
		}
		return inet_pton($addr);
	}

	// ----------------------------------------------------------------------

	/**
	 * Read the ip file and update the db
	 *
	 * Returns a list of countries in the file
	 */
	private function _read_and_insert($file)
	{
		if (($fh = fopen($file, 'r')) === FALSE)
		{
			return FALSE;
		}

		$batch = 0;
		$insert = array();
		$countries = array();

		while (($data = fgetcsv($fh, 300, ',')) !== FALSE)
		{
			if (count($data) < 5)
			{
				continue;
			}

			// $low, $high, $dec_low, $dec_high, $c_code, $c_name
			$insert[$batch++] = array(
				'ip_range_low'	=> $this->to_binary($data[0]),
				'ip_range_high'	=> $this->to_binary($data[1]),
				'country'		=> strtolower($data[4])
			);

			$countries[] = $data[4];

			if ($batch >= 1000)
			{
				$countries = array_unique($countries);
				$this->db->insert_batch('exp_ip2nation', $insert);

				$batch = 0;
				$insert = array();
			}
		}

		// deal with leftovers
		if (count($insert))
		{
			$this->db->insert_batch('exp_ip2nation', $insert);
		}

		$countries = array_unique($countries);
		sort($countries);

		fclose($fh);

		return $countries;
	}
}

/* End of file Iptonation_math.php */
/* Location: system/expressionengine/modules/ip_to_nation/libraries/Iptonation_math.php
 */
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Blacklist Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Blacklist {

	var $whitelisted = 'n';		// Is this request whitelisted
	var $blacklisted = 'n';		// Is this request blacklisted.


	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Blacklist Checker
	 *
	 * This function checks all of the available blacklists, such as urls,
	 * IP addresses, and user agents. URLs are checked as both referrers and
	 * in all $_POST'ed contents (such as comments).
	 *
	 * @access	private
	 * @return	bool
	 */
	function _check_blacklist()
	{
		// Check the referrer
		// Since we already need to check all post values for illegal urls
		// below, we'll temporarily write our referrer to $_POST.
		if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '')
		{
			$test_ref = ee()->security->xss_clean($_SERVER['HTTP_REFERER']);

			if ( ! preg_match("#^http://\w+\.\w+\.\w*#", $test_ref))
			{
				if (substr($test_ref, 0, 7) == 'http://' AND substr($test_ref, 0, 11) != 'http://www.')
				{
					$test_ref = preg_replace("#^http://(.+?)#", "http://www.\\1", $test_ref);
				}
			}

			$_POST['HTTP_REFERER'] = $test_ref;
		}

		// No referrer, and no posted data - no need to blacklist.
		// In other words, if your ip is blacklisted you can still see the
		// site, but you can not contribute content.
		if (count($_POST) == 0)
		{
			return TRUE;
		}

		ee()->load->model('addons_model');
		$installed = ee()->addons_model->module_installed('blacklist');

		if ( ! $installed)
		{
			unset($_POST['HTTP_REFERER']);
			return TRUE;
		}

		// Whitelisted Items
		$whitelisted_ip		= array();
		$whitelisted_url	= array();
		$whitelisted_agent	= array();

		$results = ee()->db->query("SELECT whitelisted_type, whitelisted_value FROM exp_whitelisted
										 WHERE whitelisted_value != ''");

		if ($results->num_rows() > 0)
		{
			foreach($results->result_array() as $row)
			{
				if ($row['whitelisted_type'] == 'url')
				{
					$whitelisted_url = explode('|', $row['whitelisted_value']);
				}
				elseif($row['whitelisted_type'] == 'ip')
				{
					$whitelisted_ip = explode('|', $row['whitelisted_value']);
				}
				elseif($row['whitelisted_type'] == 'agent')
				{
					$whitelisted_agent = explode('|', $row['whitelisted_value']);
				}
			}
		}

		if (ee()->config->item('cookie_domain') !== FALSE && ee()->config->item('cookie_domain') != '')
		{
			$whitelisted_url[] = ee()->config->item('cookie_domain');
		}

		$site_url = ee()->config->item('site_url');

		$whitelisted_url[] = $site_url;

		if ( ! preg_match("#^http://\w+\.\w+\.\w*#", $site_url))
		{
			if (substr($site_url, 0, 7) == 'http://' AND substr($site_url, 0, 11) != 'http://www.')
			{
				$whitelisted_url[] = preg_replace("#^http://(.+?)#", "http://www.\\1", $site_url);
			}
		}

		// Domain Names Array
		$domains = array('net','com','org','info', 'name','biz','us','de', 'uk');

		// Blacklisted Checking
		$query	= ee()->db->query("SELECT blacklisted_type, blacklisted_value FROM exp_blacklisted");

		if ($query->num_rows() == 0)
		{
			unset($_POST['HTTP_REFERER']);
			return TRUE;
		}

		// Load the typography helper so we can do entity_decode()
		ee()->load->helper('typography');

		foreach($query->result_array() as $row)
		{
			if ($row['blacklisted_type'] == 'url' && $row['blacklisted_value'] != '' && $this->whitelisted != 'y')
			{
				$blacklist_values = explode('|', $row['blacklisted_value']);

				if ( ! is_array($blacklist_values) OR count($blacklist_values) == 0)
				{
					continue;
				}

				foreach ($_POST as $key => $value)
				{
					// Smallest URL Possible
					// Or no external links
					if (is_array($value) OR strlen($value) < 8)
					{
						continue;
					}

					// Convert Entities Before Testing
					$value = entity_decode($value);
					$value .= ' ';

					// Clear period from the end of URLs
					$value = preg_replace("#(^|\s|\()((http://|http(s?)://|www\.)\w+[^\s\)]+)\.([\s\)])#i", "\\1\\2{{PERIOD}}\\4", $value);

					// Sometimes user content such as comments contain multiple
					// urls, so we need to check them individually.
					if (preg_match_all("/([f|ht]+tp(s?):\/\/[a-z0-9@%_.~#\/\-\?&=]+.)".
										"|(www.[a-z0-9@%_.~#\-\?&]+.)".
										"|([a-z0-9@%_~#\-\?&]*\.(".implode('|', $domains)."))/si", $value, $matches))
					{
						for($i = 0; $i < count($matches['0']); $i++)
						{
							// If this is a referrer or the comment module's
							// url field we know that it's just a single match.
							if ($key == 'HTTP_REFERER' OR $key == 'url')
							{
								$matches['0'][$i] = $value;
							}

							foreach($blacklist_values as $bad_url)
							{
								if ($bad_url != '' && stristr($matches['0'][$i], $bad_url) !== FALSE)
								{
									$bad = 'y';

									// Check Bad Against Whitelist - URLs

									if ( is_array($whitelisted_url) && count($whitelisted_url) > 0)
									{
										$parts = explode('?',$matches['0'][$i]);

										foreach($whitelisted_url as $pure)
										{
											if ($pure != '' && stristr($parts['0'], $pure) !== FALSE)
											{
												$bad = 'n';
												$this->whitelisted = 'y';
												break;
											}
										}
									}

									// Check Bad Against Whitelist - IPs
									if ( is_array($whitelisted_ip) && count($whitelisted_ip) > 0)
									{
										foreach($whitelisted_ip as $pure)
										{
											if ($pure != '' && strpos(ee()->input->ip_address(), $pure) !== FALSE)
											{
												$bad = 'n';
												$this->whitelisted = 'y';
												break;
											}
										}
									}

									if ($bad == 'y')
									{
										// Referer mismatches get a access denied error
										// since the url error doesn't make sense for a
										// user who didn't take any actions.
										if ($key == 'HTTP_REFERER')
										{
											$this->blacklisted = 'y';
										}
										else
										{
											exit('Action Denied: Blacklisted Item Found'."\n<br/>".$matches['0'][$i]);
										}
									}
									else
									{
										break;  // Free to move on
									}
								}
							}
						}
					}
				}
			}
			elseif($row['blacklisted_type'] == 'ip' && $row['blacklisted_value'] != '' && $this->whitelisted != 'y')
			{
				$blacklist_values = explode('|', $row['blacklisted_value']);

				if ( ! is_array($blacklist_values) OR count($blacklist_values) == 0)
				{
					continue;
				}

				foreach($blacklist_values as $bad_ip)
				{
					if ($bad_ip != '' && strpos(ee()->input->ip_address(), $bad_ip) === 0)
					{
						$bad = 'y';

						if ( is_array($whitelisted_ip) && count($whitelisted_ip) > 0)
						{
							foreach($whitelisted_ip as $pure)
							{
								if ($pure != '' && strpos(ee()->input->ip_address(), $pure) !== FALSE)
								{
									$bad = 'n';
									$this->whitelisted = 'y';
									break;
								}
							}
						}

						if ($bad == 'y')
						{
							$this->blacklisted = 'y';
							break;
						}
						else
						{
							unset($_POST['HTTP_REFERER']);
							return TRUE; // whitelisted, so end
						}
					}
				}
			}
			elseif($row['blacklisted_type'] == 'agent' && $row['blacklisted_value'] != '' && ee()->input->user_agent() != '' && $this->whitelisted != 'y')
			{
				$blacklist_values = explode('|', $row['blacklisted_value']);

				if ( ! is_array($blacklist_values) OR count($blacklist_values) == 0)
				{
					continue;
				}

				foreach($blacklist_values as $bad_agent)
				{
					if ($bad_agent != '' && stristr(ee()->input->user_agent(), $bad_agent) !== FALSE)
					{
						$bad = 'y';

						if ( is_array($whitelisted_ip) && count($whitelisted_ip) > 0)
						{
							foreach($whitelisted_ip as $pure)
							{
								if ($pure != '' && strpos(ee()->input->user_agent(), $pure) !== FALSE)
								{
									$bad = 'n';
									$this->whitelisted = 'y';
									break;
								}
							}
						}

						if ( is_array($whitelisted_agent) && count($whitelisted_agent) > 0)
						{
							foreach($whitelisted_agent as $pure)
							{
								if ($pure != '' && strpos(ee()->input->agent, $pure) !== FALSE)
								{
									$bad = 'n';
									$this->whitelisted = 'y';
									break;
								}
							}
						}

						if ($bad == 'y')
						{
							$this->blacklisted = 'y';
						}
						else
						{
							unset($_POST['HTTP_REFERER']);
							return TRUE; // whitelisted, so end
						}
					}
				}
			}
		}

		unset($_POST['HTTP_REFERER']);

		return TRUE;
	}


}

/* End of file Blacklist.php */
/* Location: ./system/expressionengine/libraries/Blacklist.php */
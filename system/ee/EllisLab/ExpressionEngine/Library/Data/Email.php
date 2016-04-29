<?php

namespace EllisLab\ExpressionEngine\Library\Data;

class Email {

	private $email;

	public function __construct($email)
	{
		$this->email = $email;
	}

	/**
	 * Check to see if the email is valid
	 *
	 * @return boolean TRUE if it's valid, FALSE otherwise
	 */
	public function is_valid()
	{
		return (bool) filter_var($this->email, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * Check to see if the email address is unique on the site
	 *
	 * @return boolean TRUE if it's unique, FALSE if it already exists
	 */
	public function unique()
	{
		if (strpos($this->email, '@gmail.com') !== FALSE)
		{
			$address = explode('@', $this->email);
			$query = ee()->db->query('SELECT REPLACE(REPLACE(LOWER(email), "@gmail.com", ""), ".", "") AS gmail
				FROM exp_members
				WHERE email LIKE "%gmail.com"
				HAVING gmail = "'.str_replace('.', '', $address[0]).'";');
			$count = $query->num_rows();
		}
		else
		{
			$count = ee('Model')->get('Member')
				->filter('email', $this->email)
				->count();
		}

		return ($count <= 0);
	}
}

// EOF

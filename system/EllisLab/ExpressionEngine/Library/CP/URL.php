<?php
namespace EllisLab\ExpressionEngine\Library\CP;

class URL {
	public $path;
	public $session_id;
	public $qs = array();

	/**
	 * Create a CP Path
	 * @param	string	$path		The path (i.e. 'logs/cp')
	 * @param	mixed	$qs			Query string parameters [array|string]
	 * @param	string	$session_id The session id
	 */
	public function __construct($path, $session_id = NULL, $qs = array())
	{
		$this->path = $path;
		$this->session_id = $session_id;

		if (is_array($qs))
		{
			$this->qs = $qs;
		}
		else
		{
			parse_str(str_replace(AMP, '&', $qs), $this->qs);
		}
	}

	/**
	 * When accessed as a string simply complile the URL and return that
	 *
	 * @return string	The URL
	 */
	public function __toString()
	{
		return $this->compile();
	}

	/**
	 * Sets a value in the $qs array which will become the Query String of
	 * the request
	 *
	 * @param $key		string	The name of the query string variable
	 * @param $value	string	The value of the query string variable
	 **/
	public function setQueryStringVariable($key, $value)
	{
		$this->qs[$key] = $value;
	}

	/**
	 * Compiles and returns a URL
	 *
	 * @return string	The URL
	 */
	public function compile()
	{
		$path = trim($this->path, '/');
		$path = preg_replace('#^cp(/|$)#', '', $path);

		$qs = $this->qs;

		if ($this->session_id)
		{
			$qs['S'] = $this->session_id;
		}

		$qs = http_build_query($qs, AMP);

		$path = rtrim('?/cp/'.$path, '/');

		return SELF.$path.rtrim('?'.$qs, '?');
	}
}
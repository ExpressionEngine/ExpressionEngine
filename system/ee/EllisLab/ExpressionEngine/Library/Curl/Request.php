<?php

namespace EllisLab\ExpressionEngine\Library\Curl;

abstract class Request {

	public function __construct($url, $data, $callback = NULL)
	{
		if ( ! function_exists('curl_version'))
		{
			throw new \Exception(lang('curl_not_installed'));
		}

		$this->config = array(
			CURLOPT_URL => $url,
    		CURLOPT_RETURNTRANSFER => 1,
		);

		foreach ($data as $key => $val)
		{
			if (substr($key, 0, 7) == "CURLOPT")
			{
				$this->config[$key] = $val;
			}
		}

		if ( ! empty($callback))
		{
			$this->callback = $callback;
		}
	}

	public function exec() {
		$curl = curl_init();
		curl_setopt_array($curl, $this->config);
		$data = curl_exec($curl);
		curl_close($curl);

		if ( ! empty($this->callback))
		{
			return call_user_func($this->callback, $data);
		}

		return $this->callback($data);
	}

	public function callback($data)
	{
		return $data;
	}

}

// EOF

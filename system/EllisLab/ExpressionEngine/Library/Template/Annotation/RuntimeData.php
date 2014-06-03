<?php

namespace EllisLab\ExpressionEngine\Library\Template\Annotation;

/**
 * Annotation data object
 */
class RuntimeData {

	private $data;

	public function __construct(array $data)
	{
		$this->data = $data;
	}

	public function __get($key)
	{
		return array_key_exists($key, $this->data) ? $this->data[$key] : NULL;
	}

	public function __set($key, $value)
	{
		return $this->data[$key] = $value;
	}

	public function __isset($key)
	{
		return isset($this->data[$key]);
	}

}
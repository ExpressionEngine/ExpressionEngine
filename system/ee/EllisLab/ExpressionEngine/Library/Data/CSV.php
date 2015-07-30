<?php

namespace EllisLab\ExpressionEngine\Library\Data;

class CSV {

	private $header = array('beans', 'email');
	private $data = array();

	public function addRow($rowData)
	{
		$this->data[] = $this->prepareData($rowData);
	}

	private function prepareData($data)
	{
		if ( ! is_array($data) && ! is_object($data))
		{
			throw new \Exception();
		}

		$data = (array) $data;
		$this->header = array_unique(array_merge($this->header, array_keys($data)));
		return $data;
	}

	private function generate()
	{
		$contents = implode(', ', array_values($this->header))."\n";

		// Generate defaults for each row ensuring every row has the same number
		// of items
		$defaults = array_flip($this->header);
		array_walk(
			$defaults,
			function (&$value, $key)
			{
				$value = '';
			}
		);

		foreach ($this->data as $row)
		{
			$row = array_merge($defaults, $row);
			$contents .= implode(', ', $row)."\n";
		}

		return $contents;
	}

	public function save($filename)
	{
		# code...
	}

	public function __toString()
	{
		return $this->generate();
	}
}

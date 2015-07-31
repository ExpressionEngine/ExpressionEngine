<?php

namespace EllisLab\ExpressionEngine\Library\Data;

class CSV {

	private $header = array();
	private $data = array();

	/**
	 * Add a row to the CSV object
	 *
	 * @param Array/object $rowData Associative array or object with properties
	 *                              to be added
	 * @return Object The CSV object itself, so you can chain ->addRow() and
	 *                ->save() calls
	 */
	public function addRow($rowData)
	{
		$this->data[] = $this->prepareData($rowData);
		return $this;
	}

	/**
	 * Make sure the data is an associative array or object
	 *
	 * @param  Mixed $data Whatever addRow() was sent
	 * @throws InvalidArgumentException
	 * @return array       Associative array of the data sent in
	 */
	private function prepareData($data)
	{
		if ( ! is_object($data)
			&& ( ! is_array($data) || ! $this->isAssociative($data)))
		{
			throw new \InvalidArgumentException('Rows sent to CSV should be an object or associative array');
		}

		$data = (array) $data;
		$this->header = array_unique(array_merge($this->header, array_keys($data)));
		return $data;
	}

	/**
	 * Checks to see if an array is associative or NOT
	 *
	 * @param  Array  $array Array to check for associativity
	 * @return boolean       TRUE if associative, FALSE if not
	 */
	private function isAssociative(array $array)
	{
		return (bool) count(array_filter(array_keys($array), 'is_string'));
	}

	/**
	 * Generate the content for output
	 *
	 * @return String Generated CSV content
	 */
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

	/**
	 * Save the CSV contents to a file
	 *
	 * @param  String $filename Path to a file
	 *
	 * @return boolean          TRUE if saved, FALSE if not
	 */
	public function save($filename)
	{
		return (bool) file_put_contents($filename, $this->generate());
	}

	/**
	 * Display the CSV as a string
	 *
	 * @return string Generated CSV content
	 */
	public function __toString()
	{
		return $this->generate();
	}
}

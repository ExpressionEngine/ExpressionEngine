<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\View;

/**
 * ViewHelpers
 */
class ViewHelpers {

	/**
	 * Takes an array of key => value pairs and normalizes it into something
	 * more digestable by our React components, like SelectList and Dropdown.
	 * Namely, key/value arrays get passed as an object to the component, but
	 * keys in objects do not retain their order, so this will turn it into an
	 * array and also deal with things like section headers and nested children.
	 *
	 * @param array $choices Array of key/value options
	 * @param boolean $disable_headings Whether or not to treat nested arrays as
	 *   children or as items belonging to a subheading
	 * @return array Array of choices ready for a React component
	 */
	public function normalizedChoices($choices, $disable_headings = NULL)
	{
		$return_array = [];

		// Auto-disable headings if all array keys are numeric
		if (is_null($disable_headings) &&
			count(array_filter(array_keys($choices), 'is_numeric')) === count($choices))
		{
			$disable_headings = TRUE;
		}

		$current_section = NULL;
		foreach ($choices as $value => $label)
		{
			if ( ! $disable_headings && is_array($label))
			{
				$current_section = $value;
				$return_array[] = ['section' => $value];
				$return_array = array_merge($return_array, $this->normalizedChoices($label, $disable_headings));
				continue;
			}

			$choice = [
				'value' => $value,
				'label' => $label,
				'instructions' => isset($label['instructions']) ? $label['instructions'] : '',
				'component' => isset($label['component']) ? $label['component'] : '',
				'sectionLabel' => $current_section
			];

			// Any of these can be overridden by specifying them in the source array
			if (isset($label['label'])) $choice['label'] = $label['label'];
			if (isset($label['value'])) $choice['value'] = $label['value'];
			if (isset($label['name'])) $choice['label'] = $label['name'];

			if (isset($label['children']))
			{
				$choice['children'] = $this->normalizedChoices($label['children'], $disable_headings);
			}

			$return_array[] = $choice;
		}

		return $return_array;
	}

	/**
	 * Given a normalized array of choices, returns the label for a given value
	 *
	 * @param string $value Value to search for
	 * @param array $choices Normalized choices
	 * @return mixed String of label if found, FALSE if not found
	 */
	public function findLabelForValue($value, $choices)
	{
		foreach ($choices as $choice)
		{
			if (isset($choice['value']) && $value == $choice['value'])
			{
				return $choice['label'];
			}

			if (isset($choice['children']))
			{
				$label = $this->findLabelForValue($value, $choice['children']);
				if ($label)
				{
					return $label;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Counts total items in a normalized array of choices
	 *
	 * @param array $choices Normalized choices
	 * @return int Total items
	 */
	public function countChoices($choices)
	{
		$count = 0;
		foreach ($choices as $choice)
		{
			$count += 1;
			if (isset($choice['children']))
			{
				$count += $this->countChoices($choice['children']);
			}
		}

		return $count;
	}

}
// EOF

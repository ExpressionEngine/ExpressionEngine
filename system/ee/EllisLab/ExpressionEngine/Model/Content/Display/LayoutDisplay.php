<?php

namespace EllisLab\ExpressionEngine\Model\Content\Display;

use InvalidArgumentException;

class LayoutDisplay {

	/**
	 * @var $tabs An array of LayouTab objects
	 */
	protected $tabs = array();

	/**
	 * Adds a LayoutTab to the display
	 *
	 * @param LayoutTab $tab The LayoutTab to add
	 * @return void
	 */
	public function addTab(LayoutTab $tab)
	{
		$this->tabs[$tab->id] = $tab;
	}

	/**
	 * Sets the tabs (in bulk) to the display
	 *
	 * @param array $tab An array of LayoutTabs
	 * @return void
	 */
	public function setTabs(array $tabs)
	{
		foreach ($tabs as $tab)
		{
			$this->addTab($tab);
		}
	}

	/**
	 * Fetches a LayoutTab based on its tab id.
	 *
	 * @throws InvalidArgumentException When no tab with the given id is available
	 * @param mixed $tab_id The id of the tab
	 * @return LayoutTab The requested LayoutTab
	 */
	public function getTab($tab_id)
	{
		if ( ! array_key_exists($tab_id, $this->tabs))
		{
			throw new InvalidArgumentException("No such tab: '{$tab_id}' on ".get_called_class());
		}

		return $this->tabs[$tab_id];
	}

	/**
	 * Returns all the tabs
	 *
	 * @return array An indexed array of LayoutTabs
	 */
	public function getTabs()
	{
		return array_values($this->tabs);
	}

	/**
	 * Returns all the fields across all the LayoutTabs
	 *
	 * @return array An array of fields
	 */
	public function getFields()
	{
		$fields = array();

		foreach ($this->getTabs() as $tab)
		{
			$fields = array_merge($fields, $tab->getFields());
		}

		return $fields;
	}
}

// EOF

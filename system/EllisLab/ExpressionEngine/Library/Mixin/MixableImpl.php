<?php

namespace EllisLab\ExpressionEngine\Library\Mixin;

abstract class MixableImpl implements Mixable {

	protected $_mixin_manager;

	/**
	 * @return array Array of classes to mixin
	 */
	abstract protected function getMixinClasses();

	/**
	 * Get the current mixin manager
	 */
	public function getMixinManager()
	{
		if ( ! isset($this->_mixin_manager))
		{
			$this->setMixinManager($this->newMixinManager());
		}

		return $this->_mixin_manager;
	}

	/**
	 * Set a mixin manager
	 */
	public function setMixinManager($manager)
	{
		$this->_mixin_manager = $manager;
	}

	/**
	 * Create a new mixin manager
	 */
	protected function newMixinManager()
	{
		return new Manager($this, $this->getMixinClasses());
	}
}
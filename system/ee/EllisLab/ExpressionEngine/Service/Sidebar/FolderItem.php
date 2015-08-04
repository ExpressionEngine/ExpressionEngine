<?php
namespace EllisLab\ExpressionEngine\Service\Sidebar;

use EllisLab\ExpressionEngine\Service\View\ViewFactory;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine FolderItem Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class FolderItem extends ListItem {

	protected $edit_url = '';
	protected $name;
	protected $remove_confirmation;
	protected $removal_key;
	protected $removal_key_value;

	public function __construct($text, $url = NULL, $name, $removal_key)
	{
		parent::__construct($text, $url);

		$this->name = $name;
		$this->removal_key = $removal_key;
	}

	public function asDefaultItem()
	{
		$this->class .= 'default ';
		return $this;
	}

	public function withEditUrl($url)
	{
		$this->edit_url = $url;
		return $this;
	}

	public function withRemoveConfirmation($msg)
	{
		$this->remove_confrirmation = $msg;
		return $this;
	}

	public function identifiedBy($val)
	{
		$this->removal_key_value = $val;
		return $this;
	}

	public function render(ViewFactory $view)
	{
		$class = trim($this->class);

		if ($class)
		{
			$class = ' class="' . $class . '"';
		}

		$vars = array(
			'text' => $this->text,
			'url' => $this->url,
			'class' => $class,
			'edit_url' => $this->edit_url,
			'modal_name'=> $this->name,
			'confirm' => $this->remove_confirmation,
			'key' => $this->removal_key,
			'value' => $this->removal_key_value
		);

		return $view->make('_shared/sidebar/folder_item')->render($vars);
	}

}
// EOF
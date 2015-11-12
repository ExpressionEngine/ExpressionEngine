<?php

namespace EllisLab\Addons\FilePicker\Service\FilePicker;

use Cp;
use EllisLab\ExpressionEngine\Service\URL\URLFactory;
use EllisLab\ExpressionEngine\Service\Modal\ModalCollection;
use EllisLab\ExpressionEngine\Service\View\View;

class Factory {

	protected $url;

	public function __construct(UrlFactory $url)
	{
		$this->url = $url;
	}

	public function injectModal(ModalCollection $modals, View $modal_view, Cp $cp)
	{
		$modal_vars = array('name'=> 'modal-file', 'contents' => '');
		$modal = $modal_view->render($modal_vars);

		$modals->addModal('modal-file', $modal);
		$cp->add_js_script('file', 'cp/files/picker');
	}

	public function make($dirs = 'all')
	{
		$fp = new FilePicker($this->url);
		$fp->setDirectories($dirs);

		return $fp;
	}
}

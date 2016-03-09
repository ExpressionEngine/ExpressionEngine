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

	/**
	 * Inject the Filepicker modal into the CP. Called from the DI, do not
	 * call manually.
	 */
	public function injectModal(ModalCollection $modals, View $modal_view, Cp $cp)
	{
		$modal_vars = array('name'=> 'modal-file', 'contents' => '');
		$modal = $modal_view->render($modal_vars);

		$modals->addModal('modal-file', $modal);
		$cp->add_js_script('file', 'cp/files/picker');
	}

	/**
	 * Construct a filepicker instance
	 *
	 * @param String $dirs Allowed directories
	 * @return FilePicker
	 */
	public function make($dirs = 'all')
	{
		$fp = new FilePicker($this->url);
		$fp->setDirectories($dirs);

		return $fp;
	}

	/**
	 * Handle a filepicker request. Does all the default stuff.
	 */
	public function handleRequest()
	{
		$fp = $this->fromRequest();
		return $fp->render();
	}

	/**
	 * Take the request from the url and deal with it
	 */
	protected function fromRequest()
	{
		$request = new RequestParser();

		return new Endpoint($request);
	}
}

// EOF

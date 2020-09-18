<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Queue_mcp {

	public function index()
	{

		$html = '<p>Time to make magic</p>';

		return [
			'body'	=> $html,
			'breadcrumb' => [
				ee('CP/URL')->make('addons/settings/queue')->compile() => lang('queue')
			],
			'heading' => lang('queue_settings'),
		];

	}

}
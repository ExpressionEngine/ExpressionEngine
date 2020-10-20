<?php

namespace ExpressionEngine\Addons\Queue\Jobs;

use ExpressionEngine\Addons\Queue\Traits\Queueable;

class SampleJob {

	use Queueable;

	public $email;

	protected $attempts = 3;

	protected $sleep = 10;

	private $quotes = [
		'Acceptance is such an important commodity, some have called it "the first law of personal growth." ~ Peter McWilliams',
		'Hope is necessary in every condition.~ Samuel Johnson',
		'That\'s the great thing about being in the third grade. If you\'ve got one polysyllabic adjective, everyone thinks you\'re a genius. ~ John Green',
		'The strongest principle of growth lies in human choice. ~ George Eliot',
		'For purposes of action nothing is more useful than narrowness of thought combined with energy of will.~ Henri-Frédéric Amiel',
		'I am never afraid of what I know. ~ Anna Sewell',
		'Enjoy your own life without comparing it with that of another. ~ Marquis de Condorcet',
		'All that really belongs to us is time; even he who has nothing else has that. ~ Baltasar Gracian',
	];

	public function __construct($email)
	{
		$this->construct();
		$this->email = $email;
	}

	public function handle()
	{

		$quote = $this->quotes[mt_rand(0, count($this->quotes) - 1)];

		ee()->load->library('email');

		ee()->load->helper('text');

		ee()->email->wordwrap = true;
		
		ee()->email->mailtype = 'html';
		
		ee()->email->from($this->email);
		
		ee()->email->to($this->email);

		ee()->email->subject('EE Queue Test');
		
		ee()->email->message(entities_to_ascii($quote));
		
		$result = ee()->email->send();

		ee()->email->clear();

		return true;

	}

}
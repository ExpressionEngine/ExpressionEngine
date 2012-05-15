<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class EE_Controller extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->library('core');

		$this->core->bootstrap();
		$this->core->run_ee();
	}
}

class CP_Controller extends EE_Controller {
	
	function __construct()
	{
		parent::__construct();
		$this->core->run_cp();
	}
}


/* End of file  */
/* Location: system/expressionengine/libraries/core/EE_Controller.php */
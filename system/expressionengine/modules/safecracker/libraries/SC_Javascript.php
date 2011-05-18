<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'controllers/cp/javascript.php';

class SC_Javascript extends Javascript
{
	private $js_path;
	
	/**
	 * Constructor
	 */
	public function __construct($params = array())
	{
		if (isset($params['instance']))
		{
			foreach (get_object_vars($params['instance']) as $key => $value)
			{
				$this->$key = $value;
			}
		}

		if ($this->config->item('use_compressed_js') == 'n')
		{
			$this->js_path = PATH_THEMES.'javascript/src/';
			
			if ( ! defined('PATH_JQUERY'))
			{
				define('PATH_JQUERY', $this->js_path.'jquery/');
			}
		}
		else
		{
			$this->js_path = PATH_THEMES.'javascript/compressed/';
			
			if ( ! defined('PATH_JQUERY'))
			{
				define('PATH_JQUERY', $this->js_path.'jquery/');
			}
		}

		$this->lang->loadfile('jquery');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Combo Load
	 */
	public function combo_load()
	{
		parent::combo_load();
		
		if ($this->input->get('include_jquery') == 'y')
		{
			$this->output->set_output(file_get_contents(PATH_JQUERY.'jquery.js')."\n\n".$this->output->get_output());
		}
		
		$this->safecracker->load_channel_standalone();
		
		if ($this->input->get('use_live_url') == 'y')
		{
			$this->output->append_output($this->safecracker->channel_standalone->_url_title_js()."\n\n");
		}
		
		$this->load->helper('smiley');
		
		$this->output->append_output((($this->config->item('use_compressed_js') != 'n') ? str_replace(array("\n", "\t"), '', smiley_js('', '', FALSE)) : smiley_js('', '', FALSE))."\n\n");

		$this->output->append_output(file_get_contents($this->js_path.'saef.js'));
		
		$this->output->set_header('Content-Length: '.strlen($this->output->get_output()));
	}
}

/* End of file SC_Javascript.php */
/* Location: ./system/expressionengine/modules/safecracker/libraries/SC_Javascript.php */
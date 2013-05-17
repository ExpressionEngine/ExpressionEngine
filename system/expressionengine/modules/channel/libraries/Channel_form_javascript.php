<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Channel_form_javascript
{
	private $js_path;
	
	/**
	 * Constructor
	 */
	public function __construct($params = array())
	{
		if (ee()->config->item('use_compressed_js') == 'n')
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

		ee()->lang->loadfile('jquery');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Combo Load
	 */
	public function combo_load()
	{
		ee()->load->library('javascript_loader');
		ee()->javascript_loader->combo_load();
		
		if (ee()->input->get('include_jquery') == 'y')
		{
			ee()->output->set_output(file_get_contents(PATH_JQUERY.'jquery.js')."\n\n".ee()->output->get_output());
		}
				
		if (ee()->input->get('use_live_url') == 'y')
		{
			ee()->output->append_output(ee()->safecracker->_url_title_js()."\n\n");
		}
		
		ee()->load->helper('smiley');
		
		ee()->output->append_output(((ee()->config->item('use_compressed_js') != 'n') ? str_replace(array("\n", "\t"), '', smiley_js('', '', FALSE)) : smiley_js('', '', FALSE))."\n\n");

		ee()->output->append_output(file_get_contents($this->js_path.'saef.js'));
		
		ee()->output->set_header('Content-Length: '.strlen(ee()->output->get_output()));
	}
}

/* End of file SC_Javascript.php */
/* Location: ./system/expressionengine/modules/safecracker/libraries/SC_Javascript.php */
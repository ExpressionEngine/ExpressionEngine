<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config = array(

				'entry' => array(
									array(
										'field'   => 'title',
										'label'   => 'lang:title',
										'rules'   => 'strip_tags|required'
									),
									array(
										'field'   => 'entry_date',
										'label'   => 'lang:entry_date',
										'rules'   => 'strip_tags|required'
									),
								)
				);


/* End of file form_validation.php */
/* Location: ./system/expressionengine/config/form_validation.php */
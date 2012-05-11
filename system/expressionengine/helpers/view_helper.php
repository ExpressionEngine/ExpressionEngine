<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function extend_template($which, $disable = array())
{
	get_instance()->view->extend('_templates/'.$which, $disable);
}

function extend_view($which, $disable = array())
{
	get_instance()->view->extend($which, $disable);
}

function disabled($which)
{
	return get_instance()->view->disabled($which);
}

function enabled($which)
{
	return ! get_instance()->view->disabled($which);
}

/* End of file  */
/* Location: 
 */
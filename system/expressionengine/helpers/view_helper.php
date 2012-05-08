<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
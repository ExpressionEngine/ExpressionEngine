<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
| 	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| The above route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = "some_class/some_function";
|
| The above route permits a specified class/function to be invoked instead of
| showing the 404 error template, in the event that the URI segments do not
| correlate to a valid controller.  Typically, if the URI segments do not
| correspond to a valid controller/function a 404 error is shown.  This
| routing item lets this behavior get overridden.
|
*/

$route['default_controller'] = "ee/index";
$route['404_override'] = "ee/index";

if (defined('REQ') && REQ == 'CP')
{
	$route['default_controller'] = "cp/homepage/index";
	$route['404_override'] = "cp/homepage/index";
}

/* End of file routes.php */
/* Location: ./system/expressionengine/config/routes.php */
<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Skeleton extends CP_Controller {

	protected $dependencies = NULL;
	protected $builder = NULL;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->cp->set_breadcrumb(BASE.AMP.'C=skeleton', lang('skeleton'));
		require APPPATH . '../EllisLab/ExpressionEngine/Core/Autoloader.php';
		$loader = new Autoloader();
		$loader->register();

		$di = new \EllisLab\ExpressionEngine\Core\Dependencies();

		// Conveinence links	
		$this->dependencies = $di;
		$this->builder = $this->dependencies->getModelBuilder();
	}

	public function export()
	{
		$skeleton_xml = '<?xml version="1.0" standalone="yes"?>' . "\n"
		. '<EESiteSkeleton>' . "\n";

		$field_groups = $this->builder->get('ChannelFieldGroup')->with('ChannelFieldStructures')->all();
		foreach ($field_groups as $field_group)
		{
			$skeleton_xml .= $field_group->toXml('ChannelFieldStructures');
		}

		$status_groups = $this->builder->get('StatusGroup')
			->with('Statuses')
			->filter('Statuses.status_id', '!=', 1)
			->filter('Statuses.status_id', '!=', 2)
			->all();
		foreach($status_groups as $status_group)
		{
			$skeleton_xml .= $status_group->toXml('Statuses');
		}

		$category_groups = $this->builder->get('CategoryGroup')->with('Categories')->all();
		foreach($category_groups as $category_group)
		{
			$skeleton_xml .= $category_group->toXml('Categories');
		}

		$channels = $this->builder->get('Channel')->all();
		foreach($channels as $channel)
		{
			$skeleton_xml .= $channel->toXml();
		}

		$template_groups = $this->builder->get('TemplateGroup')->with('Templates')->all();
		foreach($template_groups as $template_group)
		{
			$skeleton_xml .= $template_group->toXml('Templates');
		}

		$skeleton_xml .= '</EESiteSkeleton>' . "\n";

		header('Content-disposition: attachment; filename="skeleton.xml"');
		header('Content-type: "text/xml"; charset="utf8"');
		echo $skeleton_xml;
		exit();
	}

	public function import()
	{
		if ( ! empty ($_POST))
		{
			$file_contents = file_get_contents($_FILES['userfile']['tmp_name']);	

			// show form to get the file
			$skeleton_xml = new SimpleXMLElement($file_contents);

			foreach($skeleton_xml->model as $model_xml)
			{
				$model_class = (string) $model_xml['name'];
				$model = new $model_class($this->dependencies);
				$model->fromXml($model_xml);
			}

			return ee()->functions->redirect(BASE.AMP.'C=homepage');
		}
		else 
		{
			$this->view->title = lang('import_skeleton');
			$this->view->cp_page_title = lang('import_skeleton');
			$this->cp->set_breadcrumb(BASE.AMP.'C=skeleton'.AMP.'M=import', lang('import_skeleton'));

			$this->cp->render('skeleton/import');
		}
	}
}

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

	public function export_skeleton()
	{

		$skeleton_xml = '
		<?xml version="1.0" standalone="yes"?>' . "\n"
		. $depth . '<EESiteSkeleton>' . "\n";

		$field_groups = $this->builder->get('ChannelFieldGroup')->with('ChannelFieldStructures')->all();
		$skeleton_xml .= $depth . '<ChannelFieldGroup>' . "\n";
		foreach ($field_groups as $field_group)
		{
			$skeleton_xml .= $field_group->toXml('ChannelFieldStructures');
		}
		$skeleton_xml .= '</ChannelFieldGroup>' . "\n";

		$status_groups = $this->builder->get('StatusGroup')->with('Statuses')->all();
		$skeleton_xml .= '<StatusGroup>' . "\n";
		foreach($status_groups as $status_group)
		{
			$skeleton_xml .= $status_group->toXml('Statuses');
		}
		$skeleton_xml .= '</StatusGroup>' . "\n";

		$category_groups = $this->builder->get('CategoryGroup')->with('Categories')->all();
		$skeleton_xml .= '<CategoryGroup>' . "\n";
		foreach($category_groups as $category_group)
		{
			$skeleton_xml .= $category_group->toXml('Categories');
		}
		$skeleton_xml .= '</CategoryGroup>' . "\n";

		$channels = $this->builder->get('Channel')->all();
		$skeleton_xml .= '<Channel>' . "\n";
		foreach($channels as $channel)
		{
			$skeleton_xml .= $channel->toXml();
		}
		$skeleton_xml .= '</Channel>' . "\n";

		$template_groups = $this->builder->get('TemplateGroup')->with('Templates')->all();
		$skeleton_xml .= '<TemplateGroup>' . "\n";
		foreach($template_groups as $template_group)
		{
			$skeleton_xml .= $template_group->toXml('Templates');
		}
		$skeleton_xml .= '</TemplateGroup>' . "\n";

		$skeleton_xml .= '</EESiteSkeleton>' . "\n";

	echo '<pre>';	var_dump(htmlentities($skeleton_xml)); echo '</pre>';
		die();

	}

	public function import()
	{
		// show form to get the file

		$skeleton_xml = SimpleXML($file_contents);

		// Order matters here.
		$model_names = array(
			'ChannelFieldGroup', 
			'StatusGroup', 
			'CategoryGroup', 
			'Channel', 
			'TemplateGroup');
		foreach($model_names as $model_name)
		{
			$models = new Collection();
			foreach($skeleton_xml->{$model_name} as $model_xml)
			{
				$model = $this->builder->make($model_name);
				$model->fromXml($model_xml);
				$models[] = $model;
			}
			$models->save();
		}



		// redirect or show success message
	}
}

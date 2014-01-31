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
		$skeleton_xml = '
		<?xml version="1.0" standalone="yes"?>
		<EESiteSkeleton>';

		$field_groups = $this->builder->get('ChannelFieldGroup')->with('ChannelFieldStructures')->all();
		$skeleton_xml .= $field_groups->toXml();

		$status_groups = $this->builder->get('StatusGroup')->with('Statuses')->all();
		$skeleton_xml .= $status_groups->toXml();

		$category_groups = $this->builder->get('CategoryGroup')->with('Categories')->all();
		$skeleton_xml .= $category_groups->toXml();

		$channels = $this->builder->get('Channel')->all();
		$skeleton_xml .= $channels->toXml();

		$template_groups = $this->builder->get('TemplateGroup')->with('Templates')->all();
		$skeleton_xml .= $template_groups->toXml();

		$skeleton_xml = '</EESiteSkeleton>';

		header('Content-Type: text/xml');
		header('Content-Disposition: attachment; filename=skeleton.xml');
		header('Pragma: no-cache');
	}

	public function import()
	{
		$skeleton_xml = SimpleXML($file_contents);

		$field_groups = new Collection();
		foreach($skeleton_xml->field_groups as $field_group_xml)
		{
			$field_group = $this->builder->make('ChannelFieldGroup');
			$field_group->fromXml($field_group_xml);
			$field_groups[] = $field_group;
		}
		$field_groups->save();

		$status_groups = new Collection();
		foreach($skeleton_xml->status_groups as $status_group_xml)
		{
			$status_group = $this->builder->make('StatusGroup');
			$status_group->fromXml($status_group_xml);
			$status_groups[] = $status_group;
		}
		$status_groups->save();

		$category_groups = new Collection();
		foreach($skeleton_xml->category_groups as $category_group_xml)
		{
			$category_group = $this->builder->make('CategoryGroup');
			$category_group->fromXml($category_group_xml);
			$category_groups[] = $category_group;
		}
		$category_groups->save();

		$channels = new Collection();
		foreach($skeleton_xml->channels as $channel_xml)
		{
			$channel = $this->builder->make('Channel');
			$channel->fromXml($channel_xml);
			$channels[] = $channel;
		}
		$channels->save();

		$template_groups = new Collection();
		foreach($skeleton_xml->template_groups as $template_group_xml)
		{
			$template_group = $this->builder->make('TemplateGroup');
			$template_group->fromXml($template_group_xml);
			$template_groups[] = $template_group;
		}
		$template_groups->save();


		// redirect or show success message
	}
}

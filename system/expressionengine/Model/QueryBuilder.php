<?php namespace EllisLab\ExpressionEngine\Model;

class QueryBuilder {

	private static $instance;

	private static $model_namespace_aliases = array(
		'Template'       => '\EllisLab\ExpressionEngine\Model\Template\Template',
		'TemplateGroup'  => '\EllisLab\ExpressionEngine\Model\Template\TemplateGroup',
		'TemplateEntity' => '\EllisLab\ExpressionEngine\Model\Entity\TemplateEntity',
		'TemplateGroupEntity' => '\EllisLab\ExpressionEngine\Model\Entity\TemplateGroupEntity'
	);

	public static function getInstance()
	{
		if ( ! isset(static::$instance))
		{
			static::$instance = new static();
		}

		return static::$instance;
	}

	public function get($model_name, $ids = NULL)
	{
		$query = new Query($model_name);

		if (isset($ids))
		{
			if (is_array($ids))
			{
				$query->filter($model_name, 'IN', $ids);
			}
			else
			{
				$query->filter($model_name, $ids);
			}
		}

		return $query;
	}

	public function registerModel($name, $fully_qualified_name)
	{
		static::$model_namespace_aliases[$name] = $fully_qualified_name;
	}

	public static function getQualifiedClassName($model)
	{
		return static::$model_namespace_aliases[$model];
	}
}
<?php 
namespace EllisLab\ExpressionEngine\Model\Query;

use EllisLab\ExpressionEngine\Core\Dependencies;

class QueryBuilder {
	private $di = NULL;

	private static $model_namespace_aliases = array(
		'Template'       => '\EllisLab\ExpressionEngine\Model\Template\Template',
		'TemplateGroup'  => '\EllisLab\ExpressionEngine\Model\Template\TemplateGroup',
		'TemplateEntity' => '\EllisLab\ExpressionEngine\Model\Entity\TemplateEntity',
		'TemplateGroupEntity' => '\EllisLab\ExpressionEngine\Model\Entity\TemplateGroupEntity',
		'Channel' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Channel',
		'ChannelEntry' => '\EllisLab\ExpressionEngine\Module\Channel\Model\ChannelEntry',
		'ChannelEntity' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Entity\ChannelEntity',
		'ChannelTitleEntity' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Entity\ChannelTitleEntity',
		'ChannelDataEntity' => '\EllisLab\ExpressionEngine\Module\Channel\Model\Entity\ChannelDataEntity'
	);

	public function __construct(Dependencies $di)
	{
		$this->di = $di;
	}

	/**
	 * Retrieve a new query object for a given model.
	 *
	 * @param String  $model_name Name of the model
	 * @param  Mixed  $ids One or more primary key ids to prefilter the query
	 * @return Mixed  Query result in form of a model or collection
	 */
	public function get($model_name, $ids = NULL)
	{
		$query = new Query($this->di, $model_name);

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

	/**
	 * Register a model under a given name.
	 *
	 * @param String $name  Name to use when interacting with the query builder
	 * @param String $fully_qualified_name  Fully qualified class name of the model to use
	 * @return void
	 */
	public static function registerModel($name, $fully_qualified_name)
	{
		if (array_key_exists($name, static::$model_namespace_aliases))
		{
			throw new \OverflowException('Model name has already been registered: '. $model);
		}

		static::$model_namespace_aliases[$name] = $fully_qualified_name;
	}

	/**
	 * Register a model under a given name.
	 *
	 * @param String $name Name of the model
	 * @return String Fully qualified name of the class
	 */
	public static function getQualifiedClassName($model)
	{
		return static::$model_namespace_aliases[$model];
	}
}

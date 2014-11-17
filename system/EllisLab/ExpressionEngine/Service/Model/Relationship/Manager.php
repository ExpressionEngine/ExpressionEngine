<?php
namespace EllisLab\ExpressionEngine\Service\Model\Relationship;

class Manager {

	protected $factory;
	protected $relationships = array();

	public function __construct($factory)
	{
		$this->factory = $factory;
	}

	/**
	 *
	 */
	public function getRelationships($model_name)
	{
		if ( ! isset($this->relationships[$model_name]))
		{
			$related = $this->factory->getMetaData($model_name, 'relationships');
			$edges = array();

			foreach ($related as $name => $info)
			{
				$to_model = isset($info['model']) ? $info['model'] : $name;

				$edges[$name] = $this->createEdge($model_name, $to_model, $name, $info);
			}

			$this->relationships[$model_name] = $edges;
		}

		return $this->relationships[$model_name];
	}

	/**
	 *
	 */
	protected function createEdge($from_model, $to_model, $name, $info)
	{
		$type = $info['type'];

		$class = implode('', array_map('ucfirst', explode('_', $type)));
		$class = 'EllisLab\\ExpressionEngine\\Service\\Model\\Relationship\\Types\\'.$class;

		return new $class($this->factory, $from_model, $to_model, $name, $info);
	}
}
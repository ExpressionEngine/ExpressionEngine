<?php
namespace EllisLab\ExpressionEngine\Model\Relationship\Types;

class OneToMany extends AbstractRelationship {

	public $type	= 'one_to_many';
	public $inverse	= 'many_to_one';

	public function connect($from_instance, $to_collection)
	{
		foreach ($to_collection as $model)
		{
			$model->{$this->to_key} = $from_instance->{$this->key};
		}
	}

	// default: primary key of the one side (e.g group_id for template groups and templates)
	protected function normalizeKeys()
	{
		$from = $this->from;

		$this->is_parent = TRUE;
		$this->key = $this->key ?: $from::getMetaData('primary_key');
		$this->to_key = $this->to_key ?: $this->key;
	}

}
<?php
namespace EllisLab\ExpressionEngine\Model\Relationship\Types;

class ManyToOne extends AbstractRelationship {

	public $type	= 'many_to_one';
	public $inverse	= 'one_to_many';

	public function connect($from_instance, $to_model)
	{
		$from_instance->{$this->key} = $to_model->{$this->to_key};
	}

	// default: same as one_to_many, but looked up in the other direction
	protected function normalizeKeys()
	{
		$to_class = $this->to_class;

		$this->is_parent = FALSE;
		$this->to_key = $this->to_key ?: $to_class::getMetaData('primary_key');
		$this->key = $this->key ?: $this->to_key;
	}

}
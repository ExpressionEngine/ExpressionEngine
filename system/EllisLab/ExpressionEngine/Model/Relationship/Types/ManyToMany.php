<?php
namespace EllisLab\ExpressionEngine\Model\Relationship\Types;

class ManyToMany extends AbstractRelationship {

	public $type	= 'many_to_many';
	public $inverse	= 'many_to_many';

	public function connect($from_instance, $to_collection)
	{
		// nada
	}

	// default: both primary keys on pivot
	protected function normalizeKeys()
	{
		$from = $this->from;
		$to_class = $this->to_class;

		$this->is_parent = FALSE;
		$this->key = $this->key ?: $from::getMetaData('primary_key');
		$this->to_key = $this->to_key ?: $to_class::getMetaData('primary_key');
	}

}
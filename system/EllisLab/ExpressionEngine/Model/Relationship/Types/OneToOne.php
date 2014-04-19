<?php
namespace EllisLab\ExpressionEngine\Model\Relationship\Types;

class OneToOne extends AbstractRelationship {

	public $type	= 'one_to_one';
	public $inverse	= 'one_to_one';

	public function connect($from_instance, $to_model)
	{
		if ($this->key != $from_instance::getMetaData('primary_key'))
		{
			$from_instance->{$this->key} = $to_model->{$this->to_key};
		}

		$to_class = $this->to_class;

		if ($this->to_key != $to_class::getMetaData('primary_key'))
		{
			$to_model->{$this->to_key} = $from_instance->{$this->key};
		}
	}

	// default: opposite primary key in list or declared
	protected function normalizeKeys()
	{
		$from = $this->from;
		$to_class = $this->to_class;

		if ( ! $this->key && ! $this->to_key)
		{
			if (property_exists($to_class, $from::getMetaData('primary_key')))
			{
				$this->key	  = $from::getMetaData('primary_key');
				$this->to_key = $this->key;
				$this->is_parent = TRUE;
			}
			else if (property_exists($from, $to_class::getMetaData('primary_key')))
			{
				$this->key	  = $to_class::getMetaData('primary_key');
				$this->to_key = $this->key;
				$this->is_parent = FALSE;
			}
		}
		else
		{
			$this->key	  = $this->key ?: $to_class::getMetaData('primary_key');
			$this->to_key = $this->to_key ?: $from::getMetaData('primary_key');
			$this->is_parent = ($this->to_key == $from::getMetaData('primary_key'));
		}
	}

}
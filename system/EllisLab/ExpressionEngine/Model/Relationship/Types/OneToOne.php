<?php
namespace EllisLab\ExpressionEngine\Model\Relationship\Types;

class OneToOne extends AbstractRelationship {

	public $type	= 'one_to_one';
	public $inverse	= 'one_to_one';

	/**
	 * Set the related ids to correclty connect the models.
	 *
	 * For oneToOne, either side can be the parent, or they could both have
	 * each other's keys, so we do some extra work to compare names
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
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

	/**
	 * Figure out optional key settings as well as the parent.
	 *
	 * The parent must always be a single item, which is ambiguous here, so we
	 * use the key information to discern the parent. One side must have a reference
	 * to the other side. The key containing the reference is the child. If both
	 * sides contain the reference, the developer must specify is_parent on one
	 * of them. By default, the key used matches the primary key it references.
	 *
	 * @return void
	 */
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
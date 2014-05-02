<?php
namespace EllisLab\ExpressionEngine\Model\Relationship\Types;

class ManyToMany extends AbstractRelationship {

	public $type	= 'many_to_many';
	public $inverse	= 'many_to_many';

	/**
	 * Set the related ids to correclty connect the models.
	 *
	 * For many to many this will mean setting the pivot id on both tables,
	 * which we cannot currently do here.
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
	public function connect($from_instance, $to_collection)
	{
		// nada
	}

	/**
	 * Figure out optional key settings as well as the parent.
	 *
	 * Since parents must always be single items, a ManyToMany relationship has
	 * no distinct parents or children. Instead, both model's primary keys are
	 * stored multiple times on a pivot table. Easy stuff.
	 *
	 * @return void
	 */
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
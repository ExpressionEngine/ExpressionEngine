<?php
namespace EllisLab\ExpressionEngine\Model\Relationship\Types;

class ManyToOne extends AbstractRelationship {

	public $type	= 'many_to_one';
	public $inverse	= 'one_to_many';

	/**
	 * Set the related ids to correclty connect the models.
	 *
	 * For manyToOne, this means setting a field on from_instance, since the
	 * other side can't logically have a column for each of its related models.
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
	public function connect($from_instance, $to_model)
	{
		$from_instance->{$this->key} = $to_model->{$this->to_key};
	}

	/**
	 * Figure out optional key settings as well as the parent.
	 *
	 * The parent must always be a single item, so this can never be the parent.
	 * For keys, this side will always contain a reference to the parent, as the
	 * parent will not have a separate column for each child item. By default,
	 * the key used matches the primary key it references.
	 *
	 * @return void
	 */
	protected function normalizeKeys()
	{
		$to_class = $this->to_class;

		$this->is_parent = FALSE;
		$this->to_key = $this->to_key ?: $to_class::getMetaData('primary_key');
		$this->key = $this->key ?: $this->to_key;
	}

}
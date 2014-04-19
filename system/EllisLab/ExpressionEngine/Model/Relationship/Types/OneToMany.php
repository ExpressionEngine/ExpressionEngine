<?php
namespace EllisLab\ExpressionEngine\Model\Relationship\Types;

class OneToMany extends AbstractRelationship {

	public $type	= 'one_to_many';
	public $inverse	= 'many_to_one';

	/**
	 * Set the related ids to correclty connect the models.
	 *
	 * For OneToMany, this means setting a field on each item in $to_collection,
	 * since the other side can't logically have a column for each of its
	 * related models.
	 *
	 * @param Model  $from_instance  Model that the data is being set on.
	 * @param Model  $to_model_or_collecion  Related data that is being set.
	 * @return void
	 */
	public function connect($from_instance, $to_collection)
	{
		foreach ($to_collection as $model)
		{
			$model->{$this->to_key} = $from_instance->{$this->key};
		}
	}

	/**
	 * Figure out optional key settings as well as the parent.
	 *
	 * The parent must always be a single item, so this must always be the parent.
	 * For keys, this side can never contain a reference to each child, so the
	 * children must contain the reference to this side. By default, the key used
	 * matches the primary key it references.
	 *
	 * @return void
	 */
	protected function normalizeKeys()
	{
		$from = $this->from;

		$this->is_parent = TRUE;
		$this->key = $this->key ?: $from::getMetaData('primary_key');
		$this->to_key = $this->to_key ?: $this->key;
	}

}
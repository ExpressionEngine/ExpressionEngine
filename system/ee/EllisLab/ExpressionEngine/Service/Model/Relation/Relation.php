<?php

namespace EllisLab\ExpressionEngine\Service\Model\Relation;

use EllisLab\ExpressionEngine\Service\Model\Model;
use EllisLab\ExpressionEngine\Service\Model\MetaDataReader;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Relation
 *
 * Relations describe how two model classes are related. For distinct
 * instance connections, @see Associations.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
abstract class Relation {

	protected $from;
	protected $to;
	protected $name;
	protected $is_weak;
	protected $inverse;
	protected $inverse_info;

	protected $to_table;
	protected $from_table;

	protected $from_key;
	protected $to_key;
	protected $key_tuple;

	protected $from_primary_key;
	protected $to_primary_key;

	protected $datastore;

	public function __construct(MetaDataReader $from, MetaDataReader $to, $name, $options)
	{
		$this->from = $from;
		$this->to = $to;
		$this->name = $name;

		$this->is_weak = FALSE;
		$this->processOptions($options);
	}

	/**
	 *
	 */
	abstract public function createAssociation(Model $source);

	/**
	 *
	 */
	abstract public function fillLinkIds(Model $source, Model $target);


	/**
	 *
	 */
	abstract public function linkIds(Model $source, Model $target);

	/**
	 *
	 */
	abstract public function unlinkIds(Model $source, Model $target);

	/**
	 *
	 */
	abstract public function markLinkAsClean(Model $source, Model $target);

	/**
	 *
	 */
	abstract public function canSaveAcross();

	/**
	 * Insert a database link between the model and targets
	 */
	abstract public function insert(Model $source, $targets);

	/**
	 * Drop the database link between the model and targets, potentially
	 * triggering a soft delete.
	 */
	abstract public function drop(Model $source, $targets = NULL);

	/**
	 * Set the relation. Should do the minimum viable sql modifications required
	 * to maintain consistency.
	 */
	abstract public function set(Model $source, $targets);


	/**
	 *
	 */
	abstract protected function deriveKeys();

	/**
	 * Reverse this relation. This allows us to set both sides of an
	 * association when those get set.
	 *
	 * @return Relation Inverse of this relation or NULL
	 */
	public function getInverse()
	{
		if ( ! isset($this->inverse))
		{
			$this->inverse = $this->datastore->getInverseRelation($this);
		}

		return $this->inverse;
	}

	/**
	 *
	 */
	public function modifyEagerQuery($query, $from_alias, $to_alias)
	{
		list($from, $to) = $this->getKeys();

		$query->join(
			"{$this->to_table} AS {$to_alias}_{$this->to_table}",
			"{$to_alias}_{$this->to_table}.{$to} = {$from_alias}_{$this->from_table}.{$from}",
			'LEFT'
		);
	}

	/**
	 *
	 */
	public function modifyLazyQuery($query, $source, $to_alias)
	{
		list($from, $to) = $this->getKeys();

		$query->where("{$to_alias}_{$this->to_table}.{$to}", $source->$from);
	}

	/**
	 *
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 *
	 */
	public function getSourceModel()
	{
		return $this->from->getName();
	}

	/**
	 *
	 */
	public function getTargetModel()
	{
		return $this->to->getName();
	}

	public function isWeak()
	{
		return $this->is_weak;
	}

	/**
	 *
	 */
	public function setDataStore($datastore)
	{
		$this->datastore = $datastore;
	}

	/**
	 *
	 */
	public function getKeys()
	{
		return $this->key_tuple;
	}

	public function getPivot()
	{
		return array();
	}

	public function getInverseOptions()
	{
		return array(
			'type' => $this->inverse_info['type'],
			'name' => $this->inverse_info['name'],

			'model' => $this->getSourceModel(),

			'from_key' => $this->to_key,
			'from_primary_key' => $this->to_primary_key,

			'to_key' => $this->from_key,
			'to_primary_key' => $this->from_primary_key,

			'weak' => $this->is_weak
		);
	}

	/**
	 *
	 */
	protected function processOptions($options)
	{
		if (isset($options['weak']))
		{
			$this->is_weak = (bool) $options['weak'];
		}

		if (isset($options['from_key']))
		{
			$this->from_key = $options['from_key'];
		}

		if (isset($options['to_key']))
		{
			$this->to_key = $options['to_key'];
		}

		if (isset($options['inverse']))
		{
			$this->inverse_info = $options['inverse'];
		}

		$this->from_primary_key = $options['from_primary_key'];
		$this->to_primary_key = $options['to_primary_key'];

		$this->key_tuple = $this->deriveKeys();
		list($from, $to) = $this->key_tuple;

		$this->from_key = $from;
		$this->to_key = $to;

		$this->from_table = $this->from->getTableForField($from);
		$this->to_table = $this->to->getTableForField($to);

		if ( ! $this->from_table)
		{
			throw new \Exception('Cannot find table for field ' . $from . ' on '. $this->from->getClass());
		}

		if ( ! $this->to_table)
		{
			throw new \Exception('Cannot find table for field '.$to.' on '.$this->to->getClass(). ' from '.$this->from->getClass());
		}
	}
}

// EOF

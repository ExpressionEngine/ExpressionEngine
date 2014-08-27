<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\AliasServiceInterface;
use EllisLab\ExpressionEngine\Service\Model\Collection;
use EllisLab\ExpressionEngine\Service\Model\Factory as ModelFactory;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Query Result Class
 *
 * A class containing the result of a database query and providing behavior
 * allowing that data to be parsed into models.
 *
 * @package		ExpressionEngine
 * @subpackage	Model
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class QueryResult {

	private $factory;
	private $alias_service;

	private $db_result;
	private $model_index = array();

	public function __construct(ModelFactory $factory, AliasServiceInterface $alias_service, array $result_array)
	{
		$this->factory = $factory;
		$this->alias_service = $alias_service;

		$this->db_result = $result_array;
	}

	/**
	 * Get the first result
	 */
	public function first()
	{
		if ( ! count($this->db_result))
		{
			return NULL;
		}

		$models = $this->parseResult();

		return $models[0];
	}

	/**
	 * Get all results as a collection
	 */
	public function collection()
	{
		return new Collection($this->parseResult());
	}


	protected function parseResult()
	{
		$database_result = $this->db_result;

		// Each row holds field=>value data for the full joined query's tree.
		// In order to take this flat row data and reconstruct it into a tree,
		// the field names of each field=>value pair have been aliased with the
		// path to the correct node (in ids), and the model to which the field
		// belongs.  Each field name looks like this: path__model__fieldName
		//
		// Where path, model and fieldName may have single underscores in them.
		// The path is a series of underscore separated integers corresponding
		// to the unique ids of nodes in this query's relationship tree.  The
		// tree might look something like this:
		//
		// 				ChannelEntry(1)
		// 			/			|			\
		// 	Channel (2)		Author (3)		Categories (4)
		//						|				|
		// 					MemberGroup (5)	CategoryGroup (6)
		//
		// 	So a field in the CategoryGroup model would be aliased like so:
		//
		// 	1_4_6__CategoryGroup__group_id
		//
		// 	A field in the Author relationship (Member model) might be
		// 	aliased:
		//
		// 	1_3__Member__member_id
		//
		// 	This will allow us to create the models we've pulled and
		// 	correctly reconstruct the tree.
		$results = array();
		$result_index = array();

		foreach ($database_result as $row)
		{
			$row_data = array();

			foreach ($row as $name => $value)
			{
				list($path, $relationship_name, $model_name, $field_name) = explode('__', $name);

				if ( ! isset($row_data[$path]))
				{
					$row_data[$path] = array(
						'__model_name' => $model_name,
						'__relationship_name' => $relationship_name
					);
				}

				$row_data[$path][$field_name] = $value;
			}

			//echo 'Processing Row: <pre>'; var_dump($row_data); //echo '</pre>';

			foreach ($row_data as $path => $model_data)
			{
				// If this is an empty model that happened to have been grabbed due to the join,
				// move on and don't do anything.
				$model_name = $model_data['__model_name'];
				$model_class = $this->alias_service->getRegisteredClass($model_name);
				$primary_key_name = $model_class::getMetaData('primary_key');

				if ($row_data[$path][$primary_key_name] === NULL)
				{
					unset($row_data[$path]);
					continue;
				}

				$primary_key = $model_data[$primary_key_name];

				if (isset($this->model_index[$model_name][$primary_key]))
				{
					$model = $this->model_index[$model_name][$primary_key];
				}
				else
				{
					$model = $this->createResultModel($model_data);
				}

				if ($this->isRootModel($path))
				{
					if ( ! isset($result_index[$primary_key]))
					{
						$results[] = $model;
						$result_index[$primary_key] = TRUE;
					}

					continue;
				}

				$parent_model = $this->findModelParent($row_data, $path);

				if ($parent_model === NULL)
				{
					throw new \Exception('Missing model parent!');
				}

				$relationship_name = $model_data['__relationship_name'];

				// Reverse the relationship so we can fill in both sides
				// TODO we should not do this if $parent_model is really a child!
				$reverse = $parent_model->getRelationshipInfo($relationship_name)->getInverseOn($model);

				if ($reverse)
				{
					if ( ! $model->hasRelated($reverse->name))
					{
						$model->addRelated($reverse->name, $parent_model);
					}
				}

				if ( ! $parent_model->hasRelated($relationship_name, $primary_key))
				{
					$parent_model->addRelated($relationship_name, $model);
				}
			}
		}

		return $results;
	}


	/**
	 *
	 */
	protected function createResultModel($model_data)
	{
		$model_name = $model_data['__model_name'];

		$model = $this->factory->make($model_name);
		$model->populateFromDatabase($model_data);

		$primary_key_name = $model::getMetaData('primary_key');
		$primary_key = $model_data[$primary_key_name];

		if ( ! isset($this->model_index[$model_name]))
		{
			$this->model_index[$model_name] = array();
		}

		$this->model_index[$model_name][$primary_key] = $model;

		return $this->model_index[$model_name][$primary_key];
	}

	protected function findModelParent($path_data, $child_path)
	{
		$path = substr($child_path, 0, strrpos($child_path, '_'));

		$model_data = $path_data[$path];

		$model_name = $model_data['__model_name'];
		$model_class = $this->alias_service->getRegisteredClass($model_name);

		$primary_key_name = $model_class::getMetaData('primary_key');
		$primary_key = $model_data[$primary_key_name];

		if (isset($this->model_index[$model_name][$primary_key]))
		{
			return $this->model_index[$model_name][$primary_key];
		}

		throw new \Exception('Model parent has not been created yet for child path "' . $child_path . '" and model "' . $model_name . '"');
	}

	/**
	 * Determine if this is a Root Model
	 *
	 * Is this a root model?  One of the ones we're get()ing.
	 *
	 * @param 	string	$path	The path to the model's node in the
	 * 		relationship tree.
	 *
	 * @return	boolean	TRUE if this is a root model, FALSE otherwise.
	 */
	protected function isRootModel($path)
	{
		// If it's an integer, then it's a
		// root node, because it doesn't have
		// any children.
		return is_int($path);
	}

}

<?php
namespace EllisLab\ExpressionEngine\Model;

use EllisLab\ExpressionEngine\Model\Model as Model;
use EllisLab\ExpressionEngine\Model\Interfaces\Content\Content as Content;

// TODO This desperately needs a better name, but I got nothing!
abstract class FieldDataContentModel extends Model implements Content {

	protected $fields = array();

	/**
	 *
	 */
	public function getFields()
	{
		if ( empty($this->fields) && $this->getId() !== NULL)
		{
			$field_structures = $this->getContentStructure()
				->getFieldStructures();

			$field_content_class = QueryBuilder::getQualifiedClassName(
				static::getMetaData('field_content_class')
			);

			foreach ($field_structures as $field_structure)
			{
				$fields[$field_structure->field_id] = new $field_content_class(
					$field_sturcture
					$this->entities[static::getMetaData('field_content_entity')],
				);
			}
		}

		return $this->fields;	
	}
}

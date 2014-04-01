<?php
namespace EllisLab\ExpressionEngine\Model\Serializers;

class XmlSerializers implements SerializerInterface {

	public function serialize()
	{
		$cascade = func_get_args();

		$model_xml = '<model name="' . get_class($this) . '">' . "\n";

		foreach(get_object_vars($this) as $property => $value)
		{
			// Ignore meta properties.
			if (strpos($property, '_') === 0)
			{
				continue;
			}

			$model_xml .= '<property name="' . $property . '">' . $value . '</property>' . "\n";
		}

		if ( empty ($cascade))
		{
			$cascade = self::getMetaData('cascade');
		}
		if ( ! empty($cascade))
		{
			foreach($cascade as $relationship_name)
			{
				if (is_array($relationship_name))
				{
					foreach ($relationship_name as $from_relationship => $to_relationship)
					{
						$method = 'get' . $from_relationship;
						$models = $this->$method();

						if (count ($models) > 0)
						{
							$model_xml .= '<related_models relationship="' . $from_relationship . '">' . "\n";
							foreach ($models as $model)
							{
								if (is_array($to_relationship))
								{
									$model_xml .= $model->toXml($to_relationship);
								}
								else
								{
									$relationship_method = 'get' . $to_relationship;
									$to_models = $model->$relationship_method();

									if (count($to_models) > 0)
									{
										$model_xml .= '<related_models relationship="' . $to_relationship . '"> ' . "\n";
										foreach ($to_models as $to_model)
										{
											$to_model->toXml();
										}
										$model_xml .= '</related_models>' . "\n";
									}
								}
							}
							$model_xml .= '</related_models>' . "\n";
						}
					}
				}
				else
				{
					$relationship_method = 'get' . $relationship_name;
					$models = $this->$relationship_method();

					if (count($models) > 0)
					{
						$model_xml .= '<related_models relationship="' . $relationship_name . '">' . "\n";
						foreach ($models as $model)
						{
							$model_xml .= $model->toXml();
						}
						$model_xml .= '</related_models>' . "\n";
					}
				}
			}
		}
		$model_xml .= '</model>' . "\n";
		return $model_xml;
	}

	public function unserialize($model, $data)
	{
		foreach ($model_xml->property as $property)
		{
			$name = (string) $property['name'];
			$model->{$name} = (string) $property;
			$model->setDirty($name);
		}

		foreach($model_xml->related_models as $related_models_xml)
		{
			$models = new Collection();
			foreach($related_models_xml as $related_model_xml)
			{
				$model_class = (string) $related_model_xml['name'];
				$model = $this->builder->make($model_class);
				$model->fromXml($related_model_xml);
				$models[] = $model;
			}
			$model->setRelated((string) $related_models_xml['relationship'], $models);
		}

		$model->restore();
	}
}
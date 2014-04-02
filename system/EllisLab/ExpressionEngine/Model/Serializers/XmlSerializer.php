<?php
namespace EllisLab\ExpressionEngine\Model\Serializers;

use \SimpleXMLElement;

class XmlSerializer implements SerializerInterface {

	public function serialize($model, $cascade = array())
	{
		$data = call_user_func_array(array($model, 'toArray'), $cascade);

		$xml = new SimpleXMLElement("<?xml version=\"1.0\"?><model></model>");

		$xml->addAttribute('class', get_class($model));

		// we'll do these separately
		$related = $data['related_models'];
		unset($data['related_models']);

		foreach ($data as $key => $value)
		{
			$xml->addChild($key, htmlentities($value));
		}

		$this->relatedToXml($xml, $related);

		// xml->asXML() results in output without whitespace. We work
		// around that by having the dom library clean it up. Silly php.
		$dom = dom_import_simplexml($xml)->ownerDocument;
		$dom->formatOutput = TRUE;

		return $dom->saveXML();
	}

	public function unserialize($model, $data)
	{
		$xml = new SimpleXMLElement($data);
		$xml_attributes = $xml->attributes();

		if ($xml_attributes != get_class($model))
		{
			throw new \Exception('Cannot unserialize: Data was exported for a different model.');
		}

		return $this->relatedFromXml($xml);
	}

	protected function relatedFromXml($xml, $related = FALSE)
	{
		$result = array();

		foreach ($xml->children() as $element)
		{
			$key = $element->getName();

			if ($key == 'related_models')
			{
				$result['related_models'] = $this->relatedFromXml($element, TRUE);
			}
			elseif ($related && $key == 'relationship')
			{
				$related_name = (string) $element['name'];

				foreach ($element->children() as $child_xml)
				{
					$result[$related_name][] = $this->relatedFromXml($child_xml);
				}
			}
			elseif (count($element->children()))
			{
				$result[$key] = $this->relatedFromXml($element);
			}
			else
			{
				$result[$key] = (string) $element;
			}
		}

		return $result;
	}

	protected function relatedToXml($xml, $related)
	{
		if ( ! count($related))
		{
			return;
		}

		$related_models_xml = $xml->addChild('related_models');

		foreach ($related as $relationship_name => $related_models)
		{
			$relationship_xml = $related_models_xml->addChild('relationship');
			$relationship_xml->addAttribute('name', $relationship_name);

			if ( ! isset($related_models[0]))
			{
				$related_models = array($related_models);
			}

			foreach ($related_models as $model)
			{
				$related_model_xml = $relationship_xml->addChild('model');

				$related_models = $model['related_models'];
				unset($model['related_models']);

				foreach ($model as $key => $value)
				{
					$related_model_xml->addChild($key, htmlentities($value));
				}

				$this->relatedToXml($xml, $related_models);
			}
		}
	}
}
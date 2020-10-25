<?php

namespace ExpressionEngine\Addons\Queue\Services;

use DateTime;
use DateTimeImmutable;
use ExpressionEngine\Addons\Queue\Exceptions\QueueException;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;
use stdClass;

/**
 * This class can serialize/unserialize a complete PHP object graph to a
 * JSON string representation, using human-readable, VCS-friendly formatting.
 * This is specifically used by the Queue system in order to store
 * Objects created at a point in time in the database for running aas
 * part of the queue.
 */
class SerializerService
{
	/**
	 * @var string ISO-8601 UTC date/time format
	 */
	const DATETIME_FORMAT = "Y-m-d\TH:i:s\Z";

	/**
	 * @var (ReflectionProperty[])[] Internal cache for class-reflections
	 */
	static $_reflections = [];

	/**
	 * @var string hash token used to identify PHP classes
	 */
	const TYPE = '#type';

	/**
	 * @var string hash token previously used to identify PHP hashes (arrays with keys)
	 */
	const HASH = '#hash';

	/**
	 * @var string standard class name
	 */
	const STD_CLASS = 'stdClass';

	/**
	 * @var string one level of indentation
	 */
	public $indentation = '';

	/**
	 * @var string newline character(s)
	 */
	public $newline = '';

	/**
	 * @var string padding character(s) after ":" in JSON objects
	 */
	public $padding = '';

	/**
	 * @var bool true, if private properties should be skipped
	 *
	 * @see skipPrivateProperties()
	 * @see _getClassProperties()
	 */
	private $skip_private = false;

	/**
	 * @var callable[] map where fully-qualified class-name => serialization function
	 */
	private $serializers = [];

	/**
	 * @var callable[] map where fully-qualified class-name => serialization function
	 */
	private $unserializers = [];

	/**
	 * class constructor
	 */
	public function __construct($pretty = false)
	{
		if ($pretty) {
			$this->indentation = '  ';
			$this->newline = "\n";
			$this->padding = ' ';
		}

		$this->defineSerialization(
			'DateTime',
			[
				$this,
				"_serializeDateTime",
			],
			[
				$this,
				"_unserializeDateTime",
			]
		);

		$this->defineSerialization(
			'DateTimeImmutable',
			[
				$this,
				"_serializeDateTime",
			],
			[
				$this,
				"_unserializeDateTime",
			]
		);
	}

	/**
	 * Serialize a given PHP value/array/object-graph to a JSON representation.
	 * @param mixed $value The value, array, or object-graph to be serialized.
	 * @return string JSON serialized representation
	 */
	public function serialize($value)
	{
		return $this->_serialize($value, 0);
	}

	/**
	 * Unserialize a value/array/object-graph from a JSON string representation.
	 * @param string $string JSON serialized value/array/object representation
	 * @return mixed The unserialized value, array or object-graph.
	 */
	public function unserialize($incoming)
	{

		$data = is_object($incoming)
				? (array) $incoming
				: json_decode($string, true);

		return $this->_unserialize($data);
	}

	/**
	 * Enable (or disable) serialization of private properties.
	 * @return void
	 */
	public function skipPrivateProperties($skip = true)
	{
		if ($this->skip_private !== $skip) {
			$this->skip_private = $skip;
			self::$_reflections = [];
		}
	}

	/**
	 * Registers a pair of custom un/serialization functions for a given class
	 * @param string   $type        fully-qualified class-name
	 * @param callable $serialize   serialization function; takes an object and returns serialized data
	 * @param callable $unserialize unserialization function; takes serialized data and returns an object
	 * @return void
	 */
	public function defineSerialization($type, $serialize, $unserialize)
	{
		$this->serializers[$type] = $serialize;
		$this->unserializers[$type] = $unserialize;
	}

	/**
	 * Serializes an individual object/array/hash/value, returning a JSON string representation
	 * @param mixed $value  the value to serialize
	 * @param int   $indent indentation level
	 * @return string JSON serialized value
	 */
	protected function _serialize($value, $indent = 0)
	{
		if (is_object($value)) {
			if (get_class($value) === self::STD_CLASS) {
				return $this->_serializeStdClass($value, $indent);
			}

			return $this->_serializeObject($value, $indent);
		}

		if (is_array($value)) {
			if (array_keys($value) === array_keys(array_values($value))) {
				return $this->_serializeArray($value, $indent);
			} else {
				return $this->_serializeHash($value, $indent);
			}
		}

		if (is_string($value)) {
			if (preg_match('//u', $value) !== 1) {
				throw new QueueException("Malformed UTF-8 characters, possibly incorrectly encoded");
			}

			return json_encode($value);
		}

		if (is_scalar($value)) {
			return json_encode($value);
		}

		return 'null';

	}

	/**
	 * Serializes a complete object with aggregates, returning a JSON string representation.
	 * @param object $object object
	 * @param int    $indent indentation level
	 * @return string JSON object representation
	 */
	protected function _serializeObject($object, $indent)
	{
		$type = get_class($object);

		if (isset($this->serializers[$type])) {
			return $this->_serialize(
				call_user_func($this->serializers[$type], $object),
				$indent
			);
		}

		$whitespace = $this->newline . str_repeat($this->indentation, $indent + 1);

		$string = '{' . $whitespace . '"' . self::TYPE . '":' . $this->padding . json_encode($type);

		foreach ($this->_getClassProperties($type) as $name => $prop) {
			$string .= ','
				. $whitespace
				. json_encode($name)
				. ':'
				. $this->padding
				. $this->_serialize($prop->getValue($object), $indent + 1);
		}

		$string .= $this->newline . str_repeat($this->indentation, $indent) . '}';

		return $string;
	}

	/**
	 * Serializes a "strict" array (base-0 integer keys) returning a JSON string representation.
	 * @param array $array  array
	 * @param int   $indent indentation level
	 * @return string JSON array representation
	 */
	protected function _serializeArray($array, $indent)
	{
		$string = '[';

		$last_key = count($array) - 1;

		foreach ($array as $key => $item) {
			$string .= $this->_serialize($item, $indent)
						. ($key === $last_key ? '' : ',');
		}

		$string .= ']';

		return $string;
	}

	/**
	 * Serializes a "wild" array (e.g. a "hash" array with mixed keys) returning a JSON string representation.
	 * @param array $hash   hash array
	 * @param int   $indent indentation level
	 * @return string JSON hash representation
	 */
	protected function _serializeHash($hash, $indent)
	{
		$whitespace = $this->newline . str_repeat($this->indentation, $indent + 1);

		$string = '{';

		$comma = '';

		foreach ($hash as $key => $item) {
			$string .= $comma
				. $whitespace
				. json_encode((string) $key)
				. ':'
				. $this->padding
				. $this->_serialize($item, $indent + 1);

			$comma = ',';
		}

		$string .= $this->newline . str_repeat($this->indentation, $indent) . '}';

		return $string;
	}

	/**
	 * Serializes a stdClass object returning a JSON string representation.
	 * @param stdClass $value  stdClass object
	 * @param int      $indent indentation level
	 * @return string JSON object representation
	 */
	protected function _serializeStdClass($value, $indent)
	{
		$array = (array) $value;

		$array[self::TYPE] = self::STD_CLASS;

		return $this->_serializeHash($array, $indent);
	}

	/**
	 * Unserialize an individual object/array/hash/value from a hash of properties.
	 * @param array $data hashed value representation
	 * @return mixed unserialized value
	 */
	protected function _unserialize($data)
	{
		if (! is_array($data)) {
			return $data; // scalar value is fully unserialized
		}
		if (array_key_exists(self::TYPE, $data)) {

			if ($data[self::TYPE] === self::HASH) {
				// remove legacy hash tag from JSON serialized with version 1.x
				unset($data[self::TYPE]);
				return $this->_unserializeArray($data);
			}

			return $this->_unserializeObject($data);
		}

		return $this->_unserializeArray($data);
	}

	/**
	 * Unserialize an individual object from a hash of properties.
	 * @param array $data hash of object properties
	 * @return object unserialized object
	 */
	protected function _unserializeObject($data)
	{
		$type = $data[self::TYPE];

		if (isset($this->unserializers[$type])) {
			return $this->_unserialize(call_user_func($this->unserializers[$type], $data));
		}

		if ($type === self::STD_CLASS) {
			unset($data[self::TYPE]);
			return (object) $this->_unserializeArray($data);
		}

		$object = unserialize('O:' . strlen($type) . ':"' . $type . '":0:{}');

		foreach ($this->_getClassProperties($type) as $name => $prop) {
			if (array_key_exists($name, $data)) {
				$value = $this->_unserialize($data[$name]);
				$prop->setValue($object, $value);
			}
		}

		return $object;
	}

	/**
	 * Unserialize a hash/array.
	 * @param array $data hash/array
	 * @return array unserialized hash/array
	 */
	protected function _unserializeArray($data)
	{
		$array = [];

		foreach ($data as $key => $value) {
			$array[$key] = $this->_unserialize($value);
		}

		return $array;
	}

	/**
	 * Obtain a (cached) array of property-reflections, with all properties made accessible.
	 * @param string $type fully-qualified class name
	 * @return ReflectionProperty[] map where property-name => accessible ReflectionProperty instance
	 */
	protected function _getClassProperties($type)
	{

		if (! isset(self::$_reflections[$type])) {

			// Check if we loaded the class
			try {
				$class = new ReflectionClass($type);
			} catch(\Exception $e) {
				throw new QueueException("Error Processing Request", 1);
				return;
			}

			$props = [];

			do {
				foreach ($class->getProperties() as $prop) {
					// Omit static property
					if ($prop->isStatic()) {
						continue;
					}

					// Skip private property
					if ($this->skip_private && $prop->isPrivate()) {
						continue;
					}

					$prop->setAccessible(true);

					$name = ($prop->isPrivate() && $prop->class !== $type)
								? "{$prop->class}#" . $prop->getName()
								: $prop->getName();

					$props[$name] = $prop;
				}

			} while ($class = $class->getParentClass());

			self::$_reflections[$type] = $props;
		}

		return self::$_reflections[$type];
	}

	/**
	 * @param DateTime|DateTimeImmutable $datetime
	 * @return array
	 */
	protected function _serializeDateTime($datetime)
	{
		$utc = date_create_from_format("U", $datetime->format("U"), timezone_open("UTC"));
		
		$output = [
			self::TYPE => get_class($datetime),
			"datetime" => $utc->format(self::DATETIME_FORMAT),
			"timezone" => $datetime->getTimezone()->getName(),
		];

		return $output;
	}

	/**
	 * @param array $data
	 * @return DateTime|DateTimeImmutable
	 */
	protected function _unserializeDateTime($data)
	{
		if($data[self::TYPE] == 'DateTime') {
			$datetime = DateTime::createFromFormat(self::DATETIME_FORMAT, $data["datetime"], timezone_open("UTC"));
			$datetime->setTimezone(timezone_open($data["timezone"]));
			return $datetime;
		}

		if($data[self::TYPE] == 'DateTimeImmutable') {
			$datetime = DateTimeImmutable::createFromFormat(self::DATETIME_FORMAT, $data["datetime"], timezone_open("UTC"));
			return $datetime->setTimezone(timezone_open($data["timezone"]));
		}

		throw new QueueException("unsupported type: " . $data[self::TYPE]);
	}
}
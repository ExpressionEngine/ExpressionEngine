<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model;

use Closure;
use OverflowException;

use ExpressionEngine\Library\Data\Entity;
use ExpressionEngine\Library\Data\SerializableEntity;
use ExpressionEngine\Service\Model\Association\Association;
use ExpressionEngine\Service\Model\Column\StaticType;
use ExpressionEngine\Service\Validation\Validator;
use ExpressionEngine\Service\Validation\ValidationAware;
use ExpressionEngine\Service\Event\Subscriber;

/**
 * Model Service
 */
class Model extends SerializableEntity implements Subscriber, ValidationAware
{
    /**
     * @var String model short name
     */
    protected $_name;

    /**
     * @var Is new instance
     */
    protected $_new = true;

    /**
     * @var Model facade object
     */
    protected $_facade = null;

    /**
     * @var Validator object
     */
    protected $_validator = null;

    /**
     * @var Hook recursion prevention
     */
    protected $_in_hook = array();

    /**
     * @var Associated models
     */
    protected $_associations = array();

    /**
     * @var Cache of variable types - can be class names or objects
     */
    protected $_property_types = array();

    /**
     * @var Cache of foreign key names
     */
    protected $_foreign_keys = array();

    /**
     * @var check if entry has saved already. Used in hooks
     */
    protected $_has_saved = false;

    /**
     * @var Type names and their corresponding classes
     */
    protected static $_type_classes = array(
        'bool' => 'ExpressionEngine\Service\Model\Column\Scalar\Boolean',
        'boolean' => 'ExpressionEngine\Service\Model\Column\Scalar\Boolean',

        'float' => 'ExpressionEngine\Service\Model\Column\Scalar\FloatNumber',
        'double' => 'ExpressionEngine\Service\Model\Column\Scalar\FloatNumber',

        'int' => 'ExpressionEngine\Service\Model\Column\Scalar\Integer',
        'integer' => 'ExpressionEngine\Service\Model\Column\Scalar\Integer',

        'string' => 'ExpressionEngine\Service\Model\Column\Scalar\StringLiteral',

        'yesNo' => 'ExpressionEngine\Service\Model\Column\Scalar\YesNo',
        'boolString' => 'ExpressionEngine\Service\Model\Column\Scalar\YesNo',

        'timestamp' => 'ExpressionEngine\Service\Model\Column\ColumnObject\Timestamp',

        'base64' => 'ExpressionEngine\Service\Model\Column\Serialized\Base64',
        'base64Array' => 'ExpressionEngine\Service\Model\Column\Serialized\Base64Array',
        'base64Serialized' => 'ExpressionEngine\Service\Model\Column\Serialized\Base64Native',

        'json' => 'ExpressionEngine\Service\Model\Column\Serialized\Json',

        'commaDelimited' => 'ExpressionEngine\Service\Model\Column\Serialized\CommaDelimited',
        'pipeDelimited' => 'ExpressionEngine\Service\Model\Column\Serialized\PipeDelimited',
        'serialized' => 'ExpressionEngine\Service\Model\Column\Serialized\Native',
    );

    /**
     * @var Typed columns must default to array
     */
    protected static $_typed_columns = array();

    /**
     * @var Relationships property must default to array
     */
    protected static $_relationships = array();

    /**
     * @var Default mixins for models
     */
    protected static $_mixins = array(
        'ExpressionEngine\Service\Model\Mixin\Relationship'
    );

    /**
     * Add some default filters that we need for models. Might hardcode some
     * of these in the long run.
     */
    protected function initialize()
    {
        // not a typo, 'this' is replaced with $this to prevent
        // a memory leak - long term these need to move to a better place
        $this->addFilter('get', array('this', 'typedGet'));
        $this->addFilter('set', array('this', 'typedSetAndForeignKeys'));
        $this->addFilter('fill', array('this', 'typedLoad'));
        $this->addFilter('store', array('this', 'typedStore'));

        // Need to set these up here instead of on-demand because the model delete
        // routine will create a collection with new model objects to delete,
        // thus ignoring any previously-set up events; plus, cascading deletes
        if ($this->getMetaData('hook_id')) {
            $this->forwardEventToHooks('delete');
        }
    }

    /**
     * Forward methods to various mixins
     *
     * @param String $method Method name to call
     * @param Array $args Arguments to pass to the method
     * @return Mixed return value of the called method
     */
    public function __call($method, $args)
    {
        if ($action = $this->getMixin('Model:Relationship')->getAssociationActionFromMethod($method)) {
            return $this->getMixin('Model:Relationship')->runAssociationAction($action, $args);
        }

        return parent::__call($method, $args);
    }

    /**
     * Extend __get to grant access to associated objects
     *
     * Associations must start with an uppercase letter
     *
     * @param String $key The property to access
     * @return Mixed The property value
     */
    public function __get($key)
    {
        if ($key && strtoupper($key[0]) == $key[0]) {
            if ($this->hasAssociation($key)) {
                return $this->getAssociation($key)->get();
            }
        }

        return parent::__get($key);
    }

    /**
     * Allow use of __set to set an association
     *
     * @param String $key The property to set
     * @param Mixed $value The property value
     */
    public function __set($key, $value)
    {
        if ($key && strtoupper($key[0]) == $key[0]) {
            if ($this->hasAssociation($key)) {
                return $this->getAssociation($key)->set($value);
            }
        }

        return parent::__set($key, $value);
    }

    /**
     * Remove some variables to get cleaner var_dump
     *
     * @return array
     */
    public function __debugInfo()
    {
        $footprint = get_object_vars($this);
        unset($footprint['_facade']);
        return $footprint;
    }

    /**
     * Get the short name
     *
     * @return String short name
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Set the short name of this model
     *
     * @param String $name The short name
     */
    public function setName($name)
    {
        if (isset($this->_name)) {
            throw new OverflowException('Cannot modify name after it has been set.');
        }

        $this->_name = $name;

        return $this;
    }

    /**
     * Access the primary key name
     *
     * @return string primary key name
     */
    public function getPrimaryKey()
    {
        return $this->getMetaData('primary_key');
    }

    /**
     * Get the primary key value
     *
     * @return int Primary key
     */
    public function getId()
    {
        $pk = $this->getPrimaryKey();

        return $this->$pk;
    }

    /**
     * Set the primary key value
     *
     * This will not trigger a dirty state. Primary keys should be
     * considered immutable!
     *
     * @return $this
     */
    public function setId($id)
    {
        $pk = $this->getPrimaryKey();
        $this->$pk = $id;

        $this->_new = is_null($id);

        $this->emit('setId', $id);

        foreach ($this->getAllBootedAssociations() as $association) {
            $association->idHasChanged();
        }

        return $this;
    }

    /**
     * Attempt to get a property. Overriden from Entity to support events
     *
     * @param String $name Name of the property
     * @return Mixed  $value Value of the property
     */
    public function getProperty($name)
    {
        $this->emit('beforeGet', $name);

        $value = parent::getProperty($name);

        $this->emit('afterGet', $name);

        return $value;
    }

    /**
     * Attempt to set a property. Overriden from Entity to support events
     *
     * @param String $name Name of the property
     * @param Mixed  $value Value of the property
     */
    public function setProperty($name, $value)
    {
        $this->emit('beforeSet', $name, $value);

        parent::setProperty($name, $value);

        $this->emit('afterSet', $name, $value);

        return $this;
    }

    /**
     * Fill data without passing through a getter
     *
     * @param array $data Data to fill
     * @return $this
     */
    public function fill(array $data = array())
    {
        $pk = $this->getPrimaryKey();

        if (isset($data[$pk])) {
            $this->_new = false;
        }

        return parent::fill($data);
    }

    /**
     * Check if the model has been saved
     *
     * @return bool is new?
     */
    public function isNew()
    {
        return $this->_new;
    }

    public function getModified()
    {
        return array_merge(
            $this->getChangedTypeValues(),
            parent::getModified()
        );
    }

    /**
     * Save the model
     *
     * @return $this
     */
    public function save()
    {
        $qb = $this->newSelfReferentialQuery();

        $this->forwardEventToHooks('save');

        if ($this->isNew()) {
            $this->forwardEventToHooks('insert');
            try {
                $qb->insert();
            } catch (\Exception $e) {
                $this->catchDbExceptionOnModel($e, 'insert');
            }
        } else {
            $this->constrainQueryToSelf($qb);
            $this->forwardEventToHooks('update');
            try {
                $qb->update();
            } catch (\Exception $e) {
                $this->catchDbExceptionOnModel($e, 'update');
            }
        }

        // update relationships
        foreach ($this->getAllAssociations() as $assoc) {
            if (isset($assoc)) {
                $assoc->save();
            }
        }

        $this->emit('afterAssociationsSave');

        return $this;
    }

    /**
     * Delete the model
     *
     * @return $this
     */
    public function delete()
    {
        if ($this->isNew()) {
            return $this;
        }

        $qb = $this->newSelfReferentialQuery();

        $this->constrainQueryToSelf($qb);

        $qb->delete();

        $this->setId(null);
        $this->markAsClean();

        // clear relationships
        foreach ($this->getAllAssociations() as $name => $assoc) {
            if (isset($assoc)) {
                $assoc->set(null);
            }
        }

        return $this;
    }

    /**
     * Limit a query to the primary id of this model
     *
     * @param QueryBuilder $query The query that will be sent
     */
    protected function constrainQueryToSelf($query)
    {
        $pk = $this->getPrimaryKey();
        $id = $this->getId();

        $query->filter($pk, $id);
    }

    /**
     * Set the facade
     *
     * @param Facade $facade The model facade to use
     * @return $this
     */
    public function setFacade(Facade $facade)
    {
        if (isset($this->_facade)) {
            throw new OverflowException('Cannot override existing model facade.');
        }

        $this->_facade = $facade;

        return $this;
    }

    /**
     * Get the model facade
     *
     * @return Facade The model facade object
     */
    public function getModelFacade()
    {
        return $this->_facade;
    }

    // alias
    public function getFrontend()
    {
        return $this->getModelFacade();
    }

    /**
     * Validate the model
     *
     * @return \ExpressionEngine\Service\Validation\Result validation result
     */
    public function validate()
    {
        $validator = $this->getValidator();

        if (! isset($validator)) {
            return true;
        }

        $this->ensureValidationAliases();

        $this->emit('beforeValidate');

        if ($this->isNew()) {
            $result = $validator->validate($this);
        } else {
            $result = $validator->validatePartial($this);
        }

        $this->emit('afterValidate');

        return $result;
    }

    /**
     * Set the validator
     *
     * @param Validator $validator The validator to use
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $this->_validator = $validator;

        return $this;
    }

    /**
     * Get the validator
     *
     * @return Validator object
     */
    public function getValidator()
    {
        return $this->_validator;
    }

    /**
     * Alias some validate* rules to the unprefixed name.
     *
     * This used to be done in the validation setter, but that ends up being a
     * bit of a waste of work and sets up a circular reference that's not easily
     * garbage collected. This is much easier.
     */
    private function ensureValidationAliases()
    {
        if (! $this->_validator->hasCustomRule('unique')) {
            // alias unique to the validateUnique callback
            $this->_validator->defineRule('unique', array($this, 'validateUnique'));

            // alias uniqueWithinSiblings to the validateUniqueWithinSiblings callback
            $this->_validator->defineRule('uniqueWithinSiblings', array($this, 'validateUniqueWithinSiblings'));
        }
    }

    /**
     * Support ValidationAware
     */
    public function getValidationData()
    {
        return $this->getModified();
    }

    /**
     * Support ValidationAware
     */
    public function getValidationRules()
    {
        return $this->getMetaData('validation_rules') ?: array();
    }

    /**
     * Default callback to validate unique columns
     *
     * @param String $key    Property name
     * @param String $value  Property value
     * @param Array  $params Rule parameters
     * @return Mixed String if error, TRUE if success
     */
    public function validateUnique($key, $value, array $params = array())
    {
        $unique = $this->getModelFacade()
            ->get($this->getName())
            ->filter($key, $value);

        foreach ($params as $field) {
            $unique->filter($field, $this->getProperty($field));
        }

        // Do not match self
        if ($this->getId()) {
            $unique->filter($this->getPrimaryKey(), '!=', $this->getId());
        }

        if ($unique->count() > 0) {
            return 'unique'; // lang key
        }

        return true;
    }

    /**
     * Forwards lifecycle events to consistently named hooks
     *
     * This is fired automatically from initialize() if `hook_id` is
     * given in the model metadata.
     *
     * @param String $event Event name, either 'insert', 'update', 'save', or 'delete'
     */
    protected function forwardEventToHooks($event)
    {
        $hook_basename = $this->getMetaData('hook_id');

        $uc_first_event = ucfirst($event);

        $forwarded = array(
            'before' . $uc_first_event => 'before_' . $hook_basename . '_' . $event,
            'after' . $uc_first_event => 'after_' . $hook_basename . '_' . $event
        );

        $that = $this;
        $trigger = $this->getHookTrigger();

        foreach ($forwarded as $event => $hook) {
            if (!$this->hookShouldTrigger($hook)) {
                continue;
            }

            $this->on($event, function () use ($trigger, $hook, $that) {
                $addtl_args = func_get_args();
                $args = array($hook, $that, $that->getValues());

                call_user_func_array($trigger, array_merge($args, $addtl_args));
            });
        }
    }

    /**
     * checks if designated hook should fitre
     * @param  string $hook    Name of hook
     * @return boolean
     */
    protected function hookShouldTrigger($hook)
    {
        $process = true;

        switch ($hook) {
            case 'before_channel_entry_delete':
                $process = false;

                break;
            case 'after_channel_entry_save':
                if ($this->_has_saved) {
                    $process = false;
                }
                $this->_has_saved = true;

                break;
            default:
                $process = true;

                break;
        }

        return $process;
    }

    /**
     * Returns a function that can be used to trigger a hook outside the current
     * object scope. Thank you PHP 5.3, you hunk of garbage.
     *
     * @return Closure Function that takes hookname and parameters and calls the hook
     */
    protected function getHookTrigger()
    {
        $in_hook = & $this->_in_hook;

        return function ($name) use ($in_hook) {
            if (in_array($name, $in_hook)) {
                return;
            }

            $in_hook[] = $name;

            if (isset(ee()->extensions) && ee()->extensions->active_hook($name) === true) {
                $args = func_get_args();
                call_user_func_array(array(ee()->extensions, 'call'), $args);
            }

            array_pop($in_hook);
        };
    }

    /**
     * Default callback to validate unique columns across siblings
     *
     * @param String $key    Property name
     * @param String $value  Property value
     * @param Array  $params Rule parameters, first parameter must be the parent
     *	relationship name, second must be child relationship name from parent
     * @return Mixed String if error, TRUE if success
     */
    public function validateUniqueWithinSiblings($key, $value, array $params)
    {
        if (count($params) != 2) {
            throw new InvalidArgumentException('uniqueWithinSiblings must have at least two arguments.');
        }

        $parent = $params[0];
        $siblings = $params[1];

        if ($this->$parent && $this->$parent->$siblings) {
            $count = $this->$parent->$siblings->filter($key, $value)->count();

            if ($count > 1) {
                return 'unique';
            }
        }

        return true;
    }

    public function typedLoad($value, $name)
    {
        if ($type = $this->getTypeFor($name)) {
            return $type->load($value);
        }

        return $value;
    }

    public function typedStore($value, $name)
    {
        if ($type = $this->getTypeFor($name)) {
            return $type->store($value);
        }

        return $value;
    }

    public function typedGet($value, $name)
    {
        if ($type = $this->getTypeFor($name)) {
            return $type->get($value);
        }

        return $value;
    }

    public function typedSetAndForeignKeys($value, $name)
    {
        if ($type = $this->getTypeFor($name)) {
            $value = $type->set($value);
        }

        if (array_key_exists($name, $this->_foreign_keys)) {
            $assoc = $this->getAssociation($this->_foreign_keys[$name]);
            $assoc->foreignKeyChanged($value);
        }

        return $value;
    }

    public function getTypeFor($name)
    {
        if (! array_key_exists($name, $this->_property_types)) {
            $this->_property_types[$name] = $this->createTypeFor($name);
        }

        return $this->_property_types[$name];
    }

    public function createTypeFor($name)
    {
        $columns = $this->getMetadata('typed_columns') ?: array();

        if (! array_key_exists($name, $columns)) {
            return null;
        }

        $types = $this->getMetadata('type_classes');

        $type = $columns[$name];
        $class = $types[$type];

        return $class::create();
    }

    /**
     * Sync up typed column values
     */
    protected function getChangedTypeValues()
    {
        $changed = array();

        foreach ($this->_property_types as $name => $type) {
            $set = $this->getRawProperty($name);
            $type = $this->getTypeFor($name);

            if ($this->isDirty($name) || $type instanceof Entity) {
                $value = $this->getBackup($name, $set);
                $new_value = $this->typedStore($set, $name);

                if ($new_value !== $value) {
                    $changed[$name] = $set;
                }
            }
        }

        return $changed;
    }

    /**
     * Getter for serialization
     *
     * @return Mixed Data to serialize
     */
    protected function getSerializeData()
    {
        return array(
            'name' => $this->getName(),
            'values' => parent::getSerializeData()
        );
    }

    /**
     * Overridable setter for unserialization
     *
     * @param Mixed $data Data returned from `getSerializedData`
     * @return void
     */
    public function setSerializeData($data)
    {
        // datastore requires a name
        $this->setName($data['name']);

        // set all of the external dependencies
        ee('Model')->make($this);

        parent::setSerializeData($data['values']);
    }

    /**
     * Interface method to implement Event\Subscriber
     */
    public function getSubscribedEvents()
    {
        return $this->getMetaData('events') ?: array();
    }

    /**
     * Override emit for subscribed events. This keeps us from circularly referencing
     * ourselves in the event emitter.
     */
    public function emit(/*$event, ...$args */)
    {
        $args = func_get_args();
        $event = $args[0];

        // handle events we're subscribed to
        if (in_array($event, $this->getSubscribedEvents())) {
            $method = 'on' . ucfirst($event);
            call_user_func_array(array($this, $method), array_slice($args, 1));
        }

        call_user_func_array(parent::class . "::emit", $args);
    }

    /**
     * Emit an event to a static method, typically an event that isn't tied to
     * any one entity, but just one that an entity type needs to know about,
     * such as a bulk deletion of its type. Also calls an extension hook if the
     * model has a hook ID.
     */
    public static function emitStatic(/* $event, ...$args */)
    {
        $args = func_get_args();
        $event = array_shift($args);

        $events = self::getMetaData('events') ?: [];
        $events = array_flip($events);

        if (isset($events[$event])) {
            $method = '::on' . ucfirst($event);
            forward_static_call_array(static::class . $method, $args);
        }

        // Extension hook
        if ($hook_basename = self::getMetaData('hook_id')) {
            if (strpos($event, 'before') === 0) {
                $when = 'before_';
            }
            if (strpos($event, 'after') === 0) {
                $when = 'after_';
            }

            // Turn an event string like beforeBulkDelete into before_member_bulk_delete
            $action = str_replace(['before', 'after'], '', $event);
            $action = preg_replace_callback('/([a-z])([A-Z])/', function ($matches) {
                return strtolower($matches[1]) . '_' . strtolower($matches[2]);
            }, lcfirst($action));

            $hook_name = $when . $hook_basename . '_' . $action;

            if (isset(ee()->extensions) && ee()->extensions->active_hook($hook_name) === true) {
                $args = array_merge([$hook_name], $args);
                call_user_func_array(array(ee()->extensions, 'call'), $args);
            }
        }
    }

    /**
    * Get all associations
    *
    * @return array associations
    */
    public function getAllAssociations()
    {
        foreach ($this->_associations as $name => $assoc) {
            if (! $assoc->isBooted()) {
                $assoc->boot($this);
            }
        }

        return $this->_associations;
    }

    /**
    * Get all booted associations
    *
    * @return array associations
    */
    public function getAllBootedAssociations()
    {
        $assocs = array();

        foreach ($this->_associations as $name => $assoc) {
            if ($assoc->isBooted()) {
                $assocs[$name] = $assoc;
            }
        }

        return $assocs;
    }

    /**
    * Check if an association of a given name exists
    *
    * @param String $name Name of the association
    * @return bool has association?
    */
    public function hasAssociation($name)
    {
        return array_key_exists($name, $this->_associations);
    }

    /**
    * Get an association of a given name
    *
    * @param String $name Name of the association
    * @return Mixed the association
    */
    public function getAssociation($name)
    {
        $assoc = $this->_associations[$name];

        if (! $assoc->isBooted()) {
            $assoc->boot($this);
        }

        return $assoc;
    }

    /**
    * Set a given association
    *
    * @param String $name Name of the association
    * @param Association $association Association to set
    * @return $this
    */
    public function setAssociation($name, Association $association)
    {
        $association->setFacade($this->getModelFacade());

        // check for a foreign key to listen to
        $fk = $association->getForeignKey();

        if ($fk != $this->getPrimaryKey()) {
            $this->addForeignKey($fk, $name);
        }

        $this->_associations[$name] = $association;

        return $this;
    }

    /**
     * Alias an association
     *
     * @param String Associaton name to create an alias for
     * @param String Alias name
     */
    public function alias($association, $as)
    {
        if (strpos($association, ':') === false) {
            throw new \Exception('Cannot alias relationship.');
        }

        return $this->setAssociation($as, $this->getAssociation($association));
    }

    /**
     * Add a foreign key
     */
    public function addForeignKey($key, $assoc_name)
    {
        $this->_foreign_keys[$key] = $assoc_name;
    }

    /**
     * Create a new query tied to this object
     *
     * @return QueryBuilder new query
     */
    protected function newSelfReferentialQuery()
    {
        return $this->_facade->get($this);
    }

    /**
     * Provide a bit of debugging information when printing a model, but
     * don't show any potentially sensitive information.
     */
    public function __toString()
    {
        return spl_object_hash($this) . ':' . $this->getName() . ':' . $this->getId();
    }

    /**
     * For certain exceptions, we'd like to catch those early
     * and write to developer log
     *
     * @param \Exception $message
     * @param string $operation
     * @return void
     */
    private function catchDbExceptionOnModel($exception, $operation = 'update') {
        if (strpos($exception->getMessage(), "Incorrect string value: '\x") !== false) {
            if (! isset(ee()->logger)) {
                ee()->load->library('logger');
            }
            ee()->logger->developer('Unable to ' . $operation . ' ' . $this->getName() . ' model. The data contains multibyte characters, however the database table does not support those.', true);
        }
        throw $exception;
    }

    protected function saveToCache($key, $data)
    {
        if (isset(ee()->core)) {
            ee()->core->set_cache(get_called_class(), $key, $data);
        }
    }

    protected function getFromCache($key)
    {
        if (isset(ee()->core)) {
            return ee()->core->cache(get_called_class(), $key, false);
        }
        return false;
    }
}

// EOF

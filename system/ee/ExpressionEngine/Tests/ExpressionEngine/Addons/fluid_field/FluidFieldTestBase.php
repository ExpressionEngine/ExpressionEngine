<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use Mockery as m;
use PHPUnit\Framework\TestCase;

if (!defined('REQ')) {
    define('REQ', 'CP');
}

if (!class_exists('EE_Fieldtype')) {
    require_once SYSPATH . 'ee/legacy/fieldtypes/EE_Fieldtype.php';
}

if (!class_exists('Fluid_field_ft')) {
    require_once PATH_ADDONS . 'fluid_field/ft.fluid_field.php';
}

abstract class FluidFieldTestBase extends TestCase
{
    /**
     * @var Fluid_field_ft
     */
    protected $fieldtype;

    /**
     * @var FluidFieldModelServiceStub
     */
    protected $modelService;

    /**
     * @var FluidFieldSessionStub
     */
    protected $session;

    /**
     * @var FluidFieldInputStub
     */
    protected $input;

    /**
     * @var FluidFieldExtensionsStub
     */
    protected $extensions;

    /**
     * @var FluidFieldDbStub
     */
    protected $db;

    /**
     * @var FluidFieldViewServiceStub
     */
    protected $viewService;

    /**
     * @var FluidFieldLoadStub
     */
    protected $load;

    /**
     * @var FluidFieldRequestStub
     */
    protected $request;

    /**
     * @var FluidFieldLivePreviewStub
     */
    protected $livePreview;

    /**
     * @var FluidFieldJavascriptStub
     */
    protected $javascript;

    /**
     * @var FluidFieldCpStub
     */
    protected $cp;

    /**
     * @var FluidFieldModalStub
     */
    protected $modal;

    /**
     * @var FluidFieldAlertServiceStub
     */
    protected $alert;

    /**
     * @var FluidFieldLoggerStub
     */
    protected $logger;

    /**
     * @var FluidFieldLocalizeStub
     */
    protected $localize;

    /**
     * @var FluidFieldConfigStub
     */
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        ee()->resetMocks();

        $this->session = new FluidFieldSessionStub();
        $this->input = new FluidFieldInputStub();
        $this->extensions = new FluidFieldExtensionsStub();
        $this->db = new FluidFieldDbStub();
        $this->viewService = new FluidFieldViewServiceStub();
        $this->load = new FluidFieldLoadStub();
        $this->request = new FluidFieldRequestStub();
        $this->livePreview = new FluidFieldLivePreviewStub();
        $this->javascript = new FluidFieldJavascriptStub();
        $this->cp = new FluidFieldCpStub();
        $this->modal = new FluidFieldModalStub();
        $this->alert = new FluidFieldAlertServiceStub();
        $this->logger = new FluidFieldLoggerStub();
        $this->localize = new FluidFieldLocalizeStub();
        $this->config = new FluidFieldConfigStub();

        $this->modelService = new FluidFieldModelServiceStub();

        ee()->setMock('session', $this->session);
        ee()->setMock('input', $this->input);
        ee()->setMock('extensions', $this->extensions);
        ee()->setMock('db', $this->db);
        ee()->setMock('load', $this->load);
        ee()->setMock('javascript', $this->javascript);
        ee()->setMock('cp', $this->cp);
        ee()->setMock('logger', $this->logger);
        ee()->setMock('localize', $this->localize);
        ee()->setMock('config', $this->config);

        ee()->setMock('Model', $this->modelService);
        ee()->setMock('View', $this->viewService);
        ee()->setMock('Request', $this->request);
        ee()->setMock('LivePreview', $this->livePreview);
        ee()->setMock('CP/Modal', $this->modal);
        ee()->setMock('CP/Alert', $this->alert);
        ee()->setMock('CP/URL', new FluidFieldUrlServiceStub());
        ee()->setMock('Addon', new FluidFieldAddonServiceStub());
        ee()->setMock('Validation', new FluidFieldValidationServiceStub());

        ee()->setMock('fluid_field_parser', new FluidFieldParserStub());
        ee()->setMock('grid_parser', new FluidFieldGridParserStub());
        ee()->setMock('grid_lib', new FluidFieldGridLibStub());

        $this->fieldtype = new Fluid_field_ft();
        $this->fieldtype->_init([
            'id' => 10,
            'name' => 'fluid_content',
            'content_id' => 99,
            'content_type' => 'channel',
        ]);
        $this->fieldtype->field_id = 10;
        $this->fieldtype->field_name = 'fluid_content';

        $this->fieldtype->settings = [
            'field_channel_fields' => [],
            'field_channel_field_groups' => [],
            'field_short_name' => 'fluid_content',
            'field_label' => 'Fluid Content'
        ];
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
        m::close();
        parent::tearDown();
    }

    protected function setModelGetCallback(callable $callback): void
    {
        $this->modelService->getCallback = $callback;
    }

    protected function setModelMakeCallback(callable $callback): void
    {
        $this->modelService->makeCallback = $callback;
    }

    protected function invokePrivateMethod($object, string $method, array $args = [])
    {
        $reflection = new ReflectionClass($object);
        $refMethod = $reflection->getMethod($method);
        \TestReflectionHelper::makeAccessible($refMethod);

        return $refMethod->invokeArgs($object, $args);
    }

    protected function getPrivateProperty($object, string $property)
    {
        $reflection = new ReflectionClass($object);
        $refProperty = $reflection->getProperty($property);
        \TestReflectionHelper::makeAccessible($refProperty);

        return $refProperty->getValue($object);
    }

    protected function setPrivateProperty($object, string $property, $value): void
    {
        $reflection = new ReflectionClass($object);
        $refProperty = $reflection->getProperty($property);
        \TestReflectionHelper::makeAccessible($refProperty);
        $refProperty->setValue($object, $value);
    }

    protected function makeModelQuery($allResult = null, $firstResult = null): FluidFieldModelQueryStub
    {
        return new FluidFieldModelQueryStub($allResult, $firstResult);
    }
}

class FluidFieldAddonServiceStub
{
    public function get($addon)
    {
        return new class {
            public function getName()
            {
                return 'Fluid Field';
            }

            public function getVersion()
            {
                return '1.0.0';
            }
        };
    }
}

class FluidFieldModelServiceStub
{
    public $getCallback;
    public $makeCallback;
    public $getCalls = [];
    public $makeCalls = [];

    public function get($model, $id = null)
    {
        $this->getCalls[] = [$model, $id];

        if (is_callable($this->getCallback)) {
            return call_user_func($this->getCallback, $model, $id);
        }

        return new FluidFieldModelQueryStub(new FluidFieldTestCollection());
    }

    public function make($model)
    {
        $this->makeCalls[] = [$model];

        if (is_callable($this->makeCallback)) {
            return call_user_func($this->makeCallback, $model);
        }

        return new FluidFieldRecordStub();
    }
}

class FluidFieldModelQueryStub
{
    public $allResult;
    public $firstResult;

    public function __construct($allResult = null, $firstResult = null)
    {
        $this->allResult = $allResult;
        $this->firstResult = $firstResult;
    }

    public function with($relations)
    {
        return $this;
    }

    public function filter($field, $operator = null, $value = null)
    {
        return $this;
    }

    public function filterGroup()
    {
        return $this;
    }

    public function endFilterGroup()
    {
        return $this;
    }

    public function orFilter($field, $operator = null, $value = null)
    {
        return $this;
    }

    public function order($field, $direction = null)
    {
        return $this;
    }

    public function fields($fields)
    {
        return $this;
    }

    public function all()
    {
        return $this->allResult;
    }

    public function first()
    {
        return $this->firstResult;
    }
}

class FluidFieldTestCollection implements IteratorAggregate, Countable, ArrayAccess
{
    private $items;
    public $deleted = false;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
            return;
        }
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function asArray(): array
    {
        return array_values($this->items);
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    public function indexBy(string $property): array
    {
        $indexed = [];

        foreach ($this->items as $item) {
            $value = isset($item->$property) ? $item->$property : null;
            $indexed[$value] = $item;
        }

        return $indexed;
    }

    public function indexByIds(): array
    {
        $indexed = [];

        foreach ($this->items as $item) {
            if (is_object($item) && method_exists($item, 'getId')) {
                $indexed[$item->getId()] = $item;
                continue;
            }

            if (is_object($item) && isset($item->field_id)) {
                $indexed[$item->field_id] = $item;
            }
        }

        return $indexed;
    }

    public function filter(callable $callback): self
    {
        $filtered = [];

        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                $filtered[$key] = $item;
            }
        }

        return new self($filtered);
    }

    public function map(callable $callback): self
    {
        $mapped = [];

        foreach ($this->items as $key => $item) {
            $mapped[$key] = $callback($item, $key);
        }

        return new self($mapped);
    }

    public function pluck(string $property): array
    {
        $out = [];

        foreach ($this->items as $item) {
            $out[] = isset($item->$property) ? $item->$property : null;
        }

        return $out;
    }

    public function sortBy(string $property): self
    {
        $items = array_values($this->items);

        usort($items, function ($a, $b) use ($property) {
            $aValue = isset($a->$property) ? $a->$property : null;
            $bValue = isset($b->$property) ? $b->$property : null;

            if ($aValue == $bValue) {
                return 0;
            }

            return ($aValue < $bValue) ? -1 : 1;
        });

        return new self($items);
    }

    public function delete(): bool
    {
        $this->deleted = true;
        return true;
    }
}

#[AllowDynamicProperties]
class FluidFieldRecordStub
{
    public $id = 0;
    public $field_id = 0;
    public $group = 0;
    public $field_group_id = 0;
    public $field_data_id = 0;
    public $entry_id = 0;
    public $fluid_field_id = 0;
    public $order = 0;
    public $ChannelField;
    public $ChannelFieldGroup;
    public $savedCount = 0;
    public $deleted = false;

    private $field;
    private $fieldData;

    public function __construct($id = 0, $fieldId = 0, $field = null, $channelField = null, $channelFieldGroup = null)
    {
        $this->id = $id;
        $this->field_id = $fieldId;
        $this->field = $field ?: new FluidFieldFacadeStub($fieldId ?: 1);
        $this->ChannelField = $channelField ?: new FluidFieldChannelFieldStub($fieldId ?: 1, 'field_' . ($fieldId ?: 1));
        $this->ChannelFieldGroup = $channelFieldGroup;
        $this->fieldData = new FluidFieldDataRowStub();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFieldData()
    {
        return $this->fieldData;
    }

    public function getField($fieldData = null)
    {
        return $this->field;
    }

    public function save()
    {
        $this->savedCount++;
        return true;
    }

    public function delete()
    {
        $this->deleted = true;
        return true;
    }
}

class FluidFieldDataRowStub
{
    public $values = [];

    public function set(array $values)
    {
        $this->values = $values;
        return $this;
    }
}

#[AllowDynamicProperties]
class FluidFieldChannelFieldStub
{
    public $field_id;
    public $field_name;
    public $field_label;
    public $field_type;
    public $field_order;
    public $createTableCalls = 0;

    private $field;
    private $tableName;

    public function __construct($id, $name, $field = null, $type = 'text', $label = 'Label', $tableName = null)
    {
        $this->field_id = $id;
        $this->field_name = $name;
        $this->field_label = $label;
        $this->field_type = $type;
        $this->field_order = $id;
        $this->field = $field ?: new FluidFieldFacadeStub($id);
        $this->tableName = $tableName ?: 'exp_channel_data_field_' . $id;
    }

    public function getId()
    {
        return $this->field_id;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function createTable(): void
    {
        $this->createTableCalls++;
    }
}

#[AllowDynamicProperties]
class FluidFieldGroupStub
{
    public $group_id;
    public $group_name;
    public $short_name;
    public $ChannelFields;

    public function __construct($id, $groupName = 'Group', $shortName = 'group', $channelFields = null)
    {
        $this->group_id = $id;
        $this->group_name = $groupName;
        $this->short_name = $shortName;
        $this->ChannelFields = $channelFields ?: new FluidFieldTestCollection();
    }

    public function getId()
    {
        return $this->group_id;
    }
}

#[AllowDynamicProperties]
class FluidFieldFacadeStub
{
    public $id;
    public $name = '';
    public $data;
    public $format;
    public $timezone;
    public $contentId;
    public $items = [];
    public $validateResult = true;
    public $saveResult = 'saved';
    public $reindexResult = 'reindexed';
    public $hasReindex = false;
    public $nativeField;
    public $shortName = 'short';
    public $icon = 'icon';
    public $accepts = true;

    public function __construct($id)
    {
        $this->id = $id;
        $this->nativeField = (object) ['has_array_data' => false];
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function setContentId($contentId)
    {
        $this->contentId = $contentId;
    }

    public function setItem($key, $value)
    {
        $this->items[$key] = $value;
    }

    public function getItem($key)
    {
        return $this->items[$key] ?? null;
    }

    public function getNativeField()
    {
        return $this->nativeField;
    }

    public function validate($value)
    {
        $this->data = $value;
        return $this->validateResult;
    }

    public function save($value = null)
    {
        return $this->saveResult;
    }

    public function postSave(): void
    {
    }

    public function hasReindex(): bool
    {
        return $this->hasReindex;
    }

    public function reindex($data)
    {
        return $this->reindexResult;
    }

    public function acceptsContentType($contentType): bool
    {
        return $this->accepts;
    }

    public function getShortName()
    {
        return $this->shortName;
    }

    public function getIcon()
    {
        return $this->icon;
    }
}

class FluidFieldViewServiceStub
{
    public $renders = [];

    public function make($view)
    {
        return new class($this, $view) {
            private $service;
            private $view;

            public function __construct($service, $view)
            {
                $this->service = $service;
                $this->view = $view;
            }

            public function render($data = [])
            {
                $this->service->renders[] = ['view' => $this->view, 'data' => $data];
                return '[view:' . $this->view . ']';
            }
        };
    }
}

class FluidFieldSessionStub extends eeSingletonSessionMock
{
    public function cache($class, $key, $value = null)
    {
        // Match EE session cache behavior used by Fluid_field_ft:
        // passing false as 3rd param means "read with fallback false".
        if ($value === false) {
            return $this->cache[$class][$key] ?? false;
        }

        return parent::cache($class, $key, $value);
    }
}

class FluidFieldInputStub
{
    public $ajax = false;
    public $postData = [];

    public function is_ajax_request()
    {
        return $this->ajax;
    }

    public function post($key)
    {
        return $this->postData[$key] ?? null;
    }
}

class FluidFieldExtensionsStub
{
    public $activeHooks = [];
    public $calls = [];
    public $callReturn;

    public function active_hook($name)
    {
        return !empty($this->activeHooks[$name]);
    }

    public function call(...$args)
    {
        $this->calls[] = $args;

        if (func_num_args() > 0 && func_get_arg(0) === 'fluid_field_remove_field') {
            return null;
        }

        if (isset($this->callReturn)) {
            return $this->callReturn;
        }

        return end($args);
    }
}

class FluidFieldDbStub extends eeDbArMock
{
    public $setValues = [];
    public $whereValues = [];
    public $updates = [];
    public $inserts = [];
    public $deletes = [];
    public $insertIdValue = 321;

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->setValues[] = $key;
            return $this;
        }

        $this->setValues[] = [$key => $value];
        return $this;
    }

    public function where($field = null, $value = null)
    {
        $this->whereValues[] = [$field, $value];
        return $this;
    }

    public function update($table, $data = null, $where = null)
    {
        $this->updates[] = [$table, $data, $where];
        return true;
    }

    public function insert($table, $data = null)
    {
        $this->inserts[] = [$table, $data];
        return true;
    }

    public function delete($table, $where = null)
    {
        $this->deletes[] = [$table, $where];
        return true;
    }

    public function insert_id()
    {
        return $this->insertIdValue;
    }
}

class FluidFieldLoadStub
{
    public $helpers = [];
    public $libraries = [];
    public $packagePaths = [];

    public function helper($name): void
    {
        $this->helpers[] = $name;
    }

    public function library($name): void
    {
        $this->libraries[] = $name;
    }

    public function add_package_path($path): void
    {
        $this->packagePaths[] = ['add', $path];
    }

    public function remove_package_path($path): void
    {
        $this->packagePaths[] = ['remove', $path];
    }
}

class FluidFieldRequestStub
{
    public $values = [];

    public function get($key)
    {
        return $this->values[$key] ?? null;
    }

    public function post($key)
    {
        return $this->values[$key] ?? null;
    }
}

class FluidFieldLivePreviewStub
{
    public $hasData = false;
    public $entryData = [];

    public function hasEntryData()
    {
        return $this->hasData;
    }

    public function getEntryData()
    {
        return $this->entryData;
    }
}

class FluidFieldParserStub
{
    public $parseCalls = [];
    public $overrideCalls = [];

    public function parse($row, $id, $params, $tagdata, $contentType)
    {
        $this->parseCalls[] = [$row, $id, $params, $tagdata, $contentType];
        return 'parsed-output';
    }

    public function overrideWithPreviewData($data, $ids)
    {
        $this->overrideCalls[] = [$data, $ids];
        return $data;
    }
}

class FluidFieldGridParserStub
{
    public $fluid_field_field_names = [];
}

class FluidFieldGridLibStub
{
    public $field_id;
    public $content_type;
    public $entry_id;
    public $fluid_field_data_id;
    public $saved = [];

    public function save($data): void
    {
        $this->saved[] = $data;
    }
}

class FluidFieldJavascriptStub
{
    public $global = [];

    public function set_global(array $data): void
    {
        $this->global[] = $data;
    }
}

class FluidFieldCpStub
{
    public $scripts = [];

    public function add_js_script(array $scripts): void
    {
        $this->scripts[] = $scripts;
    }
}

class FluidFieldModalStub
{
    public $modals = [];

    public function addModal($name, $content): void
    {
        $this->modals[] = [$name, $content];
    }
}

class FluidFieldAlertServiceStub
{
    public $alerts = [];

    public function makeInline($name)
    {
        $alert = new FluidFieldInlineAlertStub($name);
        $this->alerts[] = $alert;

        return $alert;
    }
}

class FluidFieldInlineAlertStub
{
    public $name;
    public $important = false;
    public $title;
    public $body;
    public $deferred = false;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function asImportant()
    {
        $this->important = true;
        return $this;
    }

    public function withTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function addToBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function defer()
    {
        $this->deferred = true;
        return $this;
    }
}

class FluidFieldUrlServiceStub
{
    public function make($path)
    {
        return new class($path) {
            private $path;

            public function __construct($path)
            {
                $this->path = $path;
            }

            public function compile()
            {
                return '/cp/' . $this->path;
            }

            public function __toString()
            {
                return $this->compile();
            }
        };
    }
}

class FluidFieldLoggerStub
{
    public $actions = [];

    public function log_action($message): void
    {
        $this->actions[] = $message;
    }
}

class FluidFieldLocalizeStub
{
    public $now = 1700000000;
}

class FluidFieldConfigStub
{
    public $sitePrefsUpdates = [];

    public function item($name)
    {
        if ($name === 'site_id') {
            return 1;
        }

        return null;
    }

    public function update_site_prefs($prefs, $siteId): void
    {
        $this->sitePrefsUpdates[] = [$prefs, $siteId];
    }
}

class FluidFieldValidationServiceStub
{
    public $validator;

    public function __construct()
    {
        $this->validator = new FluidFieldValidatorStub(new FluidFieldValidationResultStub(true));
    }

    public function make()
    {
        return $this->validator;
    }
}

class FluidFieldValidatorStub
{
    public $rules = [];
    public $definedRules = [];
    public $result;
    public $validateCalls = [];

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function defineRule($name, $closure): void
    {
        $this->definedRules[$name] = $closure;
    }

    public function setRules($rules): void
    {
        $this->rules = $rules;
    }

    public function validate($data)
    {
        $this->validateCalls[] = $data;
        return $this->result;
    }
}

class FluidFieldValidationResultStub
{
    private $valid;
    private $failed;

    public function __construct(bool $valid, array $failed = [])
    {
        $this->valid = $valid;
        $this->failed = $failed;
    }

    public function isNotValid()
    {
        return !$this->valid;
    }

    public function getFailed()
    {
        return $this->failed;
    }
}

class FluidFieldRuleStub
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLanguageData()
    {
        return ['invalid_field', []];
    }
}

// EOF

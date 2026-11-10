<?php

// Shared by PHPUnit and the standalone suite, including exact PHP 7.2.5.
$nativeHash = function ($object) {
    if (PHP_VERSION_ID < 80600) {
        return spl_object_hash($object);
    }

    // PHP 8.6 retains the 32-character layout introduced in PHP 8.1.
    return str_pad(dechex(spl_object_id($object)), 16, '0', STR_PAD_LEFT) . str_repeat('0', 16);
};

return array(
    'Object hashes retain the native PHP representation' => function () use ($nativeHash) {
        $objects = array();
        $hashes = array();
        for ($i = 0; $i < 32; $i++) {
            $object = new stdClass();
            $objects[] = $object;
            $hash = ExpressionEngine\Library\Compat\ObjectHash::get($object);
            $hashes[] = $hash;
            compatibility_assert($nativeHash($object), $hash);
        }
        compatibility_assert(32, count(array_unique($hashes)));
    },
    'Object numeric and prefixed string map keys remain independent' => function () {
        $object = new stdClass();
        $id = spl_object_id($object);
        $entries = array(array($object, 'object'), array($id, 'number'), array('object:' . $id, 'string'));
        foreach (array($entries, array_reverse($entries)) as $ordered) {
            $map = new ExpressionEngine\Library\DataStructure\HashMap();
            foreach ($ordered as $entry) {
                $map->set($entry[0], $entry[1]);
            }
            compatibility_assert('object', $map->get($object));
            compatibility_assert('number', $map->get((string) $id));
            compatibility_assert('string', $map->get('object:' . $id));
            compatibility_assert(3, count($map->getKeys()));
            $map->remove($object);
            compatibility_assert(false, $map->hasKey($object));
            compatibility_assert('number', $map->get($id));
            compatibility_assert('string', $map->get('object:' . $id));
        }
    },
    'HashMap preserves legacy hash string lookups' => function () use ($nativeHash) {
        $object = new stdClass();
        $map = new ExpressionEngine\Library\DataStructure\HashMap();
        $map->set($object, 'value');
        compatibility_assert('value', $map->get($nativeHash($object)));
        compatibility_assert(true, $map->hasKey($nativeHash($object)));
        compatibility_assert(array($object), $map->getKeys());
    },
    'Singleton closures retain identity' => function () {
        $di = new ExpressionEngine\Service\Dependency\InjectionContainer();
        $count = 0;
        $make = function () use (&$count) { $count++; return new stdClass(); };
        compatibility_assert(true, $di->singleton($make) === $di->singleton($make));
        compatibility_assert(1, $count);
    },
    'Listeners can be removed independently' => function () {
        $events = new ExpressionEngine\Service\Event\Emitter();
        $calls = array();
        $first = function () use (&$calls) { $calls[] = 'first'; };
        $second = function () use (&$calls) { $calls[] = 'second'; };
        $events->on('event', $first)->on('event', $second)->off('event', $first)->emit('event');
        compatibility_assert(array('second'), $calls);
    },
    'Association changes cancel by object identity' => function () {
        $relation = new class {
            public $inserted;
            public function insert($parent, $models) { $this->inserted = array_values($models); }
        };
        $diff = new ExpressionEngine\Service\Model\Association\Diff(null, $relation);
        $first = new stdClass();
        $second = new stdClass();
        $diff->add($first);
        $diff->add($second);
        $diff->remove($first);
        $diff->commit();
        compatibility_assert(array($second), $relation->inserted);
    },
    'Model collections retain native hash strings' => function () use ($nativeHash) {
        $first = new ExpressionEngine\Service\Model\Collection();
        $second = new ExpressionEngine\Service\Model\Collection();
        compatibility_assert($nativeHash($first), (string) $first);
        compatibility_assert($nativeHash($second), (string) $second);
        compatibility_assert(true, (string) $first !== (string) $second);
    },
    'Models retain native hash prefixes' => function () use ($nativeHash) {
        $model = new class extends ExpressionEngine\Service\Model\Model {
            protected static $_primary_key = 'id';
            protected $id;
        };
        $model->setName('compatibility');
        $model->fill(array('id' => 7));
        compatibility_assert($nativeHash($model) . ':compatibility:7', (string) $model);
        $model->fill(array('id' => 0));
        compatibility_assert($nativeHash($model) . ':compatibility:0', (string) $model);
    },
    'Template annotations retain native hash comments' => function () use ($nativeHash) {
        $first = new ExpressionEngine\Library\Template\Annotation\Runtime();
        $second = new ExpressionEngine\Library\Template\Annotation\Runtime();
        $first->useSharedStore();
        $second->useSharedStore();
        try {
            $comment = $first->create(array('value' => 'original'));
            $data = $first->read($comment);
            compatibility_assert('{!-- ra:' . $nativeHash($data) . ' --}', $comment);
            compatibility_assert($data, $second->read($comment));
            $data->value = 'updated';
            compatibility_assert('updated', $second->read($comment)->value);
        } finally {
            $first->clearSharedStore();
        }
    },
    'Flysystem storage isolates live objects' => function () {
        $first = new ExpressionEngine\Dependency\League\Flysystem\SafeStorage();
        $second = new ExpressionEngine\Dependency\League\Flysystem\SafeStorage();
        $first->storeSafely('key', 'first');
        $second->storeSafely('key', 'second');
        compatibility_assert('first', $first->retrieveSafely('key'));
        compatibility_assert('second', $second->retrieveSafely('key'));
    },
    'IDNA constructor preserves option initialization' => function () {
        require_once BASEPATH . 'libraries/simplepie/idn/idna_convert.class.php';
        $idna = new idna_convert(array('encoding' => 'utf8', 'strict' => true));
        compatibility_assert('xn--bcher-kva', $idna->encode('bücher'));
        compatibility_assert(true, $idna->_strict_mode);
    },
);

<?php
namespace ExpressionEngine\Tests\ExpressionEngine\legacy\EE_Template;

require_once __DIR__ . '/EE_TemplateTestBase.php';

class EE_TemplateSyncFromFilesCoverageTemplatesStub
{
    private $names;

    public function __construct(array $names)
    {
        $this->names = $names;
    }

    public function pluck($field)
    {
        return $this->names;
    }
}

class EE_TemplateSyncFromFilesCoverageGroupsCollectionStub implements \IteratorAggregate
{
    private $groups;

    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->groups);
    }

    public function getDictionary($keyField, $valueField)
    {
        $out = [];
        foreach ($this->groups as $group) {
            $out[$group->$keyField] = $group->$valueField;
        }

        return $out;
    }
}

class EE_TemplateSyncFromFilesCoverageGroupQueryStub
{
    private $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    public function with($relation)
    {
        return $this;
    }

    public function filter($field, $value)
    {
        return $this;
    }

    public function order($field, $direction)
    {
        return $this;
    }

    public function all()
    {
        return $this->collection;
    }
}

class EE_TemplateSyncFromFilesCoverageTemplateGroupCreateStub
{
    public $group_id;
    private $data;
    private $model;

    public function __construct(array $data, $model)
    {
        $this->data = $data;
        $this->model = $model;
        $this->group_id = 700 + count($model->createdGroups);
    }

    public function save()
    {
        $this->model->createdGroups[] = $this->data + ['group_id' => $this->group_id];

        return $this;
    }
}

class EE_TemplateSyncFromFilesCoverageTemplateCreateStub
{
    private $data;
    private $model;

    public function __construct(array $data, $model)
    {
        $this->data = $data;
        $this->model = $model;
    }

    public function save()
    {
        $this->model->createdTemplates[] = $this->data;

        return $this;
    }

    public function saveNewTemplateRevision($template)
    {
        return true;
    }
}

class EE_TemplateSyncFromFilesCoverageModelStub
{
    public $createdGroups = [];
    public $createdTemplates = [];

    private $groupsCollection;

    public function __construct($groupsCollection)
    {
        $this->groupsCollection = $groupsCollection;
    }

    public function get($model)
    {
        return new EE_TemplateSyncFromFilesCoverageGroupQueryStub($this->groupsCollection);
    }

    public function make($model, $data = [])
    {
        if ($model === 'TemplateGroup') {
            return new EE_TemplateSyncFromFilesCoverageTemplateGroupCreateStub($data, $this);
        }

        return new EE_TemplateSyncFromFilesCoverageTemplateCreateStub($data, $this);
    }
}

class EE_TemplateSyncFromFilesCoverageTest extends EE_TemplateTestBase
{
    private function removeDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function testSyncFromFilesProcessesExistingAndNewGroupsAndTemplates()
    {
        $siteShortName = 'sync_cov_' . uniqid();
        $basepath = PATH_TMPL . $siteShortName;

        @mkdir($basepath . '/existing.group', 0777, true);
        @mkdir($basepath . '/newgroup.group', 0777, true);
        @mkdir($basepath . '/act.group', 0777, true);
        @mkdir($basepath . '/invalid!.group', 0777, true);

        file_put_contents($basepath . '/existing.group/index.html', 'existing index');
        file_put_contents($basepath . '/existing.group/about.html', 'about');
        file_put_contents($basepath . '/existing.group/._ignore.html', 'ignore');
        file_put_contents($basepath . '/existing.group/nodot', 'skip');
        @mkdir($basepath . '/existing.group/sub', 0777, true);
        file_put_contents($basepath . '/newgroup.group/page.html', 'page');
        file_put_contents($basepath . '/newgroup.group/unknown.txt', 'unknown');
        file_put_contents($basepath . '/act.group/index.html', 'reserved');
        file_put_contents($basepath . '/invalid!.group/file.html', 'invalid');

        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', $siteShortName);
        ee()->config->setItem('site_id', 1);
        ee()->session->setUserdata('member_id', 11);

        $existingGroup = (object) [
            'group_name' => 'existing',
            'group_id' => 10,
            'Templates' => new EE_TemplateSyncFromFilesCoverageTemplatesStub(['index']),
        ];
        $groupsCollection = new EE_TemplateSyncFromFilesCoverageGroupsCollectionStub([$existingGroup]);
        $modelStub = new EE_TemplateSyncFromFilesCoverageModelStub($groupsCollection);
        ee()->setMock('Model', $modelStub);

        $legacyApiMock = new class {
            public function instantiate($lib)
            {
                return true;
            }

            public function is_url_safe($name)
            {
                return preg_match('/^[a-zA-Z0-9_-]+$/', $name) === 1;
            }
        };
        ee()->setMock('legacy_api', $legacyApiMock);

        $templateStructureMock = new class {
            public function get_template_file_info($filename)
            {
                $parts = pathinfo($filename);
                if (!isset($parts['extension']) || $parts['extension'] !== 'html') {
                    return false;
                }

                return [
                    'extension' => 'html',
                    'name' => $parts['filename'],
                    'type' => 'webpage',
                    'engine' => '',
                ];
            }
        };
        ee()->setMock('api_template_structure', $templateStructureMock);

        $result = $this->template->sync_from_files();

        $this->assertNull($result);
        $this->assertCount(1, $modelStub->createdGroups);
        $this->assertEquals('newgroup', $modelStub->createdGroups[0]['group_name']);
        $this->assertGreaterThanOrEqual(3, count($modelStub->createdTemplates));

        $createdNames = array_column($modelStub->createdTemplates, 'template_name');
        $this->assertContains('about', $createdNames);
        $this->assertContains('page', $createdNames);
        $this->assertContains('index', $createdNames);

        $this->removeDir($basepath);
    }

    public function testSyncFromFilesSkipsInvalidNamesAndCatchesTemplateSaveException()
    {
        $siteShortName = 'sync_cov_skip_' . uniqid();
        $basepath = PATH_TMPL . $siteShortName;

        $longGroup = str_repeat('g', 51) . '.group';

        @mkdir($basepath . '/unsafe.group', 0777, true);
        @mkdir($basepath . '/valid.group', 0777, true);
        @mkdir($basepath . '/' . $longGroup, 0777, true);
        file_put_contents($basepath . '/unsafe.group/one.html', 'unsafe');
        file_put_contents($basepath . '/valid.group/badname.html', 'bad');
        file_put_contents($basepath . '/valid.group/longnamefile.html', 'long');
        file_put_contents($basepath . '/valid.group/explode.html', 'explode');
        file_put_contents($basepath . '/nongroup.txt', 'nongroup');

        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', $siteShortName);
        ee()->config->setItem('site_id', 1);
        ee()->session->setUserdata('member_id', 15);

        $groupsCollection = new EE_TemplateSyncFromFilesCoverageGroupsCollectionStub([]);

        $modelStub = new class($groupsCollection) extends EE_TemplateSyncFromFilesCoverageModelStub {
            public function make($model, $data = [])
            {
                if ($model === 'TemplateGroup') {
                    return parent::make($model, $data);
                }

                return new class($data, $this) extends EE_TemplateSyncFromFilesCoverageTemplateCreateStub {
                    private $dataRef;
                    private $modelRef;

                    public function __construct($data, $model)
                    {
                        $this->dataRef = $data;
                        $this->modelRef = $model;
                        parent::__construct($data, $model);
                    }

                    public function save()
                    {
                        if (($this->dataRef['template_name'] ?? '') === 'explode') {
                            throw new \Exception('template save failed');
                        }

                        $this->modelRef->createdTemplates[] = $this->dataRef;
                        return $this;
                    }
                };
            }
        };
        ee()->setMock('Model', $modelStub);

        $legacyApiMock = new class {
            public function instantiate($lib) { return true; }
            public function is_url_safe($name)
            {
                if ($name === 'unsafe') {
                    return false;
                }

                return preg_match('/^[a-zA-Z0-9_-]+$/', $name) === 1;
            }
        };
        ee()->setMock('legacy_api', $legacyApiMock);

        $templateStructureMock = new class {
            public function get_template_file_info($filename)
            {
                if ($filename === 'badname.html') {
                    return ['extension' => 'html', 'name' => 'bad$name', 'type' => 'webpage', 'engine' => ''];
                }
                if ($filename === 'longnamefile.html') {
                    return ['extension' => 'html', 'name' => str_repeat('a', 51), 'type' => 'webpage', 'engine' => ''];
                }
                if ($filename === 'explode.html') {
                    return ['extension' => 'html', 'name' => 'explode', 'type' => 'webpage', 'engine' => ''];
                }
                if (substr($filename, -5) !== '.html') {
                    return false;
                }

                return ['extension' => 'html', 'name' => pathinfo($filename, PATHINFO_FILENAME), 'type' => 'webpage', 'engine' => ''];
            }
        };
        ee()->setMock('api_template_structure', $templateStructureMock);

        $result = $this->template->sync_from_files();

        $this->assertNull($result);
        $createdNames = array_column($modelStub->createdTemplates, 'template_name');
        $this->assertContains('index', $createdNames);
        $this->assertNotContains('bad$name', $createdNames);
        $this->assertNotContains('explode', $createdNames);

        $this->removeDir($basepath);
    }

    public function testSyncFromFilesSkipsNewGroupWhenGroupNameIsNotUrlSafe()
    {
        $siteShortName = 'sync_cov_unsafe_' . uniqid();
        $basepath = PATH_TMPL . $siteShortName;

        @mkdir($basepath . '/blocked.group', 0777, true);
        file_put_contents($basepath . '/blocked.group/index.html', 'blocked');

        ee()->config->setItem('save_tmpl_files', 'y');
        ee()->config->setItem('site_short_name', $siteShortName);
        ee()->config->setItem('site_id', 1);
        ee()->session->setUserdata('member_id', 31);

        $groupsCollection = new EE_TemplateSyncFromFilesCoverageGroupsCollectionStub([]);
        $modelStub = new EE_TemplateSyncFromFilesCoverageModelStub($groupsCollection);
        ee()->setMock('Model', $modelStub);

        $legacyApiMock = new class {
            public function instantiate($lib)
            {
                return true;
            }

            public function is_url_safe($name)
            {
                if ($name === 'blocked') {
                    return false;
                }

                return true;
            }
        };
        ee()->setMock('legacy_api', $legacyApiMock);

        $templateStructureMock = new class {
            public function get_template_file_info($filename)
            {
                $parts = pathinfo($filename);
                if (!isset($parts['extension']) || $parts['extension'] !== 'html') {
                    return false;
                }

                return [
                    'extension' => 'html',
                    'name' => $parts['filename'],
                    'type' => 'webpage',
                    'engine' => '',
                ];
            }
        };
        ee()->setMock('api_template_structure', $templateStructureMock);

        $result = $this->template->sync_from_files();

        $this->assertNull($result);
        $this->assertCount(0, $modelStub->createdGroups);
        $this->assertCount(0, $modelStub->createdTemplates);

        $this->removeDir($basepath);
    }

    public function testSyncFromFilesRegexGateAlwaysImpliesUrlSafeForGroupNames()
    {
        $api = new \Api();
        $regex = "#^[a-zA-Z0-9_\\-]+$#i";

        $regexPassingSamples = [
            'a',
            'A1',
            'group_name',
            'group-name',
            'group123',
            'abc_def-ghi',
        ];

        foreach ($regexPassingSamples as $name) {
            $this->assertSame(1, preg_match($regex, $name));
            $this->assertTrue($api->is_url_safe($name));
        }

        // This value is URL-safe for Api but blocked by sync_from_files() regex gate.
        $this->assertSame(0, preg_match($regex, 'group.name'));
        $this->assertTrue($api->is_url_safe('group.name'));
    }
}

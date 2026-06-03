<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Installer\Libraries;

use PHPUnit\Framework\TestCase;

class InstallerTemplateLibraryTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testReplaceRelatedEntriesTagsTransformsRelatedAndReverseTags()
    {
        require_once SYSPATH . 'ee/installer/libraries/Template.php';

        $db = new class {
            public $selectCalls = [];
            public $getCalls = [];

            public function select($fields)
            {
                $this->selectCalls[] = $fields;
            }

            public function get($table)
            {
                $this->getCalls[] = $table;

                return new class {
                    public function result_array()
                    {
                        return [
                            ['field_id' => 1, 'field_name' => 'custom_var'],
                        ];
                    }
                };
            }
        };

        $functions = new class {
            private $i = 0;
            private $tokens = ['AAA11111', 'BBB22222'];

            public function random($type, $length)
            {
                return $this->tokens[$this->i++];
            }

            public function assign_conditional_variables($tagdata, $prefix, $ld, $rd)
            {
                return ['cond' => 'value'];
            }
        };

        $parser = new class {
            public function extractVariables($tagdata)
            {
                return [
                    'var_single' => ['title', 'ignore_single', 'custom_var'],
                    'var_pair' => [
                        'categories' => [],
                        'ignore_pair' => [],
                        'custom_var' => [],
                    ],
                ];
            }

            public function getFullTag($template, $tag, $open, $close)
            {
                return $tag;
            }

            public function parseTagParameters($params)
            {
                return [
                    'channel' => 'blog',
                    'fixed_order' => '3|2',
                ];
            }
        };

        ee()->setMock('db', $db);
        ee()->setMock('functions', $functions);
        ee()->setMock('Variables/Parser', $parser);

        $templateText = <<<TEMPLATE
{related_entries id='rel_field'}{title}{ignore_single}{custom_var}{categories}{/categories}{ignore_pair}{/ignore_pair}{custom_var}{/custom_var}{if no_related_entries}{if nested}None{/if}{/if}{/related_entries}
{reverse_related_entries channel='blog' fixed_order='3|2'}{title}{ignore_single}{custom_var}{categories}{/categories}{if no_reverse_related_entries}{if nested}None{/if}{/if}{/reverse_related_entries}
TEMPLATE;

        $reflection = new \ReflectionClass(\Installer_Template::class);
        $library = $reflection->newInstanceWithoutConstructor();

        $result = $library->replace_related_entries_tags($templateText);

        $this->assertSame(['field_id, field_name'], $db->selectCalls);
        $this->assertSame(['channel_fields'], $db->getCalls);
        $this->assertStringContainsString('{rel_field}{rel_field:title}', $result);
        $this->assertStringContainsString('{ignore_single}', $result);
        $this->assertStringContainsString('{rel_field:custom_var}{/rel_field:custom_var}', $result);
        $this->assertStringContainsString('{if rel_field:no_results}', $result);
        $this->assertStringContainsString('{parents channel="blog" fixed_order="3|2" }', $result);
        $this->assertStringContainsString('{parents:title}', $result);
        $this->assertStringContainsString('{parents:categories}{/parents:categories}', $result);
        $this->assertStringContainsString('{if parents:no_results}', $result);
        $this->assertStringNotContainsString('REL[', $result);
        $this->assertStringNotContainsString('REV_REL[', $result);
    }
}

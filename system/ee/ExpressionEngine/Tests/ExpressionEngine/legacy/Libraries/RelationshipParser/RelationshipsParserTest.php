<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\RelationshipParser;

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once SYSPATH . 'ee/legacy/libraries/Relationships_parser.php';

use PHPUnit\Framework\TestCase;

class RelationshipsParserTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();

        ee()->setMock('load', new class extends \eeSingletonLoadMock {
            public $models = [];

            public function model($name = '')
            {
                $this->models[] = $name;
            }
        });

        ee()->setMock('relationship_model', new class {
            public function node_query($node, $entry_ids, $grid_field_id = null, $fluid_field_data_id = null)
            {
                return [];
            }
        });

        ee()->setMock('extensions', new class {
            public $end_script = false;

            public function active_hook($name)
            {
                return false;
            }

            public function call(...$args)
            {
                return $args[0] ?? null;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }
        });
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testCreateReturnsNullWhenNoRelationshipTagsAreFound()
    {
        ee()->setMock('TMPL', (object) ['tagdata' => 'plain text']);

        $parser = new \EE_Relationships_parser();

        $this->assertNull($parser->create([1 => ['rel' => 10]], [], ''));
    }

    public function testCreateBuildsDataParserFromTemplateTagdataWhenTagdataArgumentIsEmpty()
    {
        ee()->setMock('TMPL', (object) ['tagdata' => '{rel}{/rel}']);

        $parser = new \EE_Relationships_parser();
        $result = $parser->create(['rel' => 10], [], '');

        $this->assertInstanceOf(\EE_Relationship_data_parser::class, $result);
    }

    public function testCreateUsesExplicitTagdataInsteadOfTemplateTagdata()
    {
        ee()->setMock('TMPL', (object) ['tagdata' => 'plain text']);

        $parser = new \EE_Relationships_parser();
        $result = $parser->create([1 => ['rel' => 10]], [], '{rel}{/rel}');

        $this->assertInstanceOf(\EE_Relationship_data_parser::class, $result);
    }
}


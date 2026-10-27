<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\FluidField\Service\Tag;
use ExpressionEngine\Model\Content\FieldFacade;
use ExpressionEngine\Service\Template\Variables\LegacyParser;
use PHPUnit\Framework\TestCase;

require_once PATH_ADDONS . 'fluid_field/Service/Tag.php';

class TagTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ee()->resetMocks();
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();

        parent::tearDown();
    }

    public function testRelationshipFieldParsesFluidMetadataConditionals()
    {
        $functions = new FluidFieldTagFunctionsStub();
        $relationshipParser = new FluidFieldTagRelationshipParserStub();
        $session = new eeSingletonSessionMock();
        $session->set_cache('mod_channel', 'active', (object) [
            'rfields' => [
                1 => ['original_related' => 7],
            ],
        ]);

        ee()->setMock('session', $session);
        ee()->setMock('relationships_parser', new FluidFieldTagRelationshipParserFactoryStub($relationshipParser));
        ee()->setMock('pro:FrontEdit', new FluidFieldTagFrontEditStub());

        $tagdata = <<<'TAGDATA'
{content status="open"}RELATED ENTRY{/content}
{if fluid_field:last}LAST FLUID ITEM{/if}
TAGDATA;

        $tag = new Tag($tagdata, $functions, new stdClass(), new LegacyParser());
        $tag->setTag('fluid_field:my_related');

        $field = new FluidFieldTagRelationshipFieldStub();
        $result = $tag->parse($field, ['fluid_field:last' => 1]);

        $this->assertSame("RELATED ENTRY\nLAST FLUID ITEM", $result);
        $this->assertSame('original_related', $field->getName());
        $this->assertCount(1, $functions->calls);
        $this->assertSame(1, $functions->calls[0]['vars']['fluid_field:last']);
    }

    public function testRelationshipFieldParsesOtherFluidMetadataConditionals()
    {
        $functions = new FluidFieldTagFunctionsStub();
        $relationshipParser = new FluidFieldTagRelationshipParserStub();
        $session = new eeSingletonSessionMock();
        $session->set_cache('mod_channel', 'active', (object) [
            'rfields' => [
                1 => ['original_related' => 7],
            ],
        ]);

        ee()->setMock('session', $session);
        ee()->setMock('relationships_parser', new FluidFieldTagRelationshipParserFactoryStub($relationshipParser));
        ee()->setMock('pro:FrontEdit', new FluidFieldTagFrontEditStub());

        $metadata = [
            'fluid_field:first' => 1,
            'fluid_field:last' => 1,
            'fluid_field:count' => 1,
            'fluid_field:index' => 1,
            'fluid_field:first_in_group' => 1,
            'fluid_field:last_in_group' => 1,
            'fluid_field:count_in_group' => 1,
            'fluid_field:index_in_group' => 1,
            'fluid_field:current_field_name' => 1,
            'fluid_field:next_field_name' => 1,
            'fluid_field:prev_field_name' => 1,
            'fluid_field:current_fieldtype' => 1,
            'fluid_field:next_fieldtype' => 1,
            'fluid_field:prev_fieldtype' => 1,
            'fluid_field:this_field_name' => 1,
            'fluid_field:this_fieldtype' => 1,
            'fluid_field:first_group' => 1,
            'fluid_field:last_group' => 1,
            'fluid_field:count_group' => 1,
            'fluid_field:index_group' => 1,
            'fluid_field:current_group_name' => 1,
            'fluid_field:current_group_short_name' => 1,
            'fluid_field:next_group_name' => 1,
            'fluid_field:next_group_short_name' => 1,
            'fluid_field:prev_group_name' => 1,
            'fluid_field:prev_group_short_name' => 1,
            'fluid_field:this_group_name' => 1,
        ];

        $tagdata = implode("\n", array_map(function ($key) {
            return "{if {$key}}{$key}{/if}";
        }, array_keys($metadata)));

        $tag = new Tag($tagdata, $functions, new stdClass(), new LegacyParser());
        $tag->setTag('fluid_field:my_related');

        $result = $tag->parse(new FluidFieldTagRelationshipFieldStub(), $metadata);

        $this->assertSame(implode("\n", array_keys($metadata)), $result);
    }
}

class FluidFieldTagRelationshipFieldStub extends FieldFacade
{
    private $name = 'original_related';

    public function __construct()
    {
    }

    public function getId()
    {
        return 7;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return 'relationship';
    }

    public function getContentId()
    {
        return 42;
    }

    public function getItem($field)
    {
        return $field === 'fluid_field_data_id' ? 99 : null;
    }

    public function getData()
    {
        return [123];
    }

    public function replaceTag($tagdata, $params = [], $specificModifier = '', $full_modifier = '', $all_modifiers = [])
    {
        return $tagdata;
    }
}

class FluidFieldTagRelationshipParserFactoryStub
{
    private $parser;

    public function __construct($parser)
    {
        $this->parser = $parser;
    }

    public function create(...$args)
    {
        return $this->parser;
    }
}

class FluidFieldTagRelationshipParserStub
{
    public function parse($entryId, $tagdata, $channel)
    {
        return str_replace('{content status="open"}RELATED ENTRY{/content}', 'RELATED ENTRY', $tagdata);
    }
}

class FluidFieldTagFrontEditStub
{
    public function prepareTemplate($tagdata, $prefix = '')
    {
        return $tagdata;
    }
}

class FluidFieldTagFunctionsStub
{
    public $calls = [];

    public function prep_conditionals($tagdata, $vars = [])
    {
        $this->calls[] = compact('tagdata', 'vars');

        return preg_replace_callback(
            '/\{if\s+([^\s}]+)\}(.*?)\{\/if\}/s',
            function ($match) use ($vars) {
                return !empty($vars[$match[1]]) ? $match[2] : '';
            },
            $tagdata
        );
    }
}

// EOF

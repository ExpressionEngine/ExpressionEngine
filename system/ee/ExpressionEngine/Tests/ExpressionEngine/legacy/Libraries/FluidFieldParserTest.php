<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

require_once __DIR__ . '/../../../eeObjectMock.php';

class FluidFieldParserTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('SYSPATH')) {
            define('SYSPATH', realpath(__DIR__ . '/../../../../../../system/') . '/');
        }

        if (!defined('BASEPATH')) {
            define('BASEPATH', SYSPATH . 'ee/legacy/');
        }

        if (!defined('LD')) {
            define('LD', '{');
        }

        if (!defined('RD')) {
            define('RD', '}');
        }

        require_once BASEPATH . 'libraries/Fluid_field_parser.php';
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testRewriteFluidTagsAsConditionalsRewritesOnlyProvidedFields(): void
    {
        $parser = $this->makeParser();
        $tagdata = '{fluid:text}Text{/fluid:text}{fluid:image}Image{/fluid:image}';

        $result = $this->invokePrivateMethod(
            $parser,
            'rewriteFluidTagsAsConditionals',
            [$tagdata, ['fluid:text']]
        );

        $this->assertSame('{if fluid:text}Text{/if}{fluid:image}Image{/fluid:image}', $result);
    }

    public function testRewriteFluidTagsAsConditionalsReturnsOriginalTagdataWhenFieldListIsEmpty(): void
    {
        $parser = $this->makeParser();
        $tagdata = '{fluid:text}Text{/fluid:text}';

        $result = $this->invokePrivateMethod(
            $parser,
            'rewriteFluidTagsAsConditionals',
            [$tagdata, []]
        );

        $this->assertSame($tagdata, $result);
    }

    public function testReplaceInnerFieldsPairsReplacesPairsWithStablePlaceholdersAndStoresMap(): void
    {
        $pairs = [
            [null, null, [], '{fields}{fluid:text}{/fields}'],
            [null, null, [], '{fields order="desc"}{fluid:image}{/fields}'],
        ];

        ee()->setMock('api_channel_fields', $this->buildApiChannelFieldsMock($pairs));

        $parser = $this->makeParser();
        $tagdata = 'A {fields}{fluid:text}{/fields} B {fields order="desc"}{fluid:image}{/fields} C';

        $result = $this->invokePrivateMethod($parser, 'replaceInnerFieldsPairs', [$tagdata]);

        $this->assertSame('A {!-- ff:fields:0 --} B {!-- ff:fields:1 --} C', $result);
        $this->assertSame(
            [
                0 => '{fields}{fluid:text}{/fields}',
                1 => '{fields order="desc"}{fluid:image}{/fields}',
            ],
            $this->getPrivateProperty($parser, 'replacements')
        );
    }

    public function testReplaceInnerFieldsPairsReturnsOriginalTagdataWhenNoPairsExist(): void
    {
        ee()->setMock('api_channel_fields', $this->buildApiChannelFieldsMock([]));

        $parser = $this->makeParser();
        $tagdata = '{fluid:text}Text{/fluid:text}';

        $result = $this->invokePrivateMethod($parser, 'replaceInnerFieldsPairs', [$tagdata]);

        $this->assertSame($tagdata, $result);
        $this->assertSame([], $this->getPrivateProperty($parser, 'replacements'));
    }

    public function testRestoreInnerFieldsPairsRestoresStoredChunks(): void
    {
        ee()->setMock(
            'api_channel_fields',
            $this->buildApiChannelFieldsMock([
                [null, null, [], '{fields}{fluid:text}{/fields}'],
            ])
        );

        $parser = $this->makeParser();
        $replaced = $this->invokePrivateMethod($parser, 'replaceInnerFieldsPairs', ['X {fields}{fluid:text}{/fields} Y']);

        $result = $this->invokePrivateMethod($parser, 'restoreInnerFieldsPairs', [$replaced]);

        $this->assertSame('X {fields}{fluid:text}{/fields} Y', $result);
    }

    public function testRestoreInnerFieldsPairsReturnsOriginalWhenThereAreNoReplacements(): void
    {
        $parser = $this->makeParser();
        $tagdata = 'no placeholders here';

        $result = $this->invokePrivateMethod($parser, 'restoreInnerFieldsPairs', [$tagdata]);

        $this->assertSame($tagdata, $result);
    }

    private function makeParser(): \Fluid_field_parser
    {
        return new \Fluid_field_parser();
    }

    private function buildApiChannelFieldsMock(array $pairs)
    {
        return new class($pairs) {
            private $pairs;

            public function __construct(array $pairs)
            {
                $this->pairs = $pairs;
            }

            public function get_pair_field($tagdata, $name)
            {
                return $this->pairs;
            }
        };
    }

    private function invokePrivateMethod($object, string $method, array $args = [])
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($object, $args);
    }

    private function getPrivateProperty($object, string $property)
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }
}

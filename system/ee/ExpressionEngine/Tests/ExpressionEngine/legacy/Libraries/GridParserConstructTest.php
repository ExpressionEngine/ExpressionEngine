<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

use PHPUnit\Framework\TestCase;

class GridParserConstructTest extends TestCase
{
    /**
     * Load the legacy parser class before tests run.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        require_once BASEPATH . 'libraries/Grid_parser.php';
    }

    /**
     * Ensure constructor seeds all supported Grid aggregate and row modifiers.
     *
     * @return void
     */
    public function testConstructorInitializesExpectedModifiersInStableOrder(): void
    {
        $parser = new \Grid_parser();

        $this->assertSame(
            [
                'next_row',
                'prev_row',
                'total_rows',
                'table',
                'sum',
                'average',
                'lowest',
                'highest',
            ],
            $parser->modifiers
        );
    }

    /**
     * Ensure reserved names always include modifiers plus parser-only tag names.
     *
     * @return void
     */
    public function testConstructorBuildsReservedNamesFromModifiersAndReservedTags(): void
    {
        $parser = new \Grid_parser();

        $this->assertSame(
            [
                'next_row',
                'prev_row',
                'total_rows',
                'table',
                'sum',
                'average',
                'lowest',
                'highest',
                'switch',
                'count',
                'index',
                'field_total_rows',
            ],
            $parser->reserved_names
        );
        $this->assertCount(count(array_unique($parser->reserved_names)), $parser->reserved_names);
    }

    /**
     * Ensure constructor creates per-instance arrays and does not leak mutations.
     *
     * @return void
     */
    public function testConstructorCreatesIndependentInstanceState(): void
    {
        $first = new \Grid_parser();
        $first->modifiers[] = 'custom_modifier';
        $first->reserved_names[] = 'custom_reserved';

        $second = new \Grid_parser();

        $this->assertNotContains('custom_modifier', $second->modifiers);
        $this->assertNotContains('custom_reserved', $second->reserved_names);
        $this->assertSame(
            array_merge($second->modifiers, ['switch', 'count', 'index', 'field_total_rows']),
            $second->reserved_names
        );
    }
}

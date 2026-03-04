<?php

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once __DIR__ . '/../../../../../Addons/structure/sql.structure.php';

use PHPUnit\Framework\TestCase;

class StructureLeafTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();
    }

    public function testAddChildDepthAndRowDepthHandling()
    {
        $root = $this->leaf(0, 'root');
        $child = $this->leaf(1, 'child');

        $root->add_child($child);
        $root->add_child($child);

        $this->assertCount(1, $root->children);
        $this->assertSame(0, $root->depth());
        $this->assertSame(1, $child->depth());

        $root->add_row_depth();
        $this->assertSame(0, $root->row['depth']);
        $this->assertSame(1, $child->row['depth']);
    }

    public function testPruneChildrenAndAncestors()
    {
        $root = $this->leaf(0, 'root');
        $child = $this->leaf(1, 'child');
        $grandchild = $this->leaf(2, 'grandchild');

        $root->add_child($child);
        $child->add_child($grandchild);

        $root->prune_children(1);
        $this->assertCount(1, $root->children);
        $this->assertCount(0, $child->children);

        $root->add_child($child);
        $child->add_child($grandchild);
        $grandchild->prune_ancestors(1);
        $this->assertNull($root->parent);
        $this->assertNull($child->parent);
    }

    public function testSelectivePruneAndValueChecks()
    {
        $root = $this->leaf(0, 'root', 0, 1, 10, 'open');
        $a = $this->leaf(1, 'a', 0, 2, 3, 'open');
        $b = $this->leaf(2, 'b', 0, 4, 9, 'closed');
        $bChild = $this->leaf(3, 'b-child', 2, 5, 6, 'closed');

        $root->add_child($a);
        $root->add_child($b);
        $b->add_child($bChild);

        $this->assertFalse($a->is_of_value('status', ['open'], false));
        $this->assertTrue($a->is_of_value('status', ['closed'], false));
        $this->assertTrue($a->is_of_value('status', ['open'], true));

        $root->selective_prune('status', ['closed'], false);
        $this->assertCount(1, $root->children);
        $this->assertSame(2, array_values($root->children)[0]->row['entry_id']);

        $root = $this->leaf(0, 'root', 0, 1, 10, 'open');
        $a = $this->leaf(1, 'a', 0, 2, 3, 'open');
        $b = $this->leaf(2, 'b', 0, 4, 9, 'closed');
        $bChild = $this->leaf(3, 'b-child', 2, 5, 6, 'closed');
        $root->add_child($a);
        $root->add_child($b);
        $b->add_child($bChild);

        $root->selective_prune_alt('status', ['open'], true);
        $this->assertCount(1, $root->children);
        $this->assertSame(2, array_values($root->children)[0]->row['entry_id']);
        $this->assertCount(1, array_values($root->children)[0]->children);
    }

    public function testFindAncestorHasAncestorAndPurifyBloodline()
    {
        $root = $this->leaf(0, 'root');
        $a = $this->leaf(1, 'a', 0, 2, 7);
        $b = $this->leaf(2, 'b', 0, 8, 11);
        $aChild = $this->leaf(3, 'a-child', 1, 3, 4);
        $aChild2 = $this->leaf(4, 'a-child2', 1, 5, 6);
        $bChild = $this->leaf(5, 'b-child', 2, 9, 10);

        $root->add_child($a);
        $root->add_child($b);
        $a->add_child($aChild);
        $a->add_child($aChild2);
        $b->add_child($bChild);

        $this->assertSame($aChild2, $root->find_ancestor('entry_id', 4));
        $this->assertFalse($root->find_ancestor('entry_id', 999));

        $this->assertFalse($root->has_ancestor(new structure_leaf(['missing' => 1])));
        $this->assertTrue($root->has_ancestor($aChild2));

        $aChild->purify_bloodline();
        $this->assertCount(2, $a->children);
        $this->assertSame(3, array_values($a->children)[0]->row['entry_id']);
        $this->assertCount(2, $root->children);
        $this->assertCount(0, $b->children);
    }

    public function testBuildFromResultsGetResultsAndRenderers()
    {
        $rows = [
            ['entry_id' => 0, 'parent_id' => 0, 'title' => 'root', 'lft' => 1, 'rgt' => 8],
            ['entry_id' => 1, 'parent_id' => 0, 'title' => 'a', 'lft' => 2, 'rgt' => 5],
            ['entry_id' => 2, 'parent_id' => 1, 'title' => 'a-child', 'lft' => 3, 'rgt' => 4],
            ['entry_id' => 99, 'parent_id' => 42, 'title' => 'skipped', 'lft' => 6, 'rgt' => 7],
            ['entry_id' => 3, 'parent_id' => 0, 'title' => 'b', 'lft' => 6, 'rgt' => 7],
        ];

        $tree = structure_leaf::build_from_results($rows);
        $results = $tree->get_results();

        $ids = array_map(function ($row) {
            return $row['entry_id'];
        }, $results);

        $this->assertSame([1, 2, 3], $ids);

        ob_start();
        $tree->print_branch();
        $printOut = ob_get_clean();
        $this->assertStringContainsString('(0) root', $printOut);
        $this->assertStringContainsString('(2)   a-child', $printOut);

        ob_start();
        $tree->list_branch();
        $listOut = ob_get_clean();
        $this->assertStringContainsString('<ul>', $listOut);
        $this->assertStringContainsString('(1) a', $listOut);
        $this->assertStringContainsString('(3) b', $listOut);
    }

    private function leaf($entryId, $title, $parentId = 0, $lft = 0, $rgt = 0, $status = 'open')
    {
        return new structure_leaf([
            'entry_id' => $entryId,
            'parent_id' => $parentId,
            'title' => $title,
            'lft' => $lft,
            'rgt' => $rgt,
            'status' => $status,
        ]);
    }
}

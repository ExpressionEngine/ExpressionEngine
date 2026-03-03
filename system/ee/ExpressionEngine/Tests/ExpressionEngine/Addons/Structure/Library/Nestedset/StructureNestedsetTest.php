<?php

require_once __DIR__ . '/../../../../../../Addons/structure/libraries/nestedset/structure_nestedset.php';

use PHPUnit\Framework\TestCase;

class StructureNestedsetAdapterSpy
{
    public $calls = [];

    public function insertNode($left, $right, array $extra = array())
    {
        $this->calls[] = ['insertNode', $left, $right, $extra];
    }

    public function shift($first, $delta)
    {
        $this->calls[] = ['shift', $first, $delta];
    }

    public function shiftRange($first, $last, $delta)
    {
        $this->calls[] = ['shiftRange', $first, $last, $delta];
        return ['left' => $first + $delta, 'right' => $last + $delta];
    }

    public function getTree($lr, $includeNode = true, $directOnly = false)
    {
        $this->calls[] = ['getTree', $lr, $includeNode, $directOnly];
        return ['ok' => true];
    }

    public function getNodeBySet($left, $right)
    {
        $this->calls[] = ['getNodeBySet', $left, $right];
        return ['id' => 99, 'left' => $left, 'right' => $right];
    }

    public function getNodeById($id)
    {
        $this->calls[] = ['getNodeById', $id];
        return ['id' => $id, 'left' => 2, 'right' => 3];
    }

    public function getDepth($node)
    {
        $this->calls[] = ['getDepth', $node];
        return 2;
    }

    public function deleteNodeTree($left, $right)
    {
        $this->calls[] = ['deleteNodeTree', $left, $right];
        return true;
    }
}

class StructureNestedsetTest extends TestCase
{
    public function testInsertHelpersCallAdapter()
    {
        $adapter = new StructureNestedsetAdapterSpy();
        $nestedset = new Structure_Nestedset($adapter);

        $nestedset->newRoot(['entry_id' => 1]);
        $lastChild = $nestedset->newLastChild(6, ['entry_id' => 2]);
        $prev = $nestedset->newPrevSibling(4, ['entry_id' => 3]);
        $next = $nestedset->newNextSibling(10, ['entry_id' => 4]);

        $this->assertSame(['left' => 6, 'right' => 7], $lastChild);
        $this->assertSame([4, 5], $prev);
        $this->assertSame([11, 12], $next);

        $this->assertSame(['insertNode', 1, 2, ['entry_id' => 1]], $adapter->calls[0]);
        $this->assertSame(['shift', 6, 2], $adapter->calls[1]);
        $this->assertSame(['insertNode', 6, 7, ['entry_id' => 2]], $adapter->calls[2]);
    }

    public function testReadHelpersAndGetNodeBranches()
    {
        $adapter = new StructureNestedsetAdapterSpy();
        $nestedset = new Structure_Nestedset($adapter);

        $this->assertSame(['ok' => true], $nestedset->getTree(['left' => 1, 'right' => 4], false, true));
        $this->assertSame(['id' => 99, 'left' => 1, 'right' => 0], $nestedset->getNode(['left' => 1]));
        $this->assertSame(['id' => 99, 'left' => 0, 'right' => 9], $nestedset->getNode(['right' => 9]));
        $this->assertSame(['id' => 123, 'left' => 2, 'right' => 3], $nestedset->getNode(123));
        $this->assertSame(['id' => [], 'left' => 2, 'right' => 3], $nestedset->getNode([]));
        $this->assertSame(2, $nestedset->getDepth(123));
    }

    public function testMoveTreeCoversBothShiftAdjustmentBranches()
    {
        $adapter = new StructureNestedsetAdapterSpy();
        $nestedset = new Structure_Nestedset($adapter);

        $movedBackward = $nestedset->moveTree(['left' => 10, 'right' => 11], 5);
        $this->assertSame(['left' => 5, 'right' => 6], $movedBackward);

        $movedForward = $nestedset->moveTree(['left' => 2, 'right' => 3], 8);
        $this->assertSame(['left' => 6, 'right' => 7], $movedForward);
    }

    public function testMoveWrappersDeleteAndChildrenCounter()
    {
        $adapter = new StructureNestedsetAdapterSpy();
        $nestedset = new Structure_Nestedset($adapter);

        $this->assertSame(['left' => 7, 'right' => 8], $nestedset->moveToNextSibling(['left' => 2, 'right' => 3], ['right' => 8]));
        $this->assertSame(['left' => 4, 'right' => 5], $nestedset->moveToPrevSibling(['left' => 2, 'right' => 3], ['left' => 6]));
        $this->assertSame(['left' => 2, 'right' => 3], $nestedset->moveToFirstChild(['left' => 8, 'right' => 9], ['left' => 1]));
        $this->assertSame(['left' => 5, 'right' => 6], $nestedset->moveToLastChild(['left' => 2, 'right' => 3], ['right' => 7]));
        $this->assertTrue($nestedset->deleteNode(['left' => 4, 'right' => 5]));

        set_error_handler(function () {
            return true;
        });
        $children = $nestedset->getNumberOfChildren(['left' => 3, 'right' => 9]);
        restore_error_handler();

        $this->assertSame(4, $children);
    }
}

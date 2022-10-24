<?php

class Structure_Nestedset
{
    private $adapter;

    public function __construct($adapter)
    {
        $this->adapter = $adapter;
    }

    public function newRoot(array $extra = array())
    {
        $this->adapter->insertNode(1, 2, $extra);
    }

    public function newLastChild($pRight, array $extra = array())
    {
        $this->adapter->shift($pRight, 2);
        $this->adapter->insertNode($pRight, $pRight + 1, $extra);
        return array(
            'left'  => $pRight,
            'right' => $pRight + 1
        );
    }

    public function newPrevSibling($left, $extra)
    {
        $this->adapter->shift($left, 2);
        $this->adapter->insertNode($left, $left + 1, $extra);
        return array($left, $left + 1);
    }

    public function newNextSibling($right, $extra)
    {
        $this->adapter->shift($right + 1, 2);
        $this->adapter->insertNode($right + 1, $right + 2, $extra);
        return array($right + 1, $right + 2);
    }

    public function getTree($lr, $includeNode = true, $directOnly = false)
    {
        return $this->adapter->getTree($lr, $includeNode, $directOnly);
    }

    public function getNode($lr)
    {
        if (is_array($lr) and ! empty($lr)) {
            if (!isset($lr['left'])) {
                $lr['left'] = 0;
            }
            if (!isset($lr['right'])) {
                $lr['right'] = 0;
            }

            $node = $this->adapter->getNodeBySet($lr['left'], $lr['right']);
        } else {
            $node = $this->adapter->getNodeById($lr);
        }
        return $node;
    }

    /**
     * Get depth of a node (root = 0)
     * @param mixed $node array with left & right key values, or int with id
     * @return int
     */
    public function getDepth($node)
    {
        return $this->adapter->getDepth($node);
    }

    public function moveTree($lr, $toL)
    {
        $treeSize = $lr['right'] - $lr['left'] + 1;

        $this->adapter->shift($toL, $treeSize);

        // node was shifted too?
        if ($lr['left'] >= $toL) {
            $lr['left']  += $treeSize;
            $lr['right'] += $treeSize;
        }

        // move tree
        $newLr = $this->adapter->shiftRange($lr['left'], $lr['right'], $toL - $lr['left']);

        // correct values after source
        $this->adapter->shift($lr['right'] + 1, -$treeSize);

        // dst was shifted too?
        if ($lr['left'] <= $toL) {
            $newLr['left']  -= $treeSize;
            $newLr['right'] -= $treeSize;
        }

        return $newLr;
    }

    public function moveToNextSibling($lr, $ref)
    {
        return $this->moveTree($lr, $ref['right'] + 1);
    }

    public function moveToPrevSibling($lr, $ref)
    {
        return $this->moveTree($lr, $ref['left']);
    }

    public function moveToFirstChild($lr, $ref)
    {
        return $this->moveTree($lr, $ref['left'] + 1);
    }

    public function moveToLastChild($lr, $ref)
    {
        return $this->moveTree($lr, $ref['right']);
    }

    public function deleteNode($lr, $children = true)
    {
        return $this->adapter->deleteNodeTree($lr['left'], $lr['right']);
    }


    public function getNumberOfChildren($lr)
    {
        return ($lr['right'] - $node['left'] - 1) / 2;
    }
}

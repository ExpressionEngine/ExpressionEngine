<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/*

 THIS FILE CONTAINS:

 EE_Tree		 - singleton tree builder
 EE_TreeNode	 - main tree object (returned from EE_Tree::load)
 EE_TreeIterator - iteration helper (returned from EE_TreeNode::flat_iterator)

*/

/**
 * Tree Factory
 */
class EE_Tree
{
    /**
     * Tree Factory
     *
     * Takes an array of rows that each have an id and parent id (as you
     * would get from the db) and returns a tree structure
     *
     * @param data - array of array('unique_id' => x, 'parent_id' => y, ...data)
     * @param config array
     *		- key : data's unique id key
     *		- parent_id: data's parent_id key
     *
     * @return Object<ImmutableTree>
     */
    public function from_list($data, array $conf = null)
    {
        $conf = array_merge(
            array(
                'id' => 'id',
                'parent' => 'parent_id',
                'class_name' => 'EE_TreeNode'
            ),
            (array) $conf
        );

        if (! isset($conf['name_key'])) {
            $conf['name_key'] = $conf['id'];
        }

        return $this->_build_tree($data, $conf);
    }

    /**
     * Flatten the tree to a list of data objects.
     *
     * @return array similar to what was passed to EE_Tree::load
     */
    public function to_list(EE_TreeNode $tree)
    {
        $it = $this->preorder_iterator();
        $result = array();

        foreach ($it as $node) {
            $result[] = $node->data();
        }

        return $result;
    }

    /**
     * Tree Builder
     *
     * Re-sorts the data from from_list() and turns it into two datastructures:
     *
     * An array of tree root nodes, with children in the __children__ key
     * of their respective parents. Thus forming a tree as a nested array.
     *
     * A lookup table of id => row, where each item is actually a reference
     * into the tree. This way we can do quick by-index lookups.
     *
     * @param data - array of array('unique_id' => x, 'parent_id' => y, ...data)
     * @param unique id key
     * @param parent id key
     *
     * @return Object<ImmutableTree>
     */
    protected function _build_tree($data, $conf)
    {
        $nodes = array();

        $child_key = $conf['id'];
        $parent_key = $conf['parent'];

        $name = $conf['name_key'];
        $class = $conf['class_name'];

        // First we create a lookup table of id => object
        // This lets us build the tree on references which
        // will in turn allow for quick subtree lookup.
        foreach ($data as $row) {
            $id = (is_object($row)) ? $row->$child_key : $row[$child_key];
            $node_name = (is_object($row)) ? $row->$name : $row[$name];
            $nodes[$id] = new $class($node_name, $row);
        }

        $tree = new EE_TreeNode('__root__');

        // And now build the actual tree by assigning children
        foreach ($data as $row) {
            $parent = (is_object($row)) ? $row->$parent_key : $row[$parent_key];
            $node = (is_object($row)) ? $nodes[$row->$child_key] : $nodes[$row[$child_key]];

            if (isset($nodes[$parent])) {
                $nodes[$parent]->add($node);
            } else {
                $tree->add($node);
            }
        }

        return $tree;
    }
}
// END CLASS

/**
 * Tree Node
 *
 * If you're completely new to this ideas:
 * @see http://xlinux.nist.gov/dads/HTML/tree.html
 */
class EE_TreeNode
{
    protected $name;
    protected $data;

    protected $parent;
    protected $children;
    protected $children_names;

    private $_frozen = false;

    public function __construct($name, $payload = null)
    {
        $this->name = $name;
        $this->data = $payload;

        $this->children = array();
        $this->children_names = array();
    }

    /**
     * Retrieve the payload data.
     *
     * If the payload is an array we treat the entire object as an
     * accessor to the payload. Otherwise the key must be "data" to
     * mimic regular object access.
     *
     * @return void
     */
    public function __get($key)
    {
        if (is_array($this->data) && isset($this->data[$key])) {
            return $this->data[$key];
        }
        if ($key == 'data') {
            return $this->data;
        }

        throw new InvalidArgumentException('Payload cannot be retrieved.');
    }

    /**
     * Change the payload data.
     *
     * If they payload is an array we treat the entire object as an
     * accessor to the payload. Otherwise the key must be "data" to
     * mimic regular object access.
     *
     * @return void
     */
    public function __set($key, $value)
    {
        if ($this->_frozen) {
            throw new RuntimeException('Cannot modify payload. Tree node is frozen.');
        }

        if (is_array($this->data)) {
            $this->data[$key] = $value;
        } elseif ($key == 'data') {
            $this->data = $value;
        } else {
            throw new InvalidArgumentException('Payload cannot be modified.');
        }
    }

    /**
     * Post-process node cloning
     *
     * Cloning needs to unfreeze the node for the benefit of the
     * subtree_copy method. Not to mention dev sanity.
     *
     * @return void
     */
    public function __clone()
    {
        $this->_frozen = false;
    }

    // Public Setters

    /**
     * Add a child node to the current node.
     *
     * Notifies the child of its parent and adds the child name to
     * the child name array. Does not enforce unique names since it
     * may be desireable to have non-unique named children. It's on
     * the developer to not rely on the get() method in that case
     *
     * @return void
     */
    public function add(EE_TreeNode $child)
    {
        if ($child == $this) {
            throw new RuntimeException('Cannot add tree node to itself.');
        }

        if ($this->_frozen) {
            throw new RuntimeException('Cannot add child. Tree node is frozen.');
        }

        $this->children[] = $child;
        $this->children_names[$child->name] = $child;
        $child->_set_parent($this);
    }

    // Getters

    /**
     * Get the node's name
     *
     * @return <string?> name
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get the node's payload
     *
     * @return <mixed> payload
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Get the node's depth relative to its root, where the root's
     * depth is 0.
     *
     * @return <Integer> depth
     */
    public function depth()
    {
        if ($this->is_root()) {
            return 0;
        }

        return 1 + $this->parent()->depth();
    }

    // Traversal

    /**
     * Get the tree's root node
     *
     * If the current node is not a root node, we move our
     * way up until we have a root.
     *
     * @return <EE_TreeNode>
     */
    public function root()
    {
        $root = $this;

        while (! $root->is_root()) {
            $root = $root->parent();
        }

        return $root;
    }

    /**
     * Get all of the node's children
     *
     * @return array[<EE_TreeNode>s]
     */
    public function children()
    {
        return $this->children;
    }

    /**
     * Get the node's first child
     *
     * @return <EE_TreeNode>
     */
    public function first_child()
    {
        return $this->children[0];
    }

    /**
     * Get the node's parent
     *
     * @return <EE_TreeNode>
     */
    public function parent()
    {
        return $this->parent;
    }

    /**
     * Get all of a node's siblings
     *
     * @return array[<EE_TreeNode>s]
     */
    public function siblings()
    {
        $siblings = array();

        if (! $this->is_root()) {
            foreach ($this->parent()->children() as $sibling) {
                if ($sibling != $this) {
                    $siblings[] = $sibling;
                }
            }
        }

        return $siblings;
    }

    // Utility

    /**
     * Check if the node has parents
     *
     * @return boolean
     */
    public function is_root()
    {
        return ! isset($this->parent);
    }

    /**
     * Check if the node has children
     *
     * @return boolean
     */
    public function is_leaf()
    {
        return count($this->children) == 0;
    }

    /**
     * Freeze the node
     *
     * Prevents data and child manipulations. Cloning a frozen node will
     * unfreeze it.
     *
     * @return void
     */
    public function freeze()
    {
        $this->_frozen = true;
    }

    /**
     * Get a child by name
     *
     * You are responsible for adding children with unique names. If you
     * do not, then this method will return the last child node of the
     * given name.
     *
     * @return <EE_TreeNode>
     */
    public function get($name)
    {
        return $this->children_names[$name];
    }

    /**
     * Create a subtree on this node.
     *
     * Clones the current node to turn it into a root node off the
     * original tree.
     *
     * This is a *shallow* copy! The root node you receive is a clone, but
     * its children remain on the tree. If you need a clone for anything
     * other than traversal, consider using the subtree_copy() method instead.
     *
     * @return <EE_TreeNode>
     */
    public function subtree()
    {
        $root = clone $this;
        $root->parent = null;

        return $root;
    }

    /**
     * Create a full subtree copy from this node down.
     *
     * Clones the current node and all of its children. This is a deep
     * copy, everything will be cloned. If all you need is a new root
     * for traversal, consider using subtree() instead.
     *
     * @return <EE_TreeNode>
     */
    public function subtree_copy()
    {
        $class = get_class($this);
        $root = new $class($this->name(), $this->data());

        foreach ($this->children() as $node) {
            $root->add($node->subtree_copy());
        }

        return $root;
    }

    /**
     * Preorder Tree Iterator
     *
     * Creates a preorder tree iterator from the current node down.
     *
     * @return <RecursiveIteratorIterator> with SELF_FIRST
     */
    public function preorder_iterator()
    {
        return new RecursiveIteratorIterator(
            new EE_TreeIterator(array($this)),
            RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * Postorder Tree Iterator
     *
     * Creates a postorder tree iterator from the current node down.
     *
     * @return <RecursiveIteratorIterator> with CHILD_FIRST
     */
    public function postorder_iterator()
    {
        return new RecursiveIteratorIterator(
            new EE_TreeIterator(array($this)),
            RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    /**
     * Leaf Iterator
     *
     * Iterates across all the leaf nodes
     *
     * @return <RecursiveIteratorIterator> with LEAVES_ONLY
     */
    public function leaf_iterator()
    {
        return new RecursiveIteratorIterator(
            new EE_TreeIterator(array($this)),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    /**
     * Breadth First Iterator
     *
     * Iterates across all nodes in a level-by-level fashion
     *
     * @return <EE_BreadthFirstIterator>
     */
    public function breadth_first_iterator()
    {
        return new EE_BreadthFirstIterator(
            new EE_TreeIterator(array($this))
        );
    }

    /**
     * Set parent
     *
     * Links up the parent node for upwards traversal. Should only ever
     * be called from add() to maintain referential integrity.
     *
     * In theory add() has access to the property directly, but sometimes
     * it's useful to override this with additional functionality.
     *
     * @param <EE_TreeNode> New parent node
     * @return void
     */
    protected function _set_parent(EE_TreeNode $parent)
    {
        $this->parent = $parent;
    }
}
// END CLASS

/**
 * Tree Iterator
 */
class EE_TreeIterator extends RecursiveArrayIterator
{
    protected $ref;

    /**
     * Override RecursiveArrayIterator's child detection method.
     * We really don't want to count object properties as children.
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function hasChildren()
    {
        return ! $this->current()->is_leaf();
    }

    /**
     * Override RecursiveArrayIterator's get child method to skip
     * ahead into the children array and not try to iterate over the
     * over the public name property.
     *
     * @return Object<EE_TreeIterator>
     */
    #[\ReturnTypeWillChange]
    public function getChildren()
    {
        $children = $this->current()->children();

        // Using ref as per PHP source
        if (empty($this->ref)) {
            $this->ref = new ReflectionClass($this);
        }

        return $this->ref->newInstance($children);
    }
}
// END CLASS

/**
 * Breadth First Iterator
 */
class EE_BreadthFirstIterator implements OuterIterator
{
    protected $_level;
    protected $_iterator;
    protected $_first_iterator;	// needed for rewind

    protected $_queue = array();

    public function __construct(RecursiveIterator $it)
    {
        $this->_level = 0;
        $this->_iterator = $it;
        $this->_first_iterator = $it;
    }

    /**
     * Current Iterator Entry
     *
     * @return iterator entry of the current inner iterator
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->_iterator->current();
    }

    /**
     * Current Iterator Key
     *
     * @return iterator key of the current inner iterator
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->_iterator->key();
    }

    /**
     * Next Iterator Step
     *
     * Standard level by level iterator using a queue to remember where
     * the children are.
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        if ($this->_iterator->hasChildren()) {
            $this->_queue[] = array($this->_level + 1, $this->_iterator->getChildren());
        }

        $this->_iterator->next();
    }

    /**
     * Rewind the Iterator
     *
     * All the subiterators are rewound when they're exhausted so we only
     * have to worry about the current one.
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->_level = 0;
        $this->_queue = array();

        $this->_iterator->rewind();
        $this->_iterator = $this->_first_iterator;
    }

    /**
     * Find a valid iterator entry if it exists
     *
     * If we have exhausted the current iterator then we need to move on
     * to the next one on the queue. If they're all exhausted we're out of
     * entries.
     *
     * @return boolean iterator is valid
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        if ($this->_iterator->valid()) {
            return true;
        }

        $this->_iterator->rewind(); // we're at the end, @todo this is a little sloppy

        if (count($this->_queue)) {
            list($this->_level, $this->_iterator) = array_shift($this->_queue);

            return $this->_iterator->valid();
        }

        return false;
    }

    /**
     * Get internal iterator
     *
     * To the user this iterator is supposed to be mostly transparent.
     * This is here to satsifty the outeriterator contract. I can't think
     * of a good reason you would want to use it.
     *
     * @return <RecursiveIterator> current sub iterator
     */
    #[\ReturnTypeWillChange]
    public function getInnerIterator()
    {
        return $this->_iterator();
    }

    /**
     * Get iteration depth
     *
     * Retrieve the per level depth of the iterator. Using the same method
     * contract as RecursiveIteratorIterator for consistency.
     *
     * @return Integer iteration depth
     */
    public function getDepth()
    {
        return $this->_level;
    }
}
// END CLASS

// EOF

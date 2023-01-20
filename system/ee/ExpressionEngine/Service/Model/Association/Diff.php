<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Association;

/**
 * Running diff of relationship changes
 */
class Diff
{
    private $parent;
    private $relation;

    protected $added = array();
    protected $removed = array();
    protected $was_set = false;

    public function __construct($parent, $relation)
    {
        $this->parent = $parent;
        $this->relation = $relation;
    }

    public function reset()
    {
        $this->added = array();
        $this->removed = array();
        $this->was_set = false;
    }

    public function wasSet()
    {
        $this->was_set = true;
    }

    public function add($model)
    {
        $hash = spl_object_hash($model);

        if (! $this->attemptFastUndoRemove($hash)) {
            $this->added[$hash] = $model;
        }
    }

    public function remove($model)
    {
        $hash = spl_object_hash($model);

        if (! $this->attemptFastUndoAdd($hash)) {
            $this->removed[$hash] = $model;
        }
    }

    protected function attemptFastUndoRemove($hash)
    {
        if (array_key_exists($hash, $this->removed)) {
            unset($this->removed[$hash]);

            return true;
        }

        return false;
    }

    protected function attemptFastUndoAdd($hash)
    {
        if (array_key_exists($hash, $this->added)) {
            unset($this->added[$hash]);

            return true;
        }

        return false;
    }

    public function commit()
    {
        // when setting, remove everything not in the new set
        if ($this->was_set) {
            $this->relation->set($this->parent, $this->added);
        } else {
            if (! empty($this->removed)) {
                $this->relation->drop($this->parent, $this->removed);
            }

            $this->relation->insert($this->parent, $this->added);
        }

        $this->reset();
    }
}

// EOF

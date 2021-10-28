<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Model\Association;

use ExpressionEngine\Service\Model\Collection;
use ExpressionEngine\Service\Model\Model;

/**
 * Model Service: To Many Association
 */
class ToMany extends Association
{
    public function fill($related, $_skip_inverse = false)
    {
        if (is_array($related)) {
            $related = new Collection($related);
        }

        if ($related instanceof Model) {
            $related = new Collection(array($related));
        }

        if ($related instanceof Collection) {
            $this->ensureAssociation($related);
        }

        return parent::fill($related, $_skip_inverse);
    }

    public function get()
    {
        $result = parent::get();

        if (! isset($result)) {
            $this->ensureCollection();

            return $this->related;
        }

        return $result;
    }

    public function foreignKeyChanged($value)
    {
        // nada
    }

    protected function ensureExists($model)
    {
        $this->ensureCollection();

        if (! $this->has($model)) {
            $this->related->add($model, false);
            parent::ensureExists($model);
        }
    }

    protected function ensureDoesNotExist($model)
    {
        if ($this->has($model)) {
            $this->related->removeElement($model);
            parent::ensureDoesNotExist($model);
        }
    }

    protected function has($model)
    {
        if (is_null($this->related)) {
            return false;
        }

        foreach ($this->related as $m) {
            if ($m === $model) {
                return true;
            }

            // Existing models queried independently may fail the above check
            if ($m->getId() && $model->getId() &&
                $m->getId() === $model->getId() &&
                get_class($m) == get_class($model)) {
                return true;
            }
        }

        return false;
    }

    protected function ensureCollection()
    {
        if (is_null($this->related)) {
            $this->related = new Collection();
        }

        $this->ensureAssociation($this->related);
    }

    protected function ensureAssociation(Collection $related)
    {
        if ($related->getAssociation() !== $this) {
            $related->setAssociation($this);
        }
    }
}

// EOF

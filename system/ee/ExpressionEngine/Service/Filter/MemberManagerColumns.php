<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Filter;

use ExpressionEngine\Library\CP\URL;
use ExpressionEngine\Service\View\ViewFactory;
use ExpressionEngine\Model\Content\StructureModel;

/**
 * Columns Filter for Member Manager
 */
class MemberManagerColumns extends Columns
{
    //view_id might hold view type here
    public function __construct(array $columns = array(), StructureModel $primaryRole = null, $view_id_or_type = null)
    {
        parent::__construct($columns, $primaryRole, $view_id_or_type);

        $this->default_value = ['member_id', 'username', 'email', 'roles', 'join_date', 'last_visit'];
    }

    // get columns from view
    public function value()
    {
        $value = '';

        $role_id = 0;
        if (!empty(ee()->uri->segment(3))) {
            if (is_numeric(ee()->uri->segment(3))) {
                $role_id = (int) ee()->uri->segment(3);
            } elseif (ee()->uri->segment(3) == 'banned') {
                $role_id = 2;
            } elseif (ee()->uri->segment(3) == 'pending') {
                $role_id = 4;
            }
        } elseif (!empty(ee()->input->get('role_id')) && is_numeric(ee()->input->get('role_id'))) {
            $role_id = (int) ee()->input->get('role_id');
        }

        $query = ee('Model')->get('MemberManagerView')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->filter('role_id', $role_id);
        $view = $query->first(true);

        if (!empty($view)) {
            $value = $view->getColumns();
        }

        if (empty($value)) {
            $value = $this->default_value;
        }

        return $value;
    }
}

// EOF

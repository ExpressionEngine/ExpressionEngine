<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Filter;

use ExpressionEngine\Library\CP\URL;
use ExpressionEngine\Service\View\ViewFactory;
use ExpressionEngine\Model\Content\StructureModel;

/**
 * Columns Filter for File Manager
 */
class FileManagerColumns extends Columns
{

    public function __construct(array $columns = array(), StructureModel $uploadLocation = null, $view_id = null)
    {
        parent::__construct($columns, $uploadLocation, $view_id);

        $this->default_value = ['title', 'file_name', 'file_type', 'upload_date', 'file_size'];
    }

    // get columns from view
    public function value()
    {
        $value = '';

        $upload_id = !empty(ee()->uri->segment(4)) ? (int) ee()->uri->segment(4) : 0;
        $viewtype = in_array(ee()->input->get('viewtype'), ['thumb', 'list']) ? ee()->input->get('viewtype') : 'list';

        $query = ee('Model')->get('FileManagerView')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->filter('viewtype', $viewtype)
            ->filter('upload_id', $upload_id);
        $view = $query->first();

        if (!empty($view)) {
            $value = $view->getColumns();
        }

        if (empty($value)) {
            if ($viewtype !== 'list') {
                $this->default_value = ['title', 'file_size'];
            }
            $value = $this->default_value;
        }

        return $value;
    }
}

// EOF

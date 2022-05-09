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

        $this->default_value = ['title', 'name', 'file_type', 'date_added', 'size'];
    }

    // get columns from view
    public function value()
    {
        $value = '';

        //if we had channel switched and no saved view, make sure to fallback to default
        if (ee()->input->post('filter_by_channel') != '') {
            $value = parent::value();
        }

        $upload_id = !empty(ee()->input->post('filter_by_channel')) ? (int) ee()->input->post('filter_by_channel') : (int) ee()->input->get('filter_by_channel');

        $query = ee('Model')->get('FileManagerView')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->filter('upload_id', $upload_id);
        $view = $query->first();

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

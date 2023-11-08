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
 * Columns Filter for File Manager
 */
class FileManagerColumns extends Columns
{
    //view_id might hold view type here
    public function __construct(array $columns = array(), StructureModel $uploadLocation = null, $view_id_or_type = null)
    {
        parent::__construct($columns, $uploadLocation, $view_id_or_type);

        $this->default_value = ['title', 'file_name', 'file_type', 'upload_date', 'file_size'];
    }

    // get columns from view
    public function value()
    {
        $value = '';

        $upload_id = 0;
        if (!empty(ee()->uri->segment(4)) && is_numeric(ee()->uri->segment(4))) {
            $upload_id = (int) ee()->uri->segment(4);
        } elseif (!empty(ee()->input->get('requested_directory')) && is_numeric(ee()->input->get('requested_directory'))) {
            $upload_id = (int) ee()->input->get('requested_directory');
        } elseif (!empty(ee()->input->get('directories')) && is_numeric(ee()->input->get('directories'))) {
            $upload_id = (int) ee()->input->get('directories');
        }
        $viewtype = in_array($this->view_id, ['thumb', 'list']) ? $this->view_id : 'list';

        $query = ee('Model')->get('FileManagerView')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->filter('viewtype', $viewtype)
            ->filter('upload_id', $upload_id);
        $view = $query->first(true);

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

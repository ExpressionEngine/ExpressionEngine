<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\Advisor;

class CategoryAdvisor
{

    public function getBrokenCategoryCount()
    {
        $sql = "SELECT exp_categories.cat_id,exp_categories.site_id,exp_categories.group_id from exp_categories
                LEFT JOIN exp_category_field_data ON exp_category_field_data.cat_id = exp_categories.cat_id
                WHERE exp_category_field_data.cat_id IS NULL";

        $query = ee()->db->query($sql);

        return $query->num_rows;
    }

    public function fixBrokenCategories()
    {
        $sql = "INSERT INTO exp_category_field_data (cat_id,site_id,group_id)
                SELECT exp_categories.cat_id,exp_categories.site_id,exp_categories.group_id from exp_categories
                LEFT JOIN exp_category_field_data ON exp_category_field_data.cat_id = exp_categories.cat_id
                WHERE exp_category_field_data.cat_id IS NULL";

        $query = ee()->db->query($sql);

        return true;
    }

}
// EOF

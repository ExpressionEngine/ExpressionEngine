<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Category;

/**
 * Category Factory Service
 */
class Factory
{
    /**
     * Adds the JS scripts and variables the category UX needs.
     */
    public function addCategoryJS($channel_id = null)
    {
        ee()->cp->add_js_script(array(
            'plugin' => array(
                'ee_url_title'
            ),
            'file' => array(
                'cp/categories'
            )
        ));

        ee()->javascript->set_global([
            'categories.createUrl' => ee('CP/URL')->make('categories/create/###')->compile(),
            'categories.editUrl' => ee('CP/URL')->make('categories/edit/###')->compile(),
            'categories.removeUrl' => ee('CP/URL')->make('categories/remove-single/')->compile(),
            'categories.fieldUrl' => ee('CP/URL')->make('categories/category-group-publish-field/###' . ($channel_id ? '/' . $channel_id : ''))->compile()
        ]);
    }
}

// EOF

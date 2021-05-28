<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Query Module
 */
class Query
{
    public $return_data = '';

    public function __construct()
    {
        // Extract the query from the tag chunk
        if (($sql = ee()->TMPL->fetch_param('sql')) === false) {
            return false;
        }

        // Rudimentary check to see if it's a SELECT query, most definitely not
        // bulletproof
        if (substr(strtolower(trim($sql)), 0, 6) != 'select') {
            return false;
        }

        $query = ee()->db->query($sql);
        $results = $query->result_array();
        if ($query->num_rows() == 0) {
            return $this->return_data = ee()->TMPL->no_results();
        }

        // Start up pagination
        ee()->load->library('pagination');
        $pagination = ee()->pagination->create();
        ee()->TMPL->tagdata = $pagination->prepare(ee()->TMPL->tagdata);
        $per_page = ee()->TMPL->fetch_param('limit', 0);

        // Disable pagination if the limit parameter isn't set
        if (empty($per_page)) {
            $pagination->paginate = false;
        }

        if ($pagination->paginate) {
            $pagination->build($query->num_rows(), $per_page);
            $results = array_slice($results, $pagination->offset, $pagination->per_page);
        }

        $this->return_data = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, array_values($results));

        if ($pagination->paginate === true) {
            $this->return_data = $pagination->render($this->return_data);
        }
    }
}
// END CLASS

// EOF

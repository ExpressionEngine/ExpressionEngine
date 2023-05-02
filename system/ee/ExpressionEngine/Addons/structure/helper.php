<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Structure_Helper
{
    public static function remove_double_slashes($str)
    {
        return preg_replace("#(^|[^:])//+#", "\\1/", $str);
    }

    /**
     * Resolves a given $value, if a closure, calls closure, otherwise returns $value
     *
     * @param mixed  $value  Value or closure to resolve
     * @return mixed
     */
    public static function resolveValue($value)
    {
        return (is_callable($value) && !is_string($value)) ? call_user_func($value) : $value;
    }

    public static function tidy_url($url)
    {
        return self::remove_double_slashes('/' . $url);
    }

    public static function get_slug($url)
    {
        $segments = explode('/', trim($url, '/'));

        return end($segments);
    }
}

/**
 * This funciton will be used to add a copy of the current
 * state of the Structure navigation to the nav History
 * it will include site_pages array and json copy of
 * the Structure table
 *
 * @method add_structure_nav_revision
 * @param  string $note a note on where and why this state was saved
 */
function add_structure_nav_revision($site_id = 'all', $note = 'None')
{
    $structure_nav_history = ee()->config->item('structure_nav_history');
    if (!empty($structure_nav_history) && $structure_nav_history == 'n') {
        return;
    }

    // Truncate the existing nav_history so the DB doesn't fill up. Allow overriding the number of history states to keep.
    $structure_nav_history_states = ee()->config->item('structure_nav_history_states');
    if (empty($structure_nav_history_states)) {
        $structure_nav_history_states = 200;
    }

    // Long way to do this but we don't want to assume the name of the DB table.
    ee()->db->order_by('date', 'desc');
    $stale_history = ee()->db->get('structure_nav_history', 9999, $structure_nav_history_states);

    if ($stale_history && $stale_history->num_rows > 0) {
        $delete_ids = array();
        foreach ($stale_history->result() as $stale) {
            $delete_ids[] = $stale->id;
        }

        if (!empty($delete_ids)) {
            ee()->db->where_in('id', $delete_ids);
            ee()->db->delete('structure_nav_history');
        }
    }

    // we need to get the site pages array
    ee()->db->select('site_id, site_pages')->from('sites');

    // if we have a site id defined lets tack that one with a where.. if not we'll get all of them
    if ($site_id != 'all' && is_numeric($site_id)) {
        ee()->db->where('site_id', $site_id);
    }

    $site_pages = ee()->db->get();

    if ($site_pages->num_rows > 0) {
        // we have results lets loop over each site id and prep it to jam into the DB
        $x = 0;
        foreach ($site_pages->result() as $site_pages_per_site) {
            $data[$x]['site_id'] = $site_pages_per_site->site_id;
            // append the Structure version to the note
            $data[$x]['note'] = $note;
            $data[$x]['structure_version'] = STRUCTURE_VERSION;
            $data[$x]['site_pages'] = $site_pages_per_site->site_pages;

            // we need to get the structure table data for this site.
            $structure_table_data = ee()->db->select('*')->from('structure')->limit(9999999)->where('site_id', $site_pages_per_site->site_id)->get()->result();
            $data[$x]['structure'] = json_encode($structure_table_data);
            $data[$x]['date'] = date("Y-m-d H:i:s");
            $data[$x]['current'] = 1;

            // before we insert we want to set all other snap shots to have a non-current status
            ee()->db->where('current', '1')->update('structure_nav_history', array('current' => 0));

            // add the navigation revision to the system
            ee()->db->insert('structure_nav_history', $data[$x]);
        }
    }
}

function structure_array_get($array, $key, $default = null)
{
    if (is_null($key)) {
        return $array;
    }

    foreach (explode(':', $key) as $segment) {
        if (! is_array($array) or ! array_key_exists($segment, $array)) {
            return Structure_Helper::resolveValue($default);
        }
        $array = $array[$segment];
    }

    return $array;
}

/**
 * Picks the first value that isn't null or an empty string
 *
 * @return mixed
 */
if (!function_exists('pick')) {
    function pick()
    {
        $args = func_get_args();

        if (!is_array($args) || !count($args)) {
            return null;
        }

        foreach ($args as $arg) {
            if (!is_null($arg) && $arg !== '') {
                return $arg;
            }
        }
    }
}

/**
 * Dump the given value and kill the script.
 *
 * @param  mixed  $value
 * @return void
 */
function structure_dd($value)
{
    echo "<pre>";
    var_dump($value);
    echo "</pre>";
    die;
}

/**
 * Print_r the given value and kill the script.
 *
 * @param  mixed  $value
 * @return void
 */
if (!function_exists('rd')) {
    function rd($value)
    {
        echo "<pre>";
        print_r($value);
        echo "</pre>";
        die;
    }
}

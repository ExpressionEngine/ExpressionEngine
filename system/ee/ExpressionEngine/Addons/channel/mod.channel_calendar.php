<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Channel Calendar Module
 */
class Channel_calendar extends Channel
{
    public $sql = '';

    /** ----------------------------------------
    /**  Channel Calendar
    /** ----------------------------------------*/
    public function calendar()
    {
        // Rick is using some funky conditional stuff for the calendar, so
        // we have to reassign the var_cond array using the legacy conditional
        // parser.  Bummer, but whatcha going to do?

        ee()->TMPL->var_cond = ee()->functions->assign_conditional_variables(ee()->TMPL->tagdata, '/', LD, RD);

        /** ----------------------------------------
        /**  Determine the Month and Year
        /** ----------------------------------------*/
        $year = '';
        $month = '';

        // Hard-coded month/year via tag parameters

        if (ee()->TMPL->fetch_param('month') and ee()->TMPL->fetch_param('year')) {
            $year = ee()->TMPL->fetch_param('year');
            $month = ee()->TMPL->fetch_param('month');

            if (strlen($month) == 1) {
                $month = '0' . $month;
            }
        } else {
            // Month/year in query string

            if (preg_match("#(\d{4}/\d{2})#", ee()->uri->query_string, $match)) {
                $ex = explode('/', $match['1']);

                $time = gmmktime(0, 0, 0, $ex['1'], 01, $ex['0']);

                $year = ee()->localize->format_date('%Y', $time, false);
                $month = ee()->localize->format_date('%m', $time, false);
            } else {
                // Defaults to current month/year

                $year = ee()->localize->format_date('%Y');
                $month = ee()->localize->format_date('%m');
            }
        }

        /** ----------------------------------------
        /**  Set Unix timestamp for the given month/year
        /** ----------------------------------------*/
        $date = gmmktime(12, 0, 0, (int)$month, 1, (int)$year);

        /** ----------------------------------------
        /**  Determine the total days in the month
        /** ----------------------------------------*/
        ee()->load->library('calendar');
        $adjusted_date = ee()->calendar->adjust_date((int)$month, (int)$year);

        $month = $adjusted_date['month'];
        $year = $adjusted_date['year'];

        ee()->load->helper('date');
        $total_days = days_in_month($month, $year);

        $previous_date = mktime(12, 0, 0, $month - 1, 1, $year);
        $next_date = mktime(12, 0, 0, $month + 1, 1, $year);

        /** ---------------------------------------
        /**  Determine the total days of the previous month
        /** ---------------------------------------*/
        $adj_prev_date = ee()->calendar->adjust_date($month - 1, $year);

        $prev_month = $adj_prev_date['month'];
        $prev_year = $adj_prev_date['year'];

        $prev_total_days = days_in_month($prev_month, $prev_year);

        /** ----------------------------------------
        /**  Set the starting day of the week
        /** ----------------------------------------*/

        // This can be set using a parameter in the tag:  start_day="saturday"
        // By default the calendar starts on sunday

        $start_days = array('sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6);

        $start_day = (isset($start_days[ee()->TMPL->fetch_param('start_day')])) ? $start_days[ee()->TMPL->fetch_param('start_day')] : 0;

        $day = $start_day + 1 - ee()->localize->format_date('%w', $date, false);

        while ($day > 1) {
            $day -= 7;
        }

        /** ----------------------------------------
        /**  {previous_path="channel/index"}
        /** ----------------------------------------*/

        // This variables points to the previous month

        if (preg_match_all("#" . LD . "previous_path=(.+?)" . RD . "#", ee()->TMPL->tagdata, $matches)) {
            $adjusted_date = ee()->calendar->adjust_date($month - 1, $year, true);

            foreach ($matches['1'] as $match) {
                $path = ee()->functions->create_url($match) . '/' . $adjusted_date['year'] . '/' . $adjusted_date['month'];

                ee()->TMPL->tagdata = preg_replace("#" . LD . "previous_path=.+?" . RD . "#", $path, ee()->TMPL->tagdata, 1);
            }
        }

        /** ----------------------------------------
        /**  {next_path="channel/index"}
        /** ----------------------------------------*/

        // This variables points to the next month

        if (preg_match_all("#" . LD . "next_path=(.+?)" . RD . "#", ee()->TMPL->tagdata, $matches)) {
            $adjusted_date = ee()->calendar->adjust_date($month + 1, $year, true);

            foreach ($matches['1'] as $match) {
                $path = ee()->functions->create_url($match) . '/' . $adjusted_date['year'] . '/' . $adjusted_date['month'];

                ee()->TMPL->tagdata = preg_replace("#" . LD . "next_path=.+?" . RD . "#", $path, ee()->TMPL->tagdata, 1);
            }
        }

        /** ----------------------------------------
        /**  {date format="%m %Y"}
        /** ----------------------------------------*/
        $dates = array();

        // This variable is used in the heading of the calendar
        // to show the month and year
        $dates['date'] = $date;

        /** ----------------------------------------
        /**  {previous_date format="%m %Y"}
        /** ----------------------------------------*/

        // This variable is used in the heading of the calendar
        // to show the month and year
        $dates['previous_date'] = $previous_date;

        /** ----------------------------------------
        /**  {next_date format="%m %Y"}
        /** ----------------------------------------*/

        // This variable is used in the heading of the calendar
        // to show the month and year
        $dates['next_date'] = $next_date;

        ee()->TMPL->tagdata = ee()->TMPL->parse_date_variables(ee()->TMPL->tagdata, $dates);

        /** ----------------------------------------
        /**  Day Heading
        /** ----------------------------------------*/
        /*
            This code parses out the headings for each day of the week
            Contained in the tag will be this variable pair:

            {calendar_heading}
            <td class="calendarDayHeading">{lang:weekday_abrev}</td>
            {/calendar_heading}

            There are three display options for the header:

            {lang:weekday_abrev} = S M T W T F S
            {lang:weekday_short} = Sun Mon Tues, etc.
            {lang:weekday_long} = Sunday Monday Tuesday, etc.

        */

        foreach (array('Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa') as $val) {
            $day_names_a[] = (! ee()->lang->line($val)) ? $val : ee()->lang->line($val);
        }

        foreach (array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat') as $val) {
            $day_names_s[] = (! ee()->lang->line($val)) ? $val : ee()->lang->line($val);
        }

        foreach (array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday') as $val) {
            $day_names_l[] = (! ee()->lang->line($val)) ? $val : ee()->lang->line($val);
        }

        if (preg_match("/" . LD . "calendar_heading" . RD . "(.*?)" . LD . '\/' . "calendar_heading" . RD . "/s", ee()->TMPL->tagdata, $match)) {
            $temp = '';

            for ($i = 0; $i < 7; $i ++) {
                $temp .= str_replace(
                    array(LD . 'lang:weekday_abrev' . RD,
                        LD . 'lang:weekday_short' . RD,
                        LD . 'lang:weekday_long' . RD),
                    array($day_names_a[($start_day + $i) % 7],
                        $day_names_s[($start_day + $i) % 7],
                        $day_names_l[($start_day + $i) % 7]),
                    trim($match['1']) . "\n"
                );
            }

            ee()->TMPL->tagdata = preg_replace("/" . LD . "calendar_heading" . RD . ".*?" . LD . '\/' . "calendar_heading" . RD . "/s", trim($temp), ee()->TMPL->tagdata);
        }

        /** ----------------------------------------
        /**  Separate out cell data
        /** ----------------------------------------*/

        // We need to strip out the various variable pairs
        // that allow us to render each calendar cell.
        // We'll do this up-front and assign temporary markers
        // in the template which we will replace with the final
        // data later

        $row_start = '';
        $row_end = '';

        $row_chunk = '';
        $row_chunk_m = '94838dkAJDei8azDKDKe01';

        $entries = '';
        $entries_m = 'Gm983TGxkedSPoe0912NNk';

        $if_today = '';
        $if_today_m = 'JJg8e383dkaadPo20qxEid';

        $if_entries = '';
        $if_entries_m = 'Rgh43K0L0Dff9003cmqQw1';

        $if_not_entries = '';
        $if_not_entries_m = 'yr83889910BvndkGei8ti3';

        $if_blank = '';
        $if_blank_m = '43HDueie4q7pa8dAAseit6';

        if (preg_match("/" . LD . "calendar_rows" . RD . "(.*?)" . LD . '\/' . "calendar_rows" . RD . "/s", ee()->TMPL->tagdata, $match)) {
            $row_chunk = trim($match['1']);

            //  Fetch all the entry_date variable

            if (preg_match("/" . LD . "row_start" . RD . "(.*?)" . LD . '\/' . "row_start" . RD . "/s", $row_chunk, $match)) {
                $row_start = trim($match['1']);

                $row_chunk = trim(str_replace($match['0'], "", $row_chunk));
            }

            if (preg_match("/" . LD . "row_end" . RD . "(.*?)" . LD . '\/' . "row_end" . RD . "/s", $row_chunk, $match)) {
                $row_end = trim($match['1']);

                $row_chunk = trim(str_replace($match['0'], "", $row_chunk));
            }

            foreach (ee()->TMPL->var_cond as $key => $val) {
                if ($val['3'] == 'today') {
                    $if_today = trim($val['2']);

                    $row_chunk = str_replace($val['1'], $if_today_m, $row_chunk);

                    unset(ee()->TMPL->var_cond[$key]);
                }

                if ($val['3'] == 'entries') {
                    $if_entries = trim($val['2']);

                    $row_chunk = str_replace($val['1'], $if_entries_m, $row_chunk);

                    unset(ee()->TMPL->var_cond[$key]);
                }

                if ($val['3'] == 'not_entries') {
                    $if_not_entries = trim($val['2']);

                    $row_chunk = str_replace($val['1'], $if_not_entries_m, $row_chunk);

                    unset(ee()->TMPL->var_cond[$key]);
                }

                if ($val['3'] == 'blank') {
                    $if_blank = trim($val['2']);

                    $row_chunk = str_replace($val['1'], $if_blank_m, $row_chunk);

                    unset(ee()->TMPL->var_cond[$key]);
                }

                if (preg_match("/" . LD . "entries" . RD . "(.*?)" . LD . '\/' . "entries" . RD . "/s", $if_entries, $match)) {
                    $entries = trim($match['1']);

                    $if_entries = trim(str_replace($match['0'], $entries_m, $if_entries));
                }
            }

            ee()->TMPL->tagdata = preg_replace("/" . LD . "calendar_rows" . RD . ".*?" . LD . '\/' . "calendar_rows" . RD . "/s", $row_chunk_m, ee()->TMPL->tagdata);
        }

        /** ----------------------------------------
        /**  Fetch {switch} variable
        /** ----------------------------------------*/

        // This variable lets us use a different CSS class
        // for the current day

        $switch_t = '';
        $switch_c = '';

        if (ee()->TMPL->fetch_param('switch')) {
            $x = explode("|", ee()->TMPL->fetch_param('switch'));

            if (count($x) == 2) {
                $switch_t = $x['0'];
                $switch_c = $x['1'];
            }
        }

        /** ---------------------------------------
        /**  Set the day number numeric format
        /** ---------------------------------------*/
        $day_num_fmt = (ee()->TMPL->fetch_param('leading_zeroes') == 'yes') ? "%02d" : "%d";

        /** ----------------------------------------
        /**  Build the SQL query
        /** ----------------------------------------*/
        $this->initialize();

        // Fetch custom channel fields if we have search fields
        if (! empty(ee()->TMPL->search_fields)) {
            $this->fetch_custom_channel_fields();
        }

        $this->build_sql_query('/' . $year . '/' . $month . '/');

        if ($this->sql != '') {
            $query = ee()->db->query($this->sql);

            $data = array();

            if ($query->num_rows() > 0) {
                // We'll need this later

                ee()->load->library('typography');
                ee()->typography->initialize(array(
                    'convert_curly' => false
                ));

                /** ----------------------------------------
                /**  Fetch query results and build data array
                /** ----------------------------------------*/
                foreach ($query->result_array() as $row) {
                    $overrides = ee()->config->get_cached_site_prefs($row['entry_site_id']);
                    $row['channel_url'] = parse_config_variables($row['channel_url'], $overrides);
                    $row['comment_url'] = parse_config_variables($row['comment_url'], $overrides);

                    /** ----------------------------------------
                    /**  Define empty arrays and strings
                    /** ----------------------------------------*/
                    $defaults = array(
                        'entry_date' => 'a',
                        'permalink' => 'a',
                        'title_permalink' => 'a',
                        'author' => 's',
                        'profile_path' => 'a',
                        'id_path' => 'a',
                        'base_fields' => 'a',
                        'day_path' => 'a',
                        'comment_auto_path' => 's',
                        'comment_entry_id_auto_path' => 's',
                        'comment_url_title_auto_path' => 's'
                    );

                    foreach ($defaults as $key => $val) {
                        $$key = ($val == 'a') ? array() : '';
                    }

                    /** ---------------------------
                    /**  Single Variables
                    /** ---------------------------*/
                    foreach (ee()->TMPL->var_single as $key => $val) {
                        $entry_date[$key] = $row['entry_date'];

                        /** ----------------------------------------
                        /**  parse permalink
                        /** ----------------------------------------*/
                        if (strncmp($key, 'permalink', 9) == 0) {
                            if (ee()->functions->extract_path($key) != '' and ee()->functions->extract_path($key) != 'SITE_INDEX') {
                                $path = ee()->functions->extract_path($key) . '/' . $row['entry_id'];
                            } else {
                                $path = $row['entry_id'];
                            }

                            $permalink[$key] = ee()->functions->create_url($path);
                        }

                        /** ----------------------------------------
                        /**  parse title permalink
                        /** ----------------------------------------*/
                        if (strncmp($key, 'title_permalink', 15) == 0 or strncmp($key, 'url_title_path', 14) == 0) {
                            if (ee()->functions->extract_path($key) != '' and ee()->functions->extract_path($key) != 'SITE_INDEX') {
                                $path = ee()->functions->extract_path($key) . '/' . $row['url_title'];
                            } else {
                                $path = $row['url_title'];
                            }

                            $title_permalink[$key] = ee()->functions->create_url($path);
                        }

                        /** ----------------------------------------
                        /**  {comment_auto_path}
                        /** ----------------------------------------*/
                        if ($key == "comment_auto_path") {
                            $comment_auto_path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];
                        }

                        /** ----------------------------------------
                        /**  {comment_url_title_auto_path}
                        /** ----------------------------------------*/
                        if ($key == "comment_url_title_auto_path") {
                            $path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];
                            $comment_url_title_auto_path = $path . $row['url_title'];
                        }

                        /** ----------------------------------------
                        /**  {comment_entry_id_auto_path}
                        /** ----------------------------------------*/
                        if ($key == "comment_entry_id_auto_path") {
                            $path = ($row['comment_url'] == '') ? $row['channel_url'] : $row['comment_url'];
                            $comment_entry_id_auto_path = $path . $row['entry_id'];
                        }

                        /** ----------------------------------------
                        /**  {author}
                        /** ----------------------------------------*/
                        if ($key == "author") {
                            $author = ($row['screen_name'] != '') ? $row['screen_name'] : $row['username'];
                        }
                        /** ----------------------------------------
                        /**  profile path
                        /** ----------------------------------------*/
                        if (strncmp($key, 'profile_path', 12) == 0) {
                            $profile_path[$key] = ee()->functions->create_url(ee()->functions->extract_path($key) . '/' . $row['member_id']);
                        }

                        /** ----------------------------------------
                        /**  parse comment_path
                        /** ----------------------------------------*/
                        if (strncmp($key, 'comment_path', 12) == 0 or strncmp($key, 'entry_id_path', 13) == 0) {
                            $id_path[$key] = ee()->functions->create_url(ee()->functions->extract_path($key) . '/' . $row['entry_id']);
                        }

                        /** ----------------------------------------
                        /**  Basic fields (username, screen_name, etc.)
                        /** ----------------------------------------*/
                        if (isset($row[$val])) {
                            $base_fields[$key] = $row[$val];
                        }

                        /** ----------------------------------------
                        /**  {day_path}
                        /** ----------------------------------------*/
                        if (strncmp($key, 'day_path', 8) == 0) {
                            $formatted_date_path = ee()->localize->format_date('%Y/%m/%d', $row['entry_date']);

                            if (ee()->functions->extract_path($key) != ''
                                and ee()->functions->extract_path($key) != 'SITE_INDEX') {
                                $formatted_date_path = ee()->functions->extract_path($key) . '/' . $formatted_date_path;
                            }

                            $if_entries = str_replace(LD . $key . RD, LD . 'day_path' . $val . RD, $if_entries);
                            $day_path[$key] = ee()->functions->create_url($formatted_date_path);
                        }
                    }
                    // END FOREACH SINGLE VARIABLES

                    /** ----------------------------------------
                    /**  Build Data Array
                    /** ----------------------------------------*/
                    $d = ee()->localize->format_date('%d', $row['entry_date']);

                    if (substr($d, 0, 1) == '0') {
                        $d = substr($d, 1);
                    }

                    $data[$d][] = array(
                        ee()->typography->parse_type($row['title'], array('text_format' => 'lite', 'html_format' => 'none', 'auto_links' => 'n', 'allow_img_url' => 'no')),
                        $row['url_title'],
                        $entry_date,
                        $permalink,
                        $title_permalink,
                        $author,
                        $profile_path,
                        $id_path,
                        $base_fields,
                        $day_path,
                        $comment_auto_path,
                        $comment_url_title_auto_path,
                        $comment_entry_id_auto_path
                    );
                } // END FOREACH
            } // END if ($query->num_rows() > 0)
        } // END if ($this->query != '')

        /** ----------------------------------------
        /**  Build Calendar Cells
        /** ----------------------------------------*/
        $out = '';

        $today = array(
            'mday' => ee()->localize->format_date('%j'),
            'mon' => ee()->localize->format_date('%n'),
            'year' => ee()->localize->format_date('%Y')
        );

        while ($day <= $total_days) {
            $out .= $row_start;

            for ($i = 0; $i < 7; $i++) {
                if ($day > 0 and $day <= $total_days) {
                    if ($if_entries != '' and isset($data[$day])) {
                        $out .= str_replace($if_entries_m, $this->var_replace($if_entries, $data[$day], $entries), $row_chunk);

                        foreach ($day_path as $k => $v) {
                            $out = str_replace(LD . 'day_path' . $k . RD, $data[$day]['0']['9'][$k], $out);
                        }
                    } else {
                        $out .= str_replace($if_not_entries_m, $if_not_entries, $row_chunk);
                    }

                    $out = str_replace(LD . 'day_number' . RD, sprintf($day_num_fmt, $day), $out);

                    if ($day == $today["mday"] and $month == $today["mon"] and $year == $today["year"]) {
                        $out = str_replace(LD . 'switch' . RD, $switch_t, $out);
                    } else {
                        $out = str_replace(LD . 'switch' . RD, $switch_c, $out);
                    }
                } else {
                    $out .= str_replace($if_blank_m, $if_blank, $row_chunk);

                    $out = str_replace(LD . 'day_number' . RD, ($day <= 0) ? sprintf($day_num_fmt, $prev_total_days + $day) : sprintf($day_num_fmt, $day - $total_days), $out);
                }

                $day++;
            }

            $out .= $row_end;
        }

        // Garbage collection
        $out = str_replace(
            array(
                $entries_m,
                $if_blank_m,
                $if_today_m,
                $if_entries_m,
                $if_not_entries_m
            ),
            '',
            $out
        );

        return str_replace($row_chunk_m, $out, ee()->TMPL->tagdata);
    }

    /** ----------------------------------------
    /**  Replace Calendar Variables
    /** ----------------------------------------*/
    public function var_replace($chunk, $data, $row = '')
    {
        if ($row != '') {
            $temp = '';

            foreach ($data as $val) {
                $str = $row;

                $str = str_replace(
                    array(LD . 'title' . RD,
                        LD . 'url_title' . RD,
                        LD . 'author' . RD,
                        LD . 'comment_auto_path' . RD,
                        LD . 'comment_url_title_auto_path' . RD,
                        LD . 'comment_entry_id_auto_path' . RD),
                    array($val['0'],
                        $val['1'],
                        $val['5'],
                        $val['10'],
                        $val['11'],
                        $val['12']),
                    $str
                );

                // Entry Date
                foreach ($val['2'] as $date) {
                    $str = ee()->TMPL->parse_date_variables($str, array('entry_date' => $date));
                }

                // Permalink
                foreach ($val['3'] as $k => $v) {
                    $str = str_replace(LD . $k . RD, $v, $str);
                }

                // Title permalink
                foreach ($val['4'] as $k => $v) {
                    $str = str_replace(LD . $k . RD, $v, $str);
                }

                // Profile path
                foreach ($val['6'] as $k => $v) {
                    $str = str_replace(LD . $k . RD, $v, $str);
                }

                // ID path
                foreach ($val['7'] as $k => $v) {
                    $str = str_replace(LD . $k . RD, $v, $str);
                }

                // Base Fields
                foreach ($val['8'] as $k => $v) {
                    $str = str_replace(LD . $k . RD, $v, $str);
                }

                // Day path
                foreach ($val['9'] as $k => $v) {
                    $str = str_replace(LD . $k . RD, $v, $str);
                }

                $temp .= $str;
            }

            $chunk = str_replace('Gm983TGxkedSPoe0912NNk', $temp, $chunk);
        }

        return $chunk;
    }
}
// END CLASS

// EOF

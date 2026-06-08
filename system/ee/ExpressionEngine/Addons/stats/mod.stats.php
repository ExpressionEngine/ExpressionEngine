<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Stats Module
 */
class Stats
{
    public $return_data = '';

    /**
     * Create the stats module and parse the current template tag.
     */
    public function __construct()
    {
        ee()->stats->load_stats();
        $statdata = ee()->stats->statdata();

        // Limit stats by channel or status
        // You can limit the stats by any combination of channels

        if (! isset(ee()->TMPL)) {
            return;
        }

        $channel_name = ee()->TMPL->fetch_param('channel');
        $status = ee()->TMPL->fetch_param('status');
        $filter_by_status = ($status !== false && $status != '');

        if ($channel_name || $filter_by_status) {
            if ($filter_by_status) {
                $now = ee()->localize->now;

                $sql = "SELECT	COUNT(exp_channel_titles.entry_id) AS total_entries,
								COALESCE(SUM(exp_channel_titles.comment_total), 0) AS total_comments,
								MAX(exp_channel_titles.entry_date) AS last_entry_date,
								MAX(exp_channel_titles.recent_comment_date) AS last_comment_date
						FROM exp_channel_titles
						INNER JOIN exp_channels
							ON exp_channel_titles.channel_id = exp_channels.channel_id
						WHERE exp_channel_titles.site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";

                if ($channel_name) {
                    $sql .= ee()->functions->sql_andor_string($channel_name, 'exp_channels.channel_name');
                }

                $sql .= $this->statusSql($status);
                $sql .= " AND exp_channel_titles.entry_date <= " . $now . " ";
                $sql .= " AND (exp_channel_titles.expiration_date = 0 OR exp_channel_titles.expiration_date > " . $now . ") ";
            } else {
                $sql = "SELECT	total_entries,
								total_comments,
								last_entry_date,
								last_comment_date
						FROM exp_channels
						WHERE site_id IN ('" . implode("','", ee()->TMPL->site_ids) . "') ";

                $sql .= ee()->functions->sql_andor_string($channel_name, 'exp_channels.channel_name');
            }

            $cache_sql = md5($sql);

            if (! isset(ee()->stats->stats_cache[$cache_sql])) {
                $query = ee()->db->query($sql);

                $sdata = array(
                    'total_entries' => 0,
                    'total_comments' => 0,
                    'last_entry_date' => 0,
                    'last_comment_date' => 0
                );

                if ($query->num_rows() > 0) {
                    foreach ($query->result_array() as $row) {
                        foreach ($sdata as $key => $val) {
                            if (substr($key, 0, 5) == 'last_') {
                                if ($row[$key] > $val) {
                                    $sdata[$key] = $row[$key];
                                }
                            } else {
                                $sdata[$key] = $sdata[$key] + $row[$key];
                            }
                        }
                    }

                    ee()->stats->stats_cache[$cache_sql] = $sdata;
                    $statdata = array_merge($statdata, $sdata);
                }
            } else {
                $statdata = array_merge($statdata, ee()->stats->stats_cache[$cache_sql]);
            }
        }

        //  Parse stat fields
        $fields = array('total_members', 'total_entries', 'total_forum_topics',
            'total_forum_replies', 'total_forum_posts', 'total_comments',
            'most_visitors', 'total_logged_in', 'total_guests', 'total_anon');
        $cond = array();

        foreach ($fields as $field) {
            if (isset(ee()->TMPL->var_single[$field])) {
                $value = isset($statdata[$field]) ? $statdata[$field] : false;
                $cond[$field] = $value;
                ee()->TMPL->tagdata = ee()->TMPL->swap_var_single($field, $value, ee()->TMPL->tagdata);
            }
        }

        if (count($cond) > 0) {
            ee()->TMPL->tagdata = ee()->functions->prep_conditionals(ee()->TMPL->tagdata, $cond);
        }

        //  Parse dates
        $dates = array('last_entry_date', 'last_forum_post_date',
            'last_comment_date', 'last_visitor_date', 'most_visitor_date');

        foreach (ee()->TMPL->var_single as $key => $val) {
            foreach ($dates as $date) {
                if (strncmp($key, $date, strlen($date)) == 0) {
                    $date_value = isset($statdata[$date]) ? $statdata[$date] : false;
                    ee()->TMPL->tagdata = ee()->TMPL->swap_var_single(
                        $key,
                        (! $date_value
                                                    or $date_value == 0) ? '--' :
                                                ee()->localize->format_date(
                                                    $val,
                                                    $date_value
                                                ),
                        ee()->TMPL->tagdata
                    );
                }
            }
        }

        //  Online user list

        $names = '';

        if (! empty($statdata['current_names'])) {
            $chunk = ee()->TMPL->fetch_data_between_var_pairs(
                ee()->TMPL->tagdata,
                'member_names'
            );

            $backspace = '';

            if (! preg_match(
                "/" . LD . "member_names.*?backspace=[\"|'](.+?)[\"|']/",
                ee()->TMPL->tagdata,
                $match
            )) {
                if (preg_match(
                    "/" . LD . "name.*?backspace=[\"|'](.+?)[\"|']/",
                    ee()->TMPL->tagdata,
                    $match
                )) {
                    $backspace = $match['1'];
                }
            } else {
                $backspace = $match['1'];
            }

            $member_path = (preg_match(
                "/" . LD . "member_path=(.+?)" . RD . "/",
                ee()->TMPL->tagdata,
                $match
            )) ? $match['1'] : '';
            $member_path = str_replace("\"", "", $member_path);
            $member_path = str_replace("'", "", $member_path);
            $member_path = trim_slashes($member_path);

            foreach ($statdata['current_names'] as $k => $v) {
                $temp = $chunk;

                if (empty($temp)) {
                    continue;
                }

                if ($v['1'] == 'y') {
                    if (ee('Permission')->isSuperAdmin()) {
                        $temp = preg_replace("/" . LD . "name.*?" . RD . "/", $v['0'] . '*', $temp);
                    } elseif (ee()->session->userdata('member_id') == $k) {
                        $temp = preg_replace("/" . LD . "name.*?" . RD . "/", $v['0'] . '*', $temp);
                    } else {
                        continue;
                    }
                } else {
                    $temp = preg_replace("/" . LD . "name.*?" . RD . "/", $v['0'], $temp);
                }

                $path = ee()->functions->create_url($member_path . '/' . $k);

                $temp = preg_replace("/" . LD . "member_path=(.+?)" . RD . "/", $path, $temp);

                $names .= $temp;
            }

            if (is_numeric($backspace)) {
                $names = substr(trim($names), 0, - $backspace);
            }
        }

        $names = str_replace(LD . 'name' . RD, '', $names);

        ee()->TMPL->tagdata = preg_replace("/" . LD . 'member_names' . ".*?" . RD . "(.*?)" . LD . '\/' . 'member_names' . RD . "/s", $names, ee()->TMPL->tagdata);

        //  {if member_names}

        if ($names != '') {
            ee()->TMPL->tagdata = preg_replace("/" . LD . 'if member_names' . ".*?" . RD . "(.*?)" . LD . '\/' . 'if' . RD . "/s", "\\1", ee()->TMPL->tagdata);
        } else {
            ee()->TMPL->tagdata = preg_replace("/" . LD . 'if member_names' . ".*?" . RD . "(.*?)" . LD . '\/' . 'if' . RD . "/s", "", ee()->TMPL->tagdata);
        }

        $this->return_data = ee()->TMPL->tagdata;
    }

    /**
     * Build the SQL clause for status-filtered stats.
     *
     * @param string $status Entry status parameter.
     * @return string
     */
    private function statusSql($status)
    {
        $status = str_replace(array('Open', 'Closed'), array('open', 'closed'), $status);
        $sstr = ee()->functions->sql_andor_string($status, 'exp_channel_titles.status');

        if (stristr($sstr, "'closed'") === false) {
            $sstr .= " AND exp_channel_titles.status != 'closed' ";
        }

        return $sstr;
    }

    /**
     * @deprecated 2.2.1
     *
     * Process all stats in a separate call
     * @return null
     */
    public function sync_stats()
    {
        // The legacy action can exist on upgraded installs. Restrict this
        // expensive sync to authorized CP users (or CLI).
        if (!(defined('REQ') && REQ === 'CLI')) {
            $member_id = (int) ee()->session->userdata('member_id');
            if ($member_id <= 0 || !ee('Permission')->can('access_data')) {
                show_error(lang('unauthorized_access'), 403);
                return;
            }
        }

        // Get last updated
        $site_id = ee()->config->item('site_id');
        $now = ee()->localize->now;
        $cooldown = 30;
        $lastAttempt = (int) ee()->cache->get('ee-stats-cache-last-attempt');
        if ($lastAttempt > 0 && ($now - $lastAttempt) < $cooldown) {
            return;
        }
        ee()->cache->save('ee-stats-cache-last-attempt', $now, $cooldown);

        $lastRun = ee()->cache->get('ee-stats-cache-last-run');

        if (!$lastRun) {
            $lastRun = 0;
        }

        $entriesUpdated = ee('Model')
            ->get('ChannelEntry')
            ->filter('edit_date', '>=', $lastRun)
            ->all();

        // If nothing has been updated, then we'll skip this.
        if ($entriesUpdated->count() == 0) {
            ee()->cache->save(
                'ee-stats-cache-last-run',
                ee()->localize->now,
                0
            );

            return;
        }

        // Update entry stats
        $entries = ee('Model')->get('ChannelEntry')
            ->fields('entry_date', 'channel_id')
            ->filter('site_id', $site_id)
            ->filter('entry_date', '<=', $now)
            ->filter('status', '!=', 'closed')
            ->filterGroup()
            ->filter('expiration_date', 0)
            ->orFilter('expiration_date', '>', $now)
            ->endFilterGroup()
            ->order('entry_date', 'desc');

        $total_entries = $entries->count();

        $entry = $entries->first();

        $last_entry_date = ($entry) ? $entry->entry_date : 0;

        $stats = ee('Model')->get('Stats')
            ->filter('site_id', $site_id)
            ->first();

        $stats->total_entries = $total_entries;
        $stats->last_entry_date = $last_entry_date;
        $stats->save();

        $authorsToUpdate = $channelsToUpdate = [];

        // Sync channel entries
        foreach (array_unique($entriesUpdated->asArray()) as $entry) {
            $authorsToUpdate[] = $entry->Author;
            $channelsToUpdate[] = $entry->Channel;
        }

        // Sync author stats
        foreach (array_unique($authorsToUpdate) as $author) {
            if (null !== $author) {
                $author->updateAuthorStats();
            }
        }
        // Sync channel stats
        foreach (array_unique($channelsToUpdate) as $channel) {
            if (null !== $channel) {
                $channel->updateEntryStats();
            }
        }

        // Save this as last run
        ee()->cache->save(
            'ee-stats-cache-last-run',
            ee()->localize->now,
            0
        );
    }
}
// END CLASS

// EOF

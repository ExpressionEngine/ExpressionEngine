<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

use ExpressionEngine\Library\CP\Table;

/**
 * Statistics Controller
 */
class Stats extends Utilities
{
    private $forums_exist = false;
    private $sources = array('members', 'channel_titles', 'sites');

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('access_data')) {
            show_error(lang('unauthorized_access'), 403);
        }

        // Do the forums exist?
        if (ee()->config->item('forum_is_installed') == "y") {
            $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_modules WHERE module_name = 'Forum'");

            if ($query->row('count') > 0) {
                $this->forums_exist = true;
                $this->sources = array_merge($this->sources, array('forums', 'forum_topics'));
            }
        }
    }

    /**
     * Determine's the default language and lists those files.
     */
    public function index()
    {
        $table = ee('CP/Table', array('autosort' => true));
        $table->setColumns(array(
            'source',
            'record_count',
            'manage' => array(
                'type' => Table::COL_TOOLBAR
            ),
            array(
                'type' => Table::COL_CHECKBOX
            )
        ));

        $data = array();
        foreach ($this->sources as $source) {
            $vars['sources'][$source] = ee()->db->count_all($source);

            $data[] = array(
                lang($source),
                ee()->db->count_all($source),
                array('toolbar_items' => array(
                    'sync' => array(
                        'href' => ee('CP/URL')->make('utilities/stats/sync/' . $source),
                        'title' => lang('sync')
                    )
                )),
                array(
                    'name' => 'selection[]',
                    'value' => $source
                )
            );
        }

        $table->setData($data);

        $vars['base_url'] = ee('CP/URL')->make('utilities/stats');
        $vars['table'] = $table->viewData($vars['base_url']);
        $vars['cp_page_title'] = lang('manage_stats');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('manage_stats')
        );

        ee()->cp->render('utilities/stats', $vars);
    }

    // @TODO This begs to be done via new Models (or a Service?)
    public function sync($source = null)
    {
        $sources = ee()->input->post('selection') ?: array($source);
        $sources = array_intersect($sources, $this->sources);

        if (empty($sources)) {
            show_404();
        }

        if (in_array('members', $this->sources)) {
            $member_entries = array(); // arrays of statements to update

            $member_entries_count = ee()->db->query('SELECT COUNT(*) AS count, author_id FROM exp_channel_titles GROUP BY author_id ORDER BY count DESC');

            if (ee()->config->item('enable_comments') == 'y') {
                $member_comments_count = ee()->db->query('SELECT COUNT(*) AS count, author_id FROM exp_comments GROUP BY author_id ORDER BY count DESC');
            }

            $member_message_count = ee()->db->query('SELECT COUNT(*) AS count, recipient_id FROM exp_message_copies WHERE message_read = "n" GROUP BY recipient_id ORDER BY count DESC');

            $member_data = array();

            if ($member_entries_count->num_rows() > 0) {
                foreach ($member_entries_count->result() as $row) {
                    $member_entries[$row->author_id]['member_id'] = $row->author_id;
                    $member_entries[$row->author_id]['total_entries'] = $row->count;
                    $member_entries[$row->author_id]['total_comments'] = 0;
                    $member_entries[$row->author_id]['private_messages'] = 0;
                    $member_entries[$row->author_id]['total_forum_posts'] = 0;
                    $member_entries[$row->author_id]['total_forum_topics'] = 0;
                }
            }

            if (ee()->config->item('enable_comments') == 'y') {
                if ($member_comments_count->num_rows() > 0) {
                    foreach ($member_comments_count->result() as $row) {
                        if (isset($member_entries[$row->author_id]['member_id'])) {
                            $member_entries[$row->author_id]['total_comments'] = $row->count;
                        } else {
                            $member_entries[$row->author_id]['member_id'] = $row->author_id;
                            $member_entries[$row->author_id]['total_entries'] = 0;
                            $member_entries[$row->author_id]['total_comments'] = $row->count;
                            $member_entries[$row->author_id]['private_messages'] = 0;
                            $member_entries[$row->author_id]['total_forum_posts'] = 0;
                            $member_entries[$row->author_id]['total_forum_topics'] = 0;
                        }
                    }
                }
            }

            if ($member_message_count->num_rows() > 0) {
                foreach ($member_message_count->result() as $row) {
                    if (isset($member_entries[$row->recipient_id]['member_id'])) {
                        $member_entries[$row->recipient_id]['private_messages'] = $row->count;
                    } else {
                        $member_entries[$row->recipient_id]['member_id'] = $row->recipient_id;
                        $member_entries[$row->recipient_id]['total_entries'] = 0;
                        $member_entries[$row->recipient_id]['total_comments'] = 0;
                        $member_entries[$row->recipient_id]['private_messages'] = $row->count;

                        $member_entries[$row->recipient_id]['total_forum_posts'] = 0;
                        $member_entries[$row->recipient_id]['total_forum_topics'] = 0;
                    }
                }
            }

            if ($this->forums_exist === true) {
                $forum_topics_count = ee()->db->query('SELECT COUNT(*) AS count, author_id FROM exp_forum_topics GROUP BY author_id ORDER BY count DESC');
                $forum_posts_count = ee()->db->query('SELECT COUNT(*) AS count, author_id FROM exp_forum_posts GROUP BY author_id ORDER BY count DESC');

                if ($forum_topics_count->num_rows() > 0) {
                    foreach ($forum_topics_count->result() as $row) {
                        if (isset($member_entries[$row->author_id]['member_id'])) {
                            $member_entries[$row->author_id]['total_forum_topics'] = $row->count;
                        } else {
                            $member_entries[$row->author_id]['member_id'] = $row->author_id;
                            $member_entries[$row->author_id]['total_entries'] = 0;
                            $member_entries[$row->author_id]['total_comments'] = 0;
                            $member_entries[$row->author_id]['private_messages'] = 0;
                            $member_entries[$row->author_id]['total_forum_posts'] = 0;
                            $member_entries[$row->author_id]['total_forum_topics'] = $row->count;
                        }
                    }
                }

                if ($forum_posts_count->num_rows() > 0) {
                    foreach ($forum_posts_count->result() as $row) {
                        if (isset($member_entries[$row->author_id]['member_id'])) {
                            $member_entries[$row->author_id]['total_forum_posts'] = $row->count;
                        } else {
                            $member_entries[$row->author_id]['member_id'] = $row->author_id;
                            $member_entries[$row->author_id]['total_entries'] = 0;
                            $member_entries[$row->author_id]['total_comments'] = 0;
                            $member_entries[$row->author_id]['private_messages'] = 0;
                            $member_entries[$row->author_id]['total_forum_posts'] = $row->count;
                            $member_entries[$row->author_id]['total_forum_topics'] = 0;
                        }
                    }
                }
            }

            if (! empty($member_entries)) {
                ee()->db->update_batch('exp_members', $member_entries, 'member_id');

                // Set the rest to 0 for all of the above

                $data = array(
                    'total_entries' => 0,
                    'total_comments' => 0,
                    'private_messages' => 0,
                    'total_forum_posts' => 0,
                    'total_forum_topics' => 0
                );

                ee()->db->where_not_in('member_id', array_keys($member_entries));
                ee()->db->update('members', $data);
            }

            // re-save every role since that will trigger members recount automatically
            foreach (ee('Model')->get('Role')->all() as $role) {
                $role->total_members = null;
                $role->save();
            }
        }

        if (in_array('channel_titles', $this->sources)) {
            $channel_titles = array(); // arrays of statements to update

            if (ee()->config->item('enable_comments') == 'y') {
                $channel_comments_count = ee()->db->query('SELECT COUNT(comment_id) AS count, entry_id FROM exp_comments WHERE status = "o" GROUP BY entry_id ORDER BY count DESC');
                $channel_comments_recent = ee()->db->query('SELECT MAX(comment_date) AS recent, entry_id FROM exp_comments WHERE status = "o" GROUP BY entry_id ORDER BY recent DESC');

                if ($channel_comments_count->num_rows() > 0) {
                    foreach ($channel_comments_count->result() as $row) {
                        $channel_titles[$row->entry_id]['entry_id'] = $row->entry_id;
                        $channel_titles[$row->entry_id]['comment_total'] = $row->count;
                        $channel_titles[$row->entry_id]['recent_comment_date'] = 0;
                    }

                    // Now for the most recent date
                    foreach ($channel_comments_recent->result() as $row) {
                        $channel_titles[$row->entry_id]['recent_comment_date'] = $row->recent;
                    }
                }
            }

            // Set the rest to 0 for all of the above
            $data = array(
                'comment_total' => 0,
                'recent_comment_date' => 0
            );

            if (count($channel_titles) > 0) {
                ee()->db->update_batch('exp_channel_titles', $channel_titles, 'entry_id');

                ee()->db->where_not_in('entry_id', array_keys($channel_titles));
                ee()->db->update('channel_titles', $data);
            } else {
                ee()->db->update('channel_titles', $data);
            }

            // now update the channels table
            $channels = ee('Model')->get('Channel')->all();
            foreach ($channels as $i => $channel) {
                $channel->updateEntryStats();
            }

            unset($data);
        }

        if (in_array('forums', $this->sources)) {
            $query = ee()->db->query("SELECT forum_id FROM exp_forums WHERE forum_is_cat = 'n'");

            if ($query->num_rows() > 0) {
                foreach ($query->result_array() as $row) {
                    $forum_id = $row['forum_id'];

                    $res1 = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE forum_id = '{$forum_id}'");
                    $total1 = $res1->row('count');

                    $res2 = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE forum_id = '{$forum_id}'");
                    $total2 = $res2->row('count');

                    ee()->db->query("UPDATE exp_forums SET forum_total_topics = '{$total1}', forum_total_posts = '{$total2}' WHERE forum_id = '{$forum_id}'");
                }
            }
        }

        if (in_array('forum_topics', $this->sources)) {
            $total_rows = ee()->db->count_all('forum_topics');

            $query = ee()->db->query("SELECT forum_id FROM exp_forums WHERE forum_is_cat = 'n' ORDER BY forum_id");

            foreach ($query->result_array() as $row) {
                $forum_id = $row['forum_id'];

                $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE forum_id = '{$forum_id}'");
                $data['forum_total_topics'] = $query->row('count');

                $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE forum_id = '{$forum_id}'");
                $data['forum_total_posts'] = $query->row('count');

                $query = ee()->db->query("SELECT topic_id, title, topic_date, last_post_date, last_post_author_id, screen_name
									FROM exp_forum_topics, exp_members
									WHERE member_id = last_post_author_id
									AND forum_id = '{$forum_id}'
									ORDER BY last_post_date DESC LIMIT 1");

                $data['forum_last_post_id'] = ($query->num_rows() == 0) ? 0 : $query->row('topic_id') ;
                $data['forum_last_post_title'] = ($query->num_rows() == 0) ? '' : $query->row('title') ;
                $data['forum_last_post_date'] = ($query->num_rows() == 0) ? 0 : $query->row('topic_date') ;
                $data['forum_last_post_author_id'] = ($query->num_rows() == 0) ? 0 : $query->row('last_post_author_id') ;
                $data['forum_last_post_author'] = ($query->num_rows() == 0) ? '' : $query->row('screen_name') ;

                $query = ee()->db->query("SELECT post_date, author_id, screen_name
									FROM exp_forum_posts, exp_members
									WHERE  member_id = author_id
									AND forum_id = '{$forum_id}'
									ORDER BY post_date DESC LIMIT 1");

                if ($query->num_rows() > 0) {
                    if ($query->row('post_date') > $data['forum_last_post_date']) {
                        $data['forum_last_post_date'] = $query->row('post_date');
                        $data['forum_last_post_author_id'] = $query->row('author_id');
                        $data['forum_last_post_author'] = $query->row('screen_name');
                    }
                }

                ee()->db->query(ee()->db->update_string('exp_forums', $data, "forum_id='{$forum_id}'"));
                unset($data);

                /** -------------------------------------
                /**  Update
                /** -------------------------------------*/
                $query = ee()->db->query("SELECT forum_id FROM exp_forums");

                $total_topics = 0;
                $total_posts = 0;

                foreach ($query->result_array() as $row) {
                    $q = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_topics WHERE forum_id = '" . $row['forum_id'] . "'");
                    $total_topics = ($total_topics == 0) ? $q->row('count') : $total_topics + $q->row('count') ;

                    $q = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE forum_id = '" . $row['forum_id'] . "'");
                    $total_posts = ($total_posts == 0) ? $q->row('count') : $total_posts + $q->row('count') ;
                }

                ee()->db->query("UPDATE exp_stats SET total_forum_topics = '{$total_topics}', total_forum_posts = '{$total_posts}'");
            }

            $query = ee()->db->query("SELECT topic_id FROM exp_forum_topics WHERE thread_total <= 1");

            if ($query->num_rows() > 0) {
                foreach ($query->result_array() as $row) {
                    $res = ee()->db->query("SELECT COUNT(*) AS count FROM exp_forum_posts WHERE topic_id = '" . $row['topic_id'] . "'");
                    $count = ($res->row('count') == 0) ? 1 : $res->row('count') + 1;

                    ee()->db->query("UPDATE exp_forum_topics SET thread_total = '{$count}' WHERE topic_id = '" . $row['topic_id'] . "'");
                }
            }
        }

        if (in_array('sites', $this->sources)) {
            $original_site_id = ee()->config->item('site_id');

            $query = ee()->db->query("SELECT site_id FROM exp_sites");

            foreach ($query->result_array() as $row) {
                ee()->config->set_item('site_id', $row['site_id']);

                if (ee()->config->item('enable_comments') == 'y') {
                    ee()->stats->update_comment_stats();
                }

                ee()->stats->update_member_stats();
                ee()->stats->update_channel_stats();
            }

            ee()->config->set_item('site_id', $original_site_id);
        }

        ee()->view->set_message('success', lang('sync_completed'), '', true);
        ee()->functions->redirect(ee('CP/URL')->make('utilities/stats'));
    }
}
// END CLASS

// EOF

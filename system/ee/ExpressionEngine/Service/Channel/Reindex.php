<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Channel;

use ExpressionEngine\Service\Model\Collection;

/**
 * Content Reindex Service
 */
class Reindex
{
    protected const CACHE_KEY = '/search/file-usage';

    protected $field_ids = [];
    public $entry_ids = [];
    public $site_id = 'all';
    protected $reindexing = false;

    public function __construct()
    {
        // Load the logger
        if (! isset(ee()->logger)) {
            ee()->load->library('logger');
        }
    }

    /**
     * Initialize the variables needed for reindex
     *
     * @return void
     */
    public function initialize()
    {
        $data = ee()->cache->get(self::CACHE_KEY);
        if ($data === false || $data['reindexing'] === false) {
            $fields = $this->getFields();
            $this->field_ids = $this->getFieldIdNames($fields);
            $this->entry_ids = [
                'all' => $this->getEntryIds($fields)
            ];
            if ($this->site_id != 'all') {
                $this->entry_ids[$this->site_id] = $this->getEntryIds($fields, $this->site_id);
            }
            $this->cache();
        } else {
            $this->field_ids = $data['field_ids'];
            $this->entry_ids = $data['entry_ids'];
            $this->reindexing = $data['reindexing'];
        }
    }

    /**
     * Gets a Collection of ChannelField entities whose fieldtypes implement
     * the `reindex` function. These are the fields that need reindexing, the
     * rest can be ignored.
     *
     * @return Collection of ChannelField entities
     */
    protected function getFields()
    {
        $fieldtypes = [];

        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');
        foreach (ee()->api_channel_fields->fetch_installed_fieldtypes() as $type => $data) {
            $ft = ee()->api_channel_fields->setup_handler($type, true);
            if (method_exists($ft, 'reindex')) {
                $fieldtypes[] = $type;
            }
        }

        return ee('Model')->get('ChannelField')
            ->filter('field_type', 'IN', $fieldtypes)
            ->all();
    }

    /**
     * Given a Collection of ChannelField entities extract an array of the
     * field id "names", i.e. ['field_id_1', 'field_id_13']
     *
     * @param obj $fields A Collection of ChannelField entities
     * @return array An array of field id names i.e. ['field_id_1', 'field_id_13']
     */
    protected function getFieldIdNames(Collection $fields)
    {
        $field_ids = [];

        foreach ($fields as $field) {
            $field_ids[] = 'field_id_' . $field->getId();
        }

        return $field_ids;
    }

    /**
     * Given a Collection of ChannelField entities fetch a list of all the
     * Channel entries that use at least one of these fields
     *
     * @param obj $fields A Collection of ChannelField entities
     * @return array An array of Channel entry IDs.
     */
    protected function getEntryIds(Collection $fields, $site_id = null)
    {
        $channel_ids = [];
        $entry_ids = ee('Model')->get('ChannelEntry')
            ->fields('entry_id');

        foreach ($fields as $field) {
            $channel_ids = array_merge($channel_ids, $field->getAllChannels()->pluck('channel_id'));
        }

        $channel_ids = array_unique($channel_ids);

        if (! empty($channel_ids)) {
            $entry_ids->filter('channel_id', 'IN', $channel_ids);
        }

        if ($site_id) {
            $entry_ids->filter('site_id', $site_id);
        }

        return $entry_ids->all()->pluck('entry_id');
    }

    /**
     * Save the field_ids, entry_ids, and reindexing status to cache
     *
     * @return bool TRUE if it saved; FALSE if not
     */
    protected function cache()
    {
        $data = [
            'field_ids' => $this->field_ids,
            'entry_ids' => $this->entry_ids,
            'reindexing' => $this->reindexing
        ];

        return ee()->cache->save(self::CACHE_KEY, $data);
    }

    public function inProgress()
    {
        return $this->reindexing;
    }

    public function getProgressSteps()
    {
        return count($this->entry_ids[$this->site_id]);
    }

    /**
     * Process the reindexing
     *
     * @access  public
     * @return  void
     */
    public function process($progress, $site = 'all')
    {
        if (! $this->reindexing) {
            ee()->logger->log_action(lang('search_reindexed_started'));
            $this->reindexing = true;
            $this->cache();
        }

        if (isset($this->entry_ids[$site][$progress])) {
            $entry = ee('Model')->get('ChannelEntry', $this->entry_ids[$site][$progress])->first();

            foreach ($entry->getCustomFields() as $field) {
                $name = $field->getName();

                if (in_array($name, $this->field_ids)) {
                    $search_data = $field->reindex($entry);
                    $entry->setRawProperty($name, $search_data);
                }
            }

            $dirty = $entry->getDirty();

            if (! empty($dirty)) {
                $entry->saveFieldData($dirty);
            }

            $progress++;
        }

        if ($progress >= count($this->entry_ids[$site])) {
            ee()->logger->log_action(sprintf(lang('search_reindex_completed'), number_format(count($this->entry_ids[$site]))));

            ee()->config->update_site_prefs(['search_reindex_needed' => null], 0);

            $this->reindexing = false; // For symmetry and "futureproofing"
            ee()->cache->delete(self::CACHE_KEY); // All done!
        }

        return $progress;
    }

}

// EOF

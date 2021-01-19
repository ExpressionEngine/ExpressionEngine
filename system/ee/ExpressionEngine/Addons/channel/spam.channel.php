<?php

namespace ExpressionEngine\Addons\Channel;

use ExpressionEngine\Addons\Spam\Service\SpamModerationInterface;

/**
 * Moderate Spam for the Channel Form
 */
class Channel_spam implements SpamModerationInterface
{
    /**
     * Approve Trapped Spam
     *
     * @param  object $entry ExpressionEngine\Model\ChannelEntry
     * @param  array $post_data The original $_POST data
     * @return void
     */
    public function approve($entry, $post_data)
    {
        // Unserializing a new ChannelEntry has two problems
        // 1. The entity is marked clean after unserializing, and since we only save dirty
        //    properties, nothing we unserialized gets saved.
        // 2. The entity is initialized without a Channel, which means it has no category
        //    groups, and thus generates a PHP error when any categories are set with the
        //    ->set($data) call.
        //
        // So: we are going to just make a new entity with the unserialized entity's values
        // and set a Channel entity, and all will be well.
        if ($entry->isNew()) {
            $data = $entry->getValues();

            $entry = ee('Model')->make('ChannelEntry');
            $entry->Channel = ee('Model')->get('Channel', $post_data['channel_id'])->first();
            $entry->set($data);
        }

        // save it
        $entry->set($post_data);
        $entry->edit_date = ee()->localize->now;
        $entry->save();

        // ChannelEntry model handles all post-save actions: notifications, cache clearing, stats updates, etc.
    }

    /**
     * Reject Trapped Spam
     *
     * @param  object $entry ExpressionEngine\Model\ChannelEntry
     * @param  array $post_data The original $_POST data
     * @return void
     */
    public function reject($entry, $post_data)
    {
        // Nothing was saved outside of the spam trap, so we don't need to do anything
        return;
    }
}
// END CLASS

// EOF

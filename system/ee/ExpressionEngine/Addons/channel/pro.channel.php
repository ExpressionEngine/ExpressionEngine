<?php

use ExpressionEngine\Addons\Pro\Service\Prolet;

use ExpressionEngine\Controller\Publish\AbstractPublish as AbstractPublishController;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Model\Channel\ChannelEntry as ChannelEntry;
use ExpressionEngine\Service\Validation\Result as ValidationResult;
use Mexitek\PHPColors\Color;
use ExpressionEngine\Library\CP\EntryManager;
use ExpressionEngine\Addons\Pro\Library\CP\EntryManager\ColumnFactory;
use ExpressionEngine\Service\Sidebar\Navigation\NavigationList;
use ExpressionEngine\Service\View\ViewFactory;

class Channel_pro extends Prolet\AbstractProlet
{
    protected $name = 'Publish';

    protected $size = 'small';

    protected $icon = 'fa-plus.svg';

    protected $buttons = [];

    private $permissions;

    public function checkPermissions()
    {
        $assigned_channel_ids = ee()->session->getMember()->getAssignedChannels()->pluck('channel_id');
        $perms = [];

        foreach ($assigned_channel_ids as $channel_id) {
            $perms[] = 'can_create_entries_channel_id_' . $channel_id;
            $perms[] = 'can_edit_self_entries_channel_id_' . $channel_id;
            $perms[] = 'can_edit_other_entries_channel_id_' . $channel_id;
        }

        if (! ee('Permission')->hasAny($perms)) {
            return false;
        }

        return true;
    }

    public function index()
    {
        $allowed_channel_ids = (ee('Permission')->isSuperAdmin()) ? null : array_keys(ee()->session->userdata['assigned_channels']);

        $channels = ee('Model')->get('Channel', $allowed_channel_ids)
            ->fields('channel_id', 'channel_title', 'max_entries', 'total_records')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('channel_title', 'ASC')
            ->all();

        $list = new NavigationList();

        foreach ($channels as $channel) {
            if (ee('Permission')->can('create_entries_channel_id_' . $channel->getId())) {
                // Only add Create link if channel has room for more entries
                if (!$channel->maxEntriesLimitReached()) {
                    $publishLink = ee('CP/URL')->make(
                        'publish/create/' . $channel->channel_id,
                        [
                            'site_id' => $channel->site_id,
                            'hide_closer' => 'y',
                            'preview' => 'y',
                            'prefer_system_preview' => 'y',
                            'return' => ee('Request')->get('current_uri')
                        ],
                        ee()->config->item('cp_url')
                    );
                    $listitem = $list->addItem($channel->channel_title, $publishLink)->withAddLink($publishLink, 'target="_top"')->withAttributes('target="_top"');
                }
            }
        }

        $out = '<style type="text/css">.dropdown {display: block}</style>';

        $out .= $list->render(ee('View'));

        return $out;
    }

}

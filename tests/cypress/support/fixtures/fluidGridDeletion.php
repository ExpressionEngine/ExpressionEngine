<?php

require __DIR__ . '/bootstrap.php';

use ExpressionEngine\Model\Channel\Channel;
use ExpressionEngine\Model\Channel\ChannelEntry;
use ExpressionEngine\Model\Channel\ChannelField;

/**
 * Create a field with its real storage table and fieldtype settings.
 *
 * @param string $type The fieldtype to install in the fixture.
 * @param array $settings Additional fieldtype settings.
 * @return ChannelField
 */
function createDeletionField(string $type, array $settings = []): ChannelField
{
    $field = ee('Model')->make('ChannelField', array_merge([
        'site_id' => 1,
        'field_name' => 'delete_test_' . $type,
        'field_label' => 'Deletion test ' . $type,
        'field_type' => $type,
        'field_required' => 'n',
        'field_search' => 'n',
        'field_fmt' => 'none',
        'legacy_field_data' => 'n',
    ], $settings));
    $field->save();

    return $field;
}

/**
 * Create a real channel entry without relying on seed entry IDs.
 *
 * @param Channel $channel The fixture channel.
 * @param string $name The entry's unique title and URL title.
 * @return ChannelEntry
 */
function createDeletionEntry(Channel $channel, string $name): ChannelEntry
{
    $author = ee('Model')->get('Member')->filter('role_id', 1)->first();
    $entry = ee('Model')->make('ChannelEntry', [
        'site_id' => 1,
        'channel_id' => $channel->channel_id,
        'author_id' => $author->member_id,
        'title' => $name,
        'url_title' => $name,
        'status' => 'open',
        'status_id' => 1,
        'entry_date' => ee()->localize->now,
    ]);
    $entry->save();

    return $entry;
}

/**
 * Seed two Fluid items, each containing two Grid rows with relationships.
 *
 * @param int $entryId The owning entry.
 * @param int $childId The related entry, which must survive deletion.
 * @param int $fluidId The parent Fluid field.
 * @param int $gridId The nested Grid or File Grid field.
 * @param int $columnId The relationship column within that Grid.
 * @return void
 */
function seedDeletionItems(int $entryId, int $childId, int $fluidId, int $gridId, int $columnId): void
{
    for ($item = 0; $item < 2; $item++) {
        ee()->db->insert('channel_data_field_' . $gridId, [
            'entry_id' => $entryId,
            'field_id_' . $gridId => 'Entry ' . $entryId . ', item ' . $item,
        ]);
        $dataId = ee()->db->insert_id();
        ee()->db->insert('fluid_field_data', [
            'fluid_field_id' => $fluidId,
            'entry_id' => $entryId,
            'field_id' => $gridId,
            'field_data_id' => $dataId,
            'order' => $item,
        ]);
        $itemId = ee()->db->insert_id();

        for ($row = 0; $row < 2; $row++) {
            ee()->db->insert('channel_grid_field_' . $gridId, [
                'entry_id' => $entryId,
                'fluid_field_data_id' => $itemId,
                'row_order' => $row,
                'col_id_' . $columnId => (string) $childId,
            ]);
            $rowId = ee()->db->insert_id();
            ee()->db->insert('relationships', [
                'parent_id' => $entryId,
                'child_id' => $childId,
                'field_id' => $columnId,
                'grid_field_id' => $gridId,
                'grid_col_id' => $columnId,
                'grid_row_id' => $rowId,
                'fluid_field_data_id' => $itemId,
                'order' => 0,
            ]);
        }
    }
}

$action = $argv[1] ?? '';

if ($action === 'setup') {
    $type = $argv[2] ?? '';
    if (!in_array($type, ['grid', 'file_grid'], true)) {
        throw new InvalidArgumentException('Expected grid or file_grid.');
    }

    $grid = createDeletionField($type, ['field_content_type' => 'all', 'allowed_directories' => 'all']);
    ee()->load->add_package_path(PATH_ADDONS . 'grid');
    ee()->load->library('grid_lib');
    ee()->grid_lib->field_id = $grid->field_id;
    ee()->grid_lib->content_type = 'channel';
    $columns = [];

    foreach ($type === 'file_grid' ? ['file', 'relationship'] : ['relationship'] as $columnType) {
        $columns['new_' . $columnType] = [
            'col_type' => $columnType,
            'col_label' => ucfirst($columnType),
            'col_name' => $columnType,
            'col_instructions' => '',
            'col_required' => 'n',
            'col_search' => 'n',
            'col_settings' => [],
        ];
    }

    ee()->grid_lib->apply_settings(['field_id' => $grid->field_id, 'grid' => ['cols' => $columns]]);
    $column = ee()->db->get_where('grid_columns', [
        'field_id' => $grid->field_id,
        'col_type' => 'relationship',
    ])->row_array();
    $fluid = createDeletionField('fluid_field', ['field_channel_fields' => [$grid->field_id]]);
    $channel = ee('Model')->make('Channel', [
        'site_id' => 1,
        'channel_name' => 'fluid_deletion',
        'channel_title' => 'Fluid deletion',
    ]);
    $channel->CustomFields = [$fluid];
    $channel->save();

    $deleted = createDeletionEntry($channel, 'delete-this-entry');
    $survivor = createDeletionEntry($channel, 'keep-this-entry');
    $child = createDeletionEntry($channel, 'relationship-target');

    foreach ([$deleted, $survivor] as $entry) {
        seedDeletionItems($entry->entry_id, $child->entry_id, $fluid->field_id, $grid->field_id, $column['col_id']);
    }

    echo json_encode([
        'deletedId' => $deleted->entry_id,
        'survivorId' => $survivor->entry_id,
        'childId' => $child->entry_id,
        'fluidId' => $fluid->field_id,
        'gridId' => $grid->field_id,
    ]);
} elseif (in_array($action, ['delete-entry', 'delete-fluid'], true)) {
    $entryId = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$entryId) {
        throw new InvalidArgumentException('Expected a positive entry ID.');
    }

    $entry = ee('Model')->get('ChannelEntry', $entryId)->first();
    if ($action === 'delete-entry') {
        $entry->delete();
    } else {
        // Keep the entry alive so its relationship cascade cannot mask a missing Grid callback.
        foreach ($entry->getCustomFields() as $field) {
            $field->delete();
        }
    }
} else {
    throw new InvalidArgumentException('Expected setup, delete-entry, or delete-fluid.');
}

<ul class="asset-list">
<?php foreach ($asset_data as $channel_id => $field) : ?>
    <li>
        <strong style="margin-right:3px"><?=$field['channel_title'];?>: </strong>
        <span class="listing-controls">
            <a href="<?=ee('CP/URL')->make('publish/create/' . $channel_id);?>" class="listing-add">Add</a>
            /
            <a href="<?=ee('CP/URL')->make('publish/edit', array('channel_id' => $channel_id));?>" class="listing-edit">Edit</a>
        </span>
    </li>
<?php endforeach; ?>
</ul>

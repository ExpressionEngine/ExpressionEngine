<?php $image_info = ee()->image_lib->get_image_properties($file->getAbsolutePath(), true); ?>

<div class="f_metadata-section">
    <p class="f_metadata-item">
        <span class="f_meta-name"><?=lang('name')?></span>
        <span class="f_meta-info"><?=$file->file_name?></span>
    </p>

    <p class="f_metadata-item">
        <span class="f_meta-name"><?=lang('size')?></span>
        <span class="f_meta-info"><?=$image_info['width'] . ' x ' . $image_info['height'] . ' - ' . ee('Format')->make('Number', $file->file_size)->bytes()?></span>
    </p>

    <p class="f_metadata-item">
        <span class="f_meta-name"><?=lang('image_manip_type')?></span>
        <span class="f_meta-info"><?=$file->mime_type?></span>
    </p>

    <p class="f_metadata-item">
        <span class="f_meta-name"><?=lang('dimensions')?></span>
        <span class="f_meta-info">2500 x 1500 pixels</span>
    </p>

    <p class="f_metadata-item">
        <span class="f_meta-name"><?=lang('last_modified')?></span>
        <span class="f_meta-info">2/15/22 3:30PM</span>
    </p>

    <p class="f_metadata-item">
        <span class="f_meta-name"><?=lang('added_by')?></span>
        <span class="f_meta-info">ajonhson</span>
    </p>
</div>
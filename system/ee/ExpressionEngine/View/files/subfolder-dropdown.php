<?php foreach ($data as $key => $item) : ?>
    <li><a href="#" data-path="<?=$item['path']?>" data-upload_location_id="<?=$item['upload_location_id']?>" data-directory_id="<?=$item['directory_id']?>" class="dropdown__link"><?=$item['label']?><?php if (isset($selected_subfolder) && $key == $selected_subfolder) : ?> (selected)<?php endif; ?></a></li>
    <?php 
    if (!empty ($item['children'])): ?>
        <ul>
            <?php $this->embed('ee:files/subfolder-dropdown', array(
                'data' => $item['children'],
                'selected_subfolder' => isset($selected_subfolder) ? $selected_subfolder : null,
            )); ?>
        </ul>
    <?php endif; ?>
<?php endforeach; ?>

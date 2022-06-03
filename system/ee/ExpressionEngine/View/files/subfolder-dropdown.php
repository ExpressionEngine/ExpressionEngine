<?php foreach ($data as $item) : ?>
    <li><a href="#" data-upload_location_id="<?=$item['upload_location_id']?>" class="dropdown__link"><?=$item['label']?></a></li>
    <?php 
    if (!empty ($item['children'])): ?>
        <ul>
            <?php $this->embed('ee:files/subfolder-dropdown', array(
                'data' => $item['children'],
            )); ?>
        </ul>
    <?php endif; ?>
<?php endforeach; ?>

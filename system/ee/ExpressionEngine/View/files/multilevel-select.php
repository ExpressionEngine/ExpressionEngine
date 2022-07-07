<div class="button-toolbar toolbar multilevel-select">
    <button type="button" class="js-dropdown-toggle button button--default button--small" title="folders modal">
        <?=lang('select'); ?>
        <i class="fal fa-angle-down"></i>
    </button>

    <div class="dropdown">
        <div class="dropdown__scroll">
            <ul>
                <?php foreach ($choices as $key => $data): ?>
                    <li>
                        <a href="#" data-upload_location_id="<?=$data['upload_location_id']?>" class="dropdown__link">
                            <?=$data['label']?><?php if ($key == $current_subfolder) : ?> (current)<?php endif; ?>
                        </a>
                        <?php
                            if (! empty($data['children'])) {
                                echo '<ul>';
                                $this->embed('ee:files/subfolder-dropdown', [
                                    'data' => $data['children'],
                                    'current_subfolder' => $current_subfolder,
                                ]);
                                echo '</ul>';
                            }
                        ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</div>

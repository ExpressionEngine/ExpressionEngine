<div class="modal-wrap modal-wrap--small <?=$name?> hidden">
    <div class="modal modal--no-padding dialog dialog--danger">

        <?=form_open($form_url, '', (isset($hidden)) ? $hidden : array())?>
        <div class="dialog__header">
            <h2 class="dialog__title"><?=isset($title) ? $title : lang('new_folder') ?></h2>
            <div class="dialog__close js-modal-close"><i class="fas fa-times"></i></div>
        </div>

        <div class="dialog__body">
            <?=isset($alert) ? $alert : lang('create_folder_location')?>

            <p>
                <label for="upload_location"><?= lang('location') ?></label>
            </p>

            <p>
                <select name="upload_location" id="upload_location">
                    <?php foreach ($destinations as $destination):?>
                        <option <?= ($destination['selected']) ? 'selected' : '' ?> value='<?= $destination['id'] ?>'><?=  $destination['value'] ?></option>
                    <?php endforeach;?>
                </select>
            </p>

            <div class="button-toolbar toolbar multilevel-select">
                <button type="button" class="js-dropdown-toggle button button--default button--small" title="folders modal">
                    <?=lang('select'); ?>
                    <i class="fas fa-angle-down"></i>
                </button>

                <div class="dropdown">
                    <div class="dropdown__scroll">
                        <ul>
                            <?php foreach ($choices as $key => $data): ?>
                                <li>
                                    <a href="#" data-upload_location_id="<?=$data['upload_location_id']?>" class="dropdown__link">
                                        <?=$data['label']?><?php if (empty($selected_subfolder) && $key == $selected) : ?> (selected)<?php endif; ?>
                                    </a>
                                    <?php
                                        if (! empty($data['children'])) {
                                            echo '<ul>';
                                            $this->embed('ee:files/subfolder-dropdown', [
                                                'data' => $data['children'],
                                                'selected_subfolder' => $selected_subfolder,
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

            <p>
                <label for="folder_name"><?= lang('folder_name') ?></label>
                <input type="text" name="folder_name" autocomplete="off">
            </p>
        </div>

        <div class="dialog__actions">
            <div class="dialog__buttons">
                <button class="button button--primary button--danger" type="submit" value="<?=lang('create'); ?>" data-submit-text="<?=lang('create'); ?>" data-work-text="<?=lang('btn_create_file_working'); ?>"><?=lang('create'); ?></button>
            </div>
        </div>
        </form>
    </div>
</div>

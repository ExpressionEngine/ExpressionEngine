<?php if (!$modal_form) { $this->extend('_templates/default-nav'); } ?>

<?php
$form_class = '';
if (isset($ajax_validate) && $ajax_validate == true) {
    $form_class .= 'ajax-validate';
}
$attributes = 'class="' . $form_class . '"';
if (isset($has_file_input) && $has_file_input == true) {
    $attributes .= ' enctype="multipart/form-data"';
}
if (! isset($alerts_name)) {
    $alerts_name = 'shared-form';
}
?>
<?=form_open($base_url, $attributes, (isset($form_hidden)) ? $form_hidden : array())?>

<?=form_hidden('action')?>

<div class="file-edit-view">
    <div class="panel">
        <div class="panel-heading">
            <div class="form-btns form-btns-top">
                <div class="title-bar title-bar--large">
                    <h3 class="title-bar__title" title="<?=$file->title?>">
                        <?=$file->title?>

                    </h3>

                    <div class="title-bar__extra-tools">
                      <a class="button button--secondary js-copy-url-button" href="<?=$file->getAbsoluteURL()?>" rel="external"  title="<?=lang('copy_link')?>">
                          <i class="fal fa-link"></i>
                      </a>
                        <a class="btn button button--secondary" href="<?=$download_url?>" title="<?=lang('download')?>"><?=lang('download')?></a>
                        <?php $this->embed('ee:_shared/form/buttons'); ?>
                    </div>
                </div>
            </div>
            <div class="entry-pannel-notice-wrap">
                <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

                <div class="alert alert--success f_manager-alert">
                    <div class="alert__icon"><i class="fal fa-check-circle fa-fw"></i></div>
                    <div class="alert__content">
                        <?=lang('link_copied')?>
                    </div>
                </div>

            </div>
      </div>



      <div class="panel-body file-preview-modal">
            <?php if (!$modal_form) : ?>
            <div class="file-preview-modal__preview">
                <?php if ($is_image) : ?>
                    <a href="<?=$file->getAbsoluteURL() . '?v=' . time()?>" target="_blank"><img src="<?=$file->getAbsoluteURL() . '?v=' . time()?>"></a>
                <?php else : ?>
                    <div class="file-preview-modal__preview-file-name"><?=str_replace('fa-3x', 'fa-10x', ee('Thumbnail')->get($file)->tag)?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="file-preview-modal__form">
                <div class="form-standard">
                    <div class="">
                        <?php if (isset($tabs)) :?>
                            <?php $active_tab = (isset($active_tab)) ? $active_tab : array_key_first($tabs); ?>
                            <div class="tab-wrap">
                                <div class="tab-bar">
                                    <div class="tab-bar__tabs">
                                    <?php foreach (array_keys($tabs) as $key) :
                                        $class = '';
                                        if ($key == $active_tab) {
                                            $class = 'active';
                                        }

                                        if (strpos($tabs[$key], 'class="ee-form-error-message"') !== false) {
                                            $class .= ' invalid';
                                        }
                                    ?>
                                        <button type="button" class="js-tab-button tab-bar__tab <?=$class?>" data-action="<?=$key?>" rel="t-<?=$key?>">
                                        <?=lang($key)?>
                                            <?php if ($key == 'usage') : ?>
                                                <span class="tab-bar__tab-notification tab-notification-generic"><?=$usage_count?></span>
                                            <?php endif; ?>
                                        </button>
                                    <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php
                            if (isset($extra_alerts)) {
                                foreach ($extra_alerts as $alert) {
                                    echo ee('CP/Alert')->get($alert);
                                }
                            }
                            if (isset($tabs)) :
                                foreach ($tabs as $key => $html) :
                            ?>
                            <div class="tab t-<?=$key?><?php if ($key == $active_tab) { echo ' tab-open'; }?>"><?=$html?></div>
                                <?php
                                    endforeach;
                                endif;

                                $secure_form_ctrls = array();

                                if (isset($sections['secure_form_ctrls'])) {
                                    $secure_form_ctrls = $sections['secure_form_ctrls'];
                                    unset($sections['secure_form_ctrls']);
                                }
                                foreach ($sections as $name => $settings) {
                                    $this->embed('_shared/form/section', array('name' => $name, 'settings' => $settings));
                                }
                                ?>
                        <?php foreach ($secure_form_ctrls as $setting) :
                            $this->embed('ee:_shared/form/fieldset', ['setting' => $setting, 'group' => false]); ?>
                        <?php endforeach ?>
                        </div>

                        <?php if (isset($tabs)) :?>
                            </div>
                        <?php endif; ?>

                </div>

            </div>
      </div>
    </div>
</div>
</form>

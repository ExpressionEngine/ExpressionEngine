<?=form_input($field)?>
<?php

$show_fmt = (isset($settings['field_show_fmt']) && $settings['field_show_fmt'] == 'y');
if ($show_fmt) {
    $format_name = str_replace('field_id_' . $settings['field_id'], 'field_ft_' . $settings['field_id'], $name);
}
$show_file_selector = (isset($settings['field_show_file_selector']) && $settings['field_show_file_selector'] == 'y');

if ($show_fmt or $show_file_selector): ?>
<div class="format-options">
    <div class="d-flex align-items-center">

    <?php if ($show_file_selector): ?>
        <div class="button-toolbar toolbar">
            <div class="button-group button-group-xsmall">
                <a class="m-link textarea-field-filepicker html-upload button button--default" href="<?=$fp_url?>" rel="modal-file" title="<?=lang('upload_file')?>" rel="modal-file" data-input-value="<?=$name?>"></a>
                <?php if ($show_fmt): ?>
                    <?=form_dropdown($format_name, $format_options, $settings['field_fmt'])?>
                    <span class="mr-s"></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (! $show_file_selector && $show_fmt): ?>
        <?=form_dropdown($format_name, $format_options, $settings['field_fmt'])?>
    <?php endif; ?>
    </div>
</div>
<?php endif; ?>

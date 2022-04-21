<?php $this->extend('_templates/default-nav'); ?>

<div class="file-edit-view">
    <div class="panel">
        <div class="panel-heading">
            <div class="form-btns form-btns-top">
                <div class="title-bar title-bar--large">
                    <h3 class="title-bar__title"><?=$file->title?></h3>

                    <div class="title-bar__extra-tools">
                        <a class="btn button button--primary" href="#">Replace</a>
                        <a class="btn button button--primary" href="<?=$download_url?>" title="<?=lang('download')?>">Download</a>
                        <?php $this->embed('ee:_shared/form/buttons'); ?>
                    </div>
                </div>
            </div>
      </div>

      <div class="panel-body file-preview-modal">
            <div class="file-preview-modal__preview">
                <?php if ($is_image) {
                    echo "<a href=\"{$file->getAbsoluteURL()}\" target=\"_blank\"><img src=\"{$file->getAbsoluteURL()}\"></a>";
                } else {
                    echo "<div class=\"file-preview-modal__preview-file-name\">{$file->file_name}</div>";
                } ?>
            </div>

            <div class="file-preview-modal__form">
                <?php $this->embed('_shared/file/edit_file_template'); ?>
            </div>
      </div>
    </div>
</div>
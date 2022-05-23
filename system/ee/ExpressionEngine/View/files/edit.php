<?php $this->extend('_templates/default-nav'); ?>

<div class="file-edit-view">
    <div class="panel">
        <div class="panel-heading">
            <div class="form-btns form-btns-top">
                <div class="title-bar title-bar--large">
                    <h3 class="title-bar__title">
                        <?=$file->title?>
                        <a class="button button--large filter-bar__button js-copy-url-button" href="<?=$file->getAbsoluteURL()?>" rel="external"  title="<?=lang('copy_link')?>">
                            <i class="fas fa-link"></i>
                        </a>
                    </h3>

                    <div class="title-bar__extra-tools">
                        <a class="btn button button--secondary" href="#">Replace</a>
                        <a class="btn button button--secondary" href="<?=$download_url?>" title="<?=lang('download')?>"><?=lang('download')?></a>
                        <?php $this->embed('ee:_shared/form/buttons'); ?>
                    </div>
                </div>
            </div>
      </div>

    <div class="alert alert--success f_manager-alert">
        <div class="alert__icon"><i class="fas fa-check-circle fa-fw"></i></div>
        <div class="alert__content">
            <p class="alert__title">link copied</code></p>
        </div>
    </div>

      <div class="panel-body file-preview-modal">
            <div class="file-preview-modal__preview">
                <?php if ($is_image) : ?>
                    <a href="<?=$file->getAbsoluteURL() . '?v=' . time()?>" target="_blank"><img src="<?=$file->getAbsoluteURL() . '?v=' . time()?>"></a>
                <?php else: ?>
                    <div class="file-preview-modal__preview-file-name"><?=$file->file_name?></div>
                <?php endif; ?>
            </div>

            <div class="file-preview-modal__form">
                <?php $this->embed('_shared/file/edit_file_template'); ?>
            </div>
      </div>
    </div>
</div>

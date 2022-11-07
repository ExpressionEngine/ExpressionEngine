<?php $this->extend('_templates/default-nav'); ?>
<div class="panel">
    <?=form_open($form_url)?>
    <div class="panel-heading">
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

        <div class="form-btns form-btns-top">
            <div class="title-bar title-bar--large">
            <h3 class="title-bar__title"><?=$cp_heading?><br><i><?=$cp_sub_heading?></i></h3>

                <div class="title-bar__extra-tools">
                    <?php $this->embed('ee:_shared/form/buttons'); ?>
                </div>
            </div>
        </div>
    </div>

  <div class="panel-body">
        <?php $this->embed('_shared/table', $table); ?>
        <?php $this->embed('_shared/pagination'); ?>
  </div>

    <div class="panel-footer">
        <div class="form-btns">
            <?php $this->embed('ee:_shared/form/buttons'); ?>
        </div>
    </div>
    <?=form_close()?>
</div>

<?php $this->extend('_templates/wrapper'); ?>

<div class="four-o-four">
    <div class="four-o-four__inner">
    <h1 class="four-o-four__title"><?=$code?>: <?=lang('http_code_' . $code)?></h1>
    <div class="four-o-four__body typography">
        <p><?=$message?></p>
    </div>
    </div>
</div>

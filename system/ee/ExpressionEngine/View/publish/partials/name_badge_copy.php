<span class="app-badge label-app-badge js-app-badge <?php if( !isset($_SERVER['HTTPS'] ) ): ?>not-clickable<?php endif; ?>"
    data-id="<?= isset($id) ? $id : '' ?>"
    data-content_type="<?= isset($content_type) ? $content_type : '' ?>"
>
    <span class="txt-only">{<?=$name?>}</span>
    <?php if( isset($_SERVER['HTTPS'] ) ): ?>

        <i class="fa-light fa-copy"></i>
        <i class="fa-sharp fa-solid fa-circle-check hidden"></i>
    <?php endif; ?>
</span>

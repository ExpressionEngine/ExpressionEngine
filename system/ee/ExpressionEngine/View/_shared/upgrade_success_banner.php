<?php if ((empty(ee()->uri->segment(2)) || ee()->uri->segment(2) == 'homepage') && ee()->session->getMember()->dismissed_banner!='y') : ?>
<div class="upgrade-success-banner__wrapper" style="background-image: url('<?=URL_THEMES?>asset/img/ee-pro-promo-banner-bg@2x.png');">
    <div class="upgrade-success-banner__header">
        <a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title" target="_blank" rel="noopener"><span aria-hidden="true">&#127881;</span> ExpressionEngine <strong><?=APP_VER?></strong></a>
        <a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title-link" target="_blank" rel="noopener"><i class="fas fa-clipboard" aria-hidden="true"></i> Release Notes&hellip;</a>
    </div>

    <!-- Change number of blurb columns via inline grid style below IF less than 3 blurbs: -->
    <div class="upgrade-success-banner__blurb-wrapper" style="grid-template-columns: repeat(4, 1fr);">

        <a href="https://expressionengine.com/blog/expressionengine-7.6.0" class="upgrade-success-banner__blurb" target="_blank" rel="noopener">
            <i class="fal fa-newspaper fa-fw" aria-hidden="true"></i>
            <h6>ExpressionEngine 7.6.0</h6>
            <p>A new Contrast theme, one-click URL title refresh, PHP 8.6 support, and <span class="upgrade-success-banner__blurb-learn">more in 7.6.0.</span></p>
        </a>
        <a href="https://u.expressionengine.com/article/configuring-mfa-session-length-in-expressionengine-7.6" class="upgrade-success-banner__blurb" target="_blank" rel="noopener">
            <i class="fal fa-shield-check fa-fw" aria-hidden="true"></i>
            <h6>MFA Session Length</h6>
            <p>Set how long a browser remembers a successful MFA verification between sign-ins. <span class="upgrade-success-banner__blurb-learn">See how to configure it.</span></p>
        </a>
        <a href="https://expressionengine.com/blog/expressionengine-7.5-what-you-missed" class="upgrade-success-banner__blurb" target="_blank" rel="noopener">
            <i class="fal fa-list-check fa-fw" aria-hidden="true"></i>
            <h6>What You Missed in 7.5</h6>
            <p>Catch up on improved File Manager search, Live Preview that keeps your place, and <span class="upgrade-success-banner__blurb-learn">updates to Grid.</span></p>
        </a>
        <a href="https://expressionengine.com/blog/expressionengine-6-to-enter-legacy-status-at-the-end-of-2025" class="upgrade-success-banner__blurb" target="_blank" rel="noopener">
            <i class="fal fa-clock fa-fw" aria-hidden="true"></i>
            <h6>ExpressionEngine 6 Legacy Status</h6>
            <p>Find out what legacy status means for ExpressionEngine 6 and <span class="upgrade-success-banner__blurb-learn">how to plan your upgrade.</span></p>
        </a>
    </div>
    <a href="<?=ee('CP/URL')->make('homepage/dismiss-banner')->compile();?>" class="banner-dismiss"><span class="sr-only"><?=lang('close_banner')?></span></a>
</div>
<?php endif; ?>

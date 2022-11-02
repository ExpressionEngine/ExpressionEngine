<?php if ((empty(ee()->uri->segment(2)) || ee()->uri->segment(2) == 'homepage') && ee()->session->getMember()->dismissed_banner != 'y') : ?>
    <div class="upgrade-success-banner__wrapper" style="background-image: url('<?= URL_THEMES ?>asset/img/ee-pro-promo-banner-bg@2x.png');">
        <a href="<?= DOC_URL . 'installation/changelog.html#version-640' ?>" class="upgrade-success-banner__title" target="_blank">&#127881; ExpressionEngine <strong>6.4.0</strong></a>
        <a href="<?= DOC_URL . 'installation/changelog.html#version-640' ?>" class="upgrade-success-banner__title-link" target="_blank"><i class="fas fa-clipboard"></i> Release Notes&hellip;</a>

        <!-- Change number of blurb columns via inline grid style below IF less than 3 blurbs: -->
        <div class="upgrade-success-banner__blurb-wrapper" style="grid-template-columns: repeat(3, 1fr);">

            <a href="https://expressionengine.com/blog/expressionengine-6.4-7.2" class="upgrade-success-banner__blurb" target="_blank">
                <i class="fas fa-folder-open fa-fw"></i>
                <h6>Welcome to ExpressionEngine 6.4</h6>
                <p>ExpressionEngine 6.4 brings with it the LARGE performance scope originally included in the ExpressionEngine 7.0 release. <span class="upgrade-success-banner__blurb-learn">Find out more&hellip;</span></p>
            </a>
            <a href="https://expressionengine.com/blog/conditional-fields" class="upgrade-success-banner__blurb" target="_blank">
                <i class="fas fa-i-cursor fa-fw"></i>
                <h6>Conditional Fields</h6>
                <p>Conditional fields bring the ExpressionEngine content administration experience to the next level. It does this on the fly by changing the fields that are available and required based on content input into the entry in real-time. <span class="upgrade-success-banner__blurb-learn">Find out more&hellip;</span></p>
            </a>
            <a href="https://expressionengine.com/blog/expressionengine-7-official-release" class="upgrade-success-banner__blurb" target="_blank">
                <i class="fas fa-star fa-fw"></i>
                <h6>ExpressionEngine 7</h6>
                <p>We're pleased to announce the release of ExpressionEngine version 7. The largest ExpressionEngine release ever. <span class="upgrade-success-banner__blurb-learn">Find out more&hellip;</span></p>
            </a>
        </div>
        <a href="<?= ee('CP/URL')->make('homepage/dismissBanner')->compile(); ?>" class="banner-dismiss"></a>
    </div>
<?php endif; ?>
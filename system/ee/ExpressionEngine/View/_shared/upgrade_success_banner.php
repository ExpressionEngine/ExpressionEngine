<?php if ((empty(ee()->uri->segment(2)) || ee()->uri->segment(2) == 'homepage') && ee()->session->getMember()->dismissed_banner!='y') : ?>
<div class="upgrade-success-banner__wrapper" style="background-image: url('<?=URL_THEMES?>asset/img/ee-pro-promo-banner-bg@2x.png');">
    <a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title" target="_blank">&#127881; ExpressionEngine <strong><?=APP_VER?></strong></a>
    <a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title-link" target="_blank"><i class="fas fa-clipboard"></i> Release Notes&hellip;</a>

    <!-- Change number of blurb columns via inline grid style below IF less than 3 blurbs: -->
    <div class="upgrade-success-banner__blurb-wrapper" style="grid-template-columns: repeat(4, 1fr);">

        <a href="https://expressionengine.com/blog/expressionengine-7.5" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-newspaper fa-fw"></i>
            <h6>ExpressionEngine 7.5.0</h6>
            <p>7.5 is a major step forward in helping developers be successful faster. Removing the guess work, simplifying template code, and much more! <span class="upgrade-success-banner__blurb-learn">Read the blog post&hellip;</span></p>
        </a>
        <a href="https://expressionengine.com/blog/7.5-code-generators" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-wand-magic-sparkles fa-fw"></i>
            <h6>Code Generators</h6>
            <p>Making development easier and more fun.  Build your dreams in ExpressionEngine <span class="upgrade-success-banner__blurb-learn">faster than ever before&hellip;</span></p>
        </a>
        <a href="https://expressionengine.com/blog/ee75-inline-form-validation-errors-everywhere" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-check-double fa-fw"></i>
            <h6>Form Validation</h6>
            <p>In 7.5 form validation has been standardized and expanded across even more tags.  You also have more flexibility to handle errors inline with your forms!</p>
        </a>
        <a href="https://expressionengine.com/blog/expressionengine-7.5-all-the-cool-little-additions" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-sunglasses fa-fw"></i>
            <h6>Cool Little Additions</h6>
            <p>Dramatically simplified templates for field groups in Fluid, new channel:fields tags <span class="upgrade-success-banner__blurb-learn">and a bunch more!</span></p>
        </a>
    </div>
    <a href="<?=ee('CP/URL')->make('homepage/dismiss-banner')->compile();?>" class="banner-dismiss"><span class="sr-only"><?=lang('close_banner')?></span></a>
</div>
<?php endif; ?>

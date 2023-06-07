<?php if ((empty(ee()->uri->segment(2)) || ee()->uri->segment(2) == 'homepage') && ee()->session->getMember()->dismissed_banner!='y') : ?>
<div class="upgrade-success-banner__wrapper" style="background-image: url('<?=URL_THEMES?>asset/img/ee-pro-promo-banner-bg@2x.png');">
    <a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title" target="_blank">&#127881; ExpressionEngine <strong><?=APP_VER?></strong></a>
    <a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title-link" target="_blank"><i class="fas fa-clipboard"></i> Release Notes&hellip;</a>

    <!-- Change number of blurb columns via inline grid style below IF less than 3 blurbs: -->
    <div class="upgrade-success-banner__blurb-wrapper" style="grid-template-columns: repeat(4, 1fr);">

        <a href="https://expressionengine.com/blog/expressionengine-7-official-release" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-newspaper fa-fw"></i>
            <h6>ExpressionEngine 7.3.0</h6>
            <p>Packed with the features targeted to improve and speed up your content management process. <span class="upgrade-success-banner__blurb-learn">Read the blog post&hellip;</span></p>
        </a>
        <a href="https://docs.expressionengine.com/latest/fieldtypes/fluid.html" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-rectangles-mixed fa-fw"></i>
            <h6>Field Groups in Fluid</h6>
            <p>Assign group of fields to Fluid field, operate and display those in template as a single unit of content.</p>
        </a>
        <a href="https://docs.expressionengine.com/latest/templates/variable-modifiers.html" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-key-skeleton-left-right fa-fw"></i>
            <h6>Chained Variables Modifiers</h6>
            <p><code>{image:resize:rotate:webp}</code> is now easy. Apply multiple modifiers to fields and variables with a single template tag.</p>
        </a>
        <a href="https://docs.expressionengine.com/latest/cli/intro.html" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-rectangle-terminal fa-fw"></i>
            <h6>New CLI commands</h6>
            <p>Manage add-ons, back up the database, change system settings and do other things with Command Line Interface.</p>
        </a>
    </div>
    <a href="<?=ee('CP/URL')->make('homepage/dismiss-banner')->compile();?>" class="banner-dismiss"></a>
</div>
<?php endif; ?>

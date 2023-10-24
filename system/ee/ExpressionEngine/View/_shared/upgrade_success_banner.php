<?php if ((empty(ee()->uri->segment(2)) || ee()->uri->segment(2) == 'homepage') && ee()->session->getMember()->dismissed_banner!='y') : ?>
<div class="upgrade-success-banner__wrapper" style="background-image: url('<?=URL_THEMES?>asset/img/ee-pro-promo-banner-bg@2x.png');">
    <a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title" target="_blank">&#127881; ExpressionEngine <strong><?=APP_VER?></strong></a>
    <a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title-link" target="_blank"><i class="fas fa-clipboard"></i> Release Notes&hellip;</a>

    <!-- Change number of blurb columns via inline grid style below IF less than 3 blurbs: -->
    <div class="upgrade-success-banner__blurb-wrapper" style="grid-template-columns: repeat(4, 1fr);">

        <a href="https://expressionengine.com/blog/expressionengine-7.3" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-newspaper fa-fw"></i>
            <h6>ExpressionEngine 7.3.0</h6>
            <p>Packed with features targeted to speed up and improve your content management process. <span class="upgrade-success-banner__blurb-learn">Read the blog post&hellip;</span></p>
        </a>
        <a href="https://docs.expressionengine.com/latest/fieldtypes/fluid.html" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-rectangles-mixed fa-fw"></i>
            <h6>Field Groups in Fluid</h6>
            <p>Assign field groups to a fluid field, to easily tie the content admin experience to components in your templates and on your site!</p>
        </a>
        <a href="https://docs.expressionengine.com/latest/templates/variable-modifiers.html" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-key-skeleton-left-right fa-fw"></i>
            <h6>Chained Variable Modifiers</h6>
            <p><code>{image:resize:rotate:webp}</code> is that simple, and right in your template! Apply multiple modifiers to fields, variables, and content with a single template tag.</p>
        </a>
        <a href="https://docs.expressionengine.com/latest/cli/intro.html" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-rectangle-terminal fa-fw"></i>
            <h6>New CLI commands</h6>
            <p>Manage add-ons, back up the database, change settings, and streamline deployments through the improved Command Line Interface.</p>
        </a>
    </div>
    <a href="<?=ee('CP/URL')->make('homepage/dismiss-banner')->compile();?>" class="banner-dismiss"><span class="sr-only"><?=lang('close_banner')?></span></a>
</div>
<?php endif; ?>

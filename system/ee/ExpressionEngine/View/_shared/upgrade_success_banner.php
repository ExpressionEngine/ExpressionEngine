<?php if ((empty(ee()->uri->segment(2)) || ee()->uri->segment(2) == 'homepage') && ee()->session->getMember()->dismissed_banner!='y') : ?>
<div class="upgrade-success-banner__wrapper" style="background-image: url('<?=URL_THEMES?>asset/img/ee-pro-promo-banner-bg@2x.png');">
    <a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title" target="_blank">&#127881; ExpressionEngine <strong><?=APP_VER?></strong></a>
    <a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title-link" target="_blank"><i class="fas fa-clipboard"></i> Release Notes&hellip;</a>

    <!-- Change number of blurb columns via inline grid style below IF less than 3 blurbs: -->
    <div class="upgrade-success-banner__blurb-wrapper" style="grid-template-columns: repeat(4, 1fr);">

        <a href="https://expressionengine.com/blog/expressionengine-7.4" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-newspaper fa-fw"></i>
            <h6>ExpressionEngine 7.4.0</h6>
            <p>7.4 is a BIG update, packed with significant changes to members, the rich text editor, managing file metadata, channel forms, and categories! <span class="upgrade-success-banner__blurb-learn">Read the blog post&hellip;</span></p>
        </a>
        <a href="https://expressionengine.com/blog/7.4-preview-big-additions-to-member-management" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-rectangles-mixed fa-fw"></i>
            <h6>Big Additions to Member Management</h6>
            <p>A new channel fieldtype for members, member custom fields now include almost all native fieldtypes, control panel updates to manage members, and <span class="upgrade-success-banner__blurb-learn">much more!&hellip;</span></p>
        </a>
        <a href="https://expressionengine.com/blog/7.4.0-preview-redactor-x-in-the-rich-text-editor-rte" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-key-skeleton-left-right fa-fw"></i>
            <h6>Redactor X</h6>
            <p>A new, easier-to-customize, more powerful WYSIWYG editor for ExpressionEngine is now included in 7.4!</p>
        </a>
        <a href="https://expressionengine.com/blog/7.4.0-preview-cool-things" class="upgrade-success-banner__blurb" target="_blank">
            <i class="fal fa-rectangle-terminal fa-fw"></i>
            <h6>Cool Things You Need</h6>
            <p>7.4 also includes a pile of new quality-of-life additions. We took the time to <span class="upgrade-success-banner__blurb-learn">highlight a few of them&hellip;</span></p>
        </a>
    </div>
    <a href="<?=ee('CP/URL')->make('homepage/dismiss-banner')->compile();?>" class="banner-dismiss"><span class="sr-only"><?=lang('close_banner')?></span></a>
</div>
<?php endif; ?>

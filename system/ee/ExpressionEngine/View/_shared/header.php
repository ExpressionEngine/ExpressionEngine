<!doctype html>
<html lang="<?=ee()->lang->code()?>" dir="ltr">
    <head>
        <?=ee()->view->head_title($cp_page_title)?>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"  name="viewport">
        <?php if (ee('pro:Access')->hasRequiredLicense() && ee()->config->item('favicon')) : ?>
        <link rel="icon" type="image/x-icon" href="<?=ee()->config->item('favicon')?>" />
        <?php endif; ?>
        <?php if (isset($meta_refresh)): ?>
        <meta http-equiv='refresh' content='<?=$meta_refresh['rate']?>; url=<?=$meta_refresh['url']?>'>
        <?php endif;?>

        <?=ee()->view->head_link('css/common.min.css'); ?>
        <?php if (ee()->extensions->active_hook('cp_css_end') === true):?>
        <link rel="stylesheet" href="<?=ee('CP/URL', 'css/cp_global_ext')?>" type="text/css" />
        <?php endif;?>

        <?php if (ee()->config->item('site_color') != ''): ?>
        <style type="text/css">
            body {
                --ee-sidebar-title-bg: #<?=ee()->config->item('site_color')?>;
                --ee-sidebar-title-bg-hover: #<?=ee()->config->item('site_color')?>;
                --ee-sidebar-title-text: #FFFFFF;
            }
            .ee-sidebar__title, .ee-sidebar__title:hover {
                background-color: #<?=ee()->config->item('site_color')?>;
        color: #ffffff;
            }
            .ee-sidebar__title, .ee-sidebar__title-down-arrow {
                color: #FFFFFF;
            }
        </style>
        <?php endif; ?>

        <?php
        foreach (ee()->cp->get_head() as $item) {
            echo $item . "\n";
        }
        ?>
    </head>
    <body data-ee-version="<?=APP_VER?>" id="top"<?php echo isset($body_class) ? ' class="' . $body_class . '"' : ''; ?>>
        <script type="text/javascript">
        var currentTheme = localStorage.getItem('theme');

        // Restore the currently selected theme
        // This is at the top of the body to prevent the default theme from flashing
        if (currentTheme) {
            document.body.dataset.theme = currentTheme;
        }
        </script>

        <div class="global-alerts">
        <?=ee('CP/Alert')->getAllBanners()?>
        </div>

        <div class="theme-switch-circle"></div>

<?php
// Get the current page to highlight it in the sidebar
$current_page = ee()->uri->segment(2);
?>

    <div class="ee-wrapper-overflow">
        <section class="ee-wrapper">
            <?php if (!isset($hide_sidebar) || $hide_sidebar != true) :
                $this->embed('ee:_shared/sidebar/navigation/navigation');
            endif; ?>
            <div class="ee-main" role="main">

            <section class="lv-banner">
                <?php if(!empty(ee('pro:Access')->getLicenseNotices(['expired','invalid'], true))): ?>
                    <div class="lv-banner__inner alert <?= ee('pro:Access')->canManageLicenses() ? 'alert--error' : 'alert--warning'; ?>">
                        <div class="lv-banner__info alert__content">
                            <p class="alert__title">Software License Notice.</p>
                            <?php if(ee('pro:Access')->canManageLicenses()): ?>
                                <?php if(ee('pro:Access')->requiresValidLicense() && !empty(ee('pro:Access')->hasProNotice('invalid'))): ?>
                                    <p>Your ExpressionEngine site needs a license. <a href="https://expressionengine.com/store/purchase-pro" target="_blank">Please purchase ExpressionEngine Pro today.</a>
                                <?php elseif(!empty(ee('pro:Access')->hasProNotice('expired'))): ?>
                                    <p>Your ExpressionEngine license has expired. <a href="https://expressionengine.com/store/purchase-pro#renew" target="_blank">Please renew ExpressionEngine Pro today</a>.
                                <?php endif; ?>
                                <?php if(!empty(ee('pro:Access')->getLicenseNotices(['expired','invalid']))): ?>
                                    <p>
                                        There <?= count(ee('pro:Access')->getLicenseNotices(['invalid','expired'])) > 1 ? 'are several Add-ons' : 'is an Add-on'; ?> with a license problem. <a href="<?= ee('CP/URL', 'addons'); ?>">Please review and correct these issues.</a>
                                    </p>
                                <?php endif; ?>
                                <p>We recommend keeping licenses current to ensure smooth site operation, access to updates, and security fixes.</p>
                            <?php else: ?>
                                <p>Contact the site administrator and ask that they login to resolve any license notices.</p>
                                <p>Please note, this will not impact the operation of this site.</p>
                            <?php endif; ?>
                        </div>
                        <a class="js-lv-banner__close-btn alert__close">
                            <i class="fal fa-times alert__close-icon"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </section>

        <?php if (!isset($hide_topbar) || $hide_topbar != true) : ?>
        <div class="ee-main-header <?php if (!empty($head['class']) ): echo $head['class']; endif ?>">

          <a href="" class="sidebar-toggle<?php if (isset($collapsed_nav) && $collapsed_nav == '1') : ?> sidebar-toggle__collapsed<?php endif; ?>" title="<?=lang('toggle_sidebar')?>"><i class="fal fa-angle-<?php if (isset($collapsed_nav) && $collapsed_nav == '1') : ?>right<?php else : ?>left<?php endif; ?>"></i></a>

          <a class="main-nav__mobile-menu js-toggle-main-sidebar hidden">
                <svg xmlns="http://www.w3.org/2000/svg" width="18.585" height="13.939" viewBox="0 0 18.585 13.939"><g transform="translate(-210.99 -17.71)"><path d="M3,12.1H19.585" transform="translate(208.99 12.575)" fill="none" stroke-linecap="round" stroke-width="2"/><path d="M3,6H19.585" transform="translate(208.99 12.71)" fill="none" stroke-linecap="round" stroke-width="2"/><path d="M3,18H9.386" transform="translate(208.99 12.649)" fill="none" stroke-linecap="round" stroke-width="2"/></g></svg>
            </a>

          <?php if (count($cp_breadcrumbs)): ?>
            <div class="breadcrumb-wrapper">
              <ul class="breadcrumb">
                    <li><a href="<?=ee('CP/URL')->make('/')->compile()?>"><span class="sr-only"><?=ee()->config->item('site_name')?></span><i class="fal fa-home"></i></a></li>
                        <?php
                        $i = 0;
                        foreach ($cp_breadcrumbs as $link => $title):
                            $i++;
                            if ($i < count($cp_breadcrumbs)) :
                        ?>
                            <li><a href="<?=$link?>"><?=$title?></a></li>
                        <?php else: ?>
                            <li><span><?=$title?></span></li>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </ul>
            </div>
            <?php endif ?>

          <div class="field-control field-control_input--jump with-icon-start with-input-shortcut">
            <i class="fal fa-bullseye fa-fw icon-start jump-focus"></i>
            <label for="jumpEntry1" class="hidden"><?=lang('jump_menu_input')?></label>
            <input type="text" id="jumpEntry1" class="input--jump input--rounded jump-to" placeholder="<?=lang('jump_menu_input')?>" autocomplete="off">
            <span class="input-shortcut jump-focus">⌘J</span>
          </div>

          <div class="main-header__account">
            <button type="button" data-dropdown-offset="0px, 4px" data-dropdown-pos="bottom-end" class="main-nav__account-icon main-header__account-icon js-dropdown-toggle">
                    <?php if (isset($cp_avatar_path)) : ?>
                    <img src="<?= $cp_avatar_path ?>" alt="<?=$cp_screen_name?>">
                    <?php endif; ?>
                </button>
            <div class="dropdown dropdown--accent account-menu">
                    <div class="account-menu__header">
                        <div class="account-menu__header-title">
                            <h2><?=$cp_screen_name?></h2>
                            <span><?=$cp_member_primary_role_title?></span>
                        </div>

                    </div>

                    <a class="dropdown__link" href="<?=ee('CP/URL')->make('members/profile', array('id' => ee()->session->userdata('member_id')))?>"><i class="fal fa-user fa-fw"></i> <?=lang('my_profile')?></a>
              <a class="dropdown__link js-dark-theme-toggle" href=""><i class="fal fa-adjust fa-fw"></i> <?= lang('dark_theme') ?></a>

                    <div class="dropdown__divider"></div>

              <a class="dropdown__link" href="<?=ee('CP/URL', 'login/logout')?>"><i class="fal fa-sign-out-alt fa-fw"></i> <?=lang('log_out')?></a>

              <div class="dropdown__divider"></div>

                    <h3 class="dropdown__header"><?=lang('quick_links')?></h3>
                    <?php foreach ($cp_quicklinks as $link): ?>
                    <a class="dropdown__link" href="<?=$link['link']?>"><?=htmlentities($link['title'], ENT_QUOTES, 'UTF-8')?></a>
                    <?php endforeach ?>
                    <a class="dropdown__link" href="<?=ee('CP/URL')->make('members/profile/quicklinks/create', array('id' => ee()->session->userdata('member_id'), 'url' => ee('CP/URL')->getCurrentUrl()->encode(), 'name' => $cp_page_title))?>"><i class="fal fa-plus fa-sm"></i>  <?=lang('new_link')?></a>
                </div>
          </div>


        </div>
        <?php else: ?>
        <br />
        <?php endif; ?>

<?php

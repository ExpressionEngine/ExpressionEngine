    <div id="theme-modal" rev="theme-modal" class="modal-wrap modal-wrap--small hidden">
        <div class="modal modal--no-padding dialog dialog--warning">

            <div class="dialog__header">
                <div class="dialog__icon"><i class="fal fa-user-clock"></i></div>
                <h2 class="dialog__title"><?=lang('toggle_theme')?></h2>
            </div>

            <div class="dialog__body">
                <?php foreach (['light', 'dark', 'slate'] as $theme) : ?>
                    <p><a class="js-theme-toggle" href="" data-theme="<?=$theme?>"><?=lang($theme)?></a></p>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

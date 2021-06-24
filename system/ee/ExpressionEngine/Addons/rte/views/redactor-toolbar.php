<div id="rte-toolbar">
    <div class="form-standard">
        <div class="redactor-toolbar">
            <h2><?=lang('rte_toolbar_buttons')?></h2>
            <?php foreach ($buttons as $btnHandle => $btn) :?>
                <p>
                    <label><?=$btn['label']?></label>
                    <?=form_checkbox('settings[toolbar][buttons][]', $btnHandle, in_array($btnHandle, $settings['toolbar']['buttons']))?>
                </p>
            <?php endforeach;?>
        </div>
        
        <div class="redactor-toolbar">
            <h2><?=lang('rte_plugins')?></h2>
            <?php foreach ($plugins as $pluginHandle => $plugin) :?>
                <p>
                    <label>
                        <?=$plugin['label']?>
                        <small>
                            <?=$plugin['desc']?>
                        </small>
                    </label>
                    <?=form_checkbox('settings[toolbar][plugins][]', $pluginHandle, in_array($pluginHandle, $settings['toolbar']['plugins']))?>
                </p>
            <?php endforeach;?>
        </div>
    </div>
</div>
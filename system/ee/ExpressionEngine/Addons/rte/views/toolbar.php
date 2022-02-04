<div class="rte-toolbar" id="ckeditor-toolbar">
    <div id="tb-selections" class="cke cke_reset cke_chrome cke_ltr cke_browser_webkit">
        <div class="cke_inner cke_reset">
            <div class="cke_top cke_reset_all">
                <div class="cke_toolbox">
                    <?php foreach ($buttons as $button => $label): ?>
                        <?php
                            $lc_class = str_replace(':', '', strtolower($button));
                            $id = 'tb-option-' . $lc_class;
                            $file = PATH_THEMES . 'asset/img/rte-icons/' . $lc_class . '_icon.svg';
                        ?>
                        <span id="<?php echo $id ?>" class="cke_toolbar tb-option tb-selected">
                            <span class="cke_toolgroup">
                                <a class="cke_button cke_button_off cke_button__<?php echo $lc_class ?> <?php if (!in_array($button, $selection)) {
                                        echo 'disabled';
                                    } ?>" title="<?php echo htmlspecialchars($label) ?>">
                                <span class="cke_button_icon cke_button__<?php echo $lc_class ?>_icon" data-icon="<?php echo $lc_class ?>">
                                    <svg width="16" height="16"><?php echo file_get_contents($file); ?></svg>
                                    
                                </span>
                                <span class="cke_button_label cke_button__<?php echo $lc_class ?>_label"><?php echo $label ?></span>
                                <input type="hidden" name="settings[ckeditor_toolbar][]" <?php if (!in_array($button, $selection)) {
                                        echo 'disabled';
                                    } ?> value="<?php echo $button ?>">
                                </a>
                            </span>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

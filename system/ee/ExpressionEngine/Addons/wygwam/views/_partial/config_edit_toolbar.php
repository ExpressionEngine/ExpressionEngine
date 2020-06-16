<div id="tb-<?php echo $id ?>" class="cke cke_reset cke_chrome cke_ltr cke_browser_webkit">
<div class="cke_inner cke_reset">
<div class="cke_top cke_reset_all">
<div class="cke_toolbox">

    <?php foreach ($groups as $group): ?>
        <?php
            $first = $group[0];
            if (substr($first, 0, 1) == '!') {
                $first = substr($first, 1);
            }
            $id = 'tb-option-'.$first;
        ?>
        <?php if (array_intersect($group, $selected_groups)): ?>
            <span id="<?php echo $id ?>-placeholder" class="tb-placeholder"></span>
        <?php else: ?>
            <span id="<?php echo $id ?>" class="cke_toolbar tb-option <?php if ($selections_pane) {
            echo 'tb-selected';
        } ?>">
                <?php $is_combo = in_array($first, $variables['tbCombos']); ?>
                <?php if ($is_combo): ?>
                    <?php
                        $button = $group[0];
                        $lc_class = strtolower($button);
                        $label = isset($variables['tbLabelOverrides'][$button]) ? $variables['tbLabelOverrides'][$button] : $button;
                    ?>
                    <span class="cke_combo cke_combo__<?php echo $lc_class ?> cke_combo_off">
                        <span class="cke_combo_label"><?php echo $label ?></span>
                        <a class="cke_combo_button" title="<?php echo $label ?>">
                            <span class="cke_combo_text cke_combo_inlinelabel"><?php echo $label ?></span>
                            <span class="cke_combo_open"><span class="cke_combo_arrow"></span></span>
                        </a>
                    </span>
                    <input type="hidden" name="settings[toolbar][]" value="<?php echo $button ?>" <?php if (!$selections_pane) {
                        echo 'disabled';
                    } ?>>
                <?php else: ?>
                    <span class="cke_toolgroup">
                        <?php foreach ($group as $button): ?>
                            <?php
                                if ($disabled = substr($button, 0, 1) == '!') {
                                    $button = substr($button, 1);
                                }
                                $lc_class = strtolower($button);
                                $label = isset($variables['tbLabelOverrides'][$button]) ? $variables['tbLabelOverrides'][$button] : $button;
                            ?>
                            <a class="cke_button cke_button_off cke_button__<?php echo $lc_class ?> <?php if ($disabled) {
                                echo 'disabled';
                            } ?>" title="<?php echo $label ?>">
                                <span class="cke_button_icon cke_button__<?php echo $lc_class ?>_icon" data-icon="<?php echo $lc_class ?>">&nbsp;</span>
                                <span class="cke_button_label cke_button__<?php echo $lc_class ?>_label"><?php echo $label ?></span>
                                <input type="hidden" name="settings[toolbar][]" value="<?php echo $button ?>" <?php if (!$selections_pane || $disabled) {
                                echo 'disabled';
                            } ?>>
                            </a>
                        <?php endforeach ?>
                    </span>
                <?php endif ?>
            </span>
        <?php endif; ?>
    <?php endforeach; ?>

</div>
</div>
</div>
</div>

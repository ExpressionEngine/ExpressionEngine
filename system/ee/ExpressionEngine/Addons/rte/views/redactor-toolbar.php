<div class="rte-toolbar" id="redactor-toolbar-<?=$type?>">
    <div id="tb-selections-redactor-<?=$type?>">
        <div class="form-standard">
            <div class="redactor-toolbar cke_toolbox">
                <?php foreach ($buttons as $button => $label): ?>
                    <?php
                        $lc_class = str_replace(':', '', strtolower($button));
                        $id = 'tb-option-' . $lc_class;
                        switch ($lc_class) {
                            case 'fullscreen':
                                $icon_class = 'expand';
                                break;
                            case 'filemanager': 
                                $icon_class = 'file';
                                break;
                            case 'imagemanager': 
                                $icon_class = 'file';
                                break;
                            case 'inlinestyle':
                                $icon_class = 'inline';
                                break;
                            case 'specialchars':
                                $icon_class = 'specialcharacters';
                                break;
                            default:
                                $icon_class = $lc_class;
                                break;
                        }
                    ?>
                    <span id="<?php echo $id ?>" class="cke_toolbar tb-option tb-selected">
                        <span class="cke_toolgroup">
                            <a class="re-button re-button-icon re-<?php echo $lc_class ?> <?php if (!in_array($button, $selection)) { echo 'redactor-button-active';} ?>" title="<?php echo htmlspecialchars($label) ?>">
                                <i class="re-icon-<?php echo $icon_class ?>"></i>
                                <input type="hidden" name="settings[redactor_toolbar][<?=$type?>][]" <?php if (!in_array($button, $selection)) { echo 'disabled'; } ?> value="<?php echo $button ?>">
                            </a>
                        </span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

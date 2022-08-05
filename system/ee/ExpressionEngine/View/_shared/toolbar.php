<?php if (! empty($toolbar_items)): ?>
    <?php if (isset($toolbar_type) && $toolbar_type == 'dropdown'): ?>
        <div class="button-toolbar toolbar">
            <button type="button" class="js-dropdown-toggle button button--default button--xsmall" title="Actions">
                <i class="fal fa-ellipsis-h"></i>
            </button>

            <div class="dropdown">
                <div class="dropdown__scroll">

                <?php foreach ($toolbar_items as $type => $attributes):
                    if (isset($attributes['type'])) {
                        $type = $attributes['type'];
                    }
                    $class = $type;
                    $attr = '';
                    $content = '';
                    foreach ($attributes as $key => $val) {
                        if ($key == 'content') {
                            $content = $val;

                            continue;
                        }
                        if ($key == 'class') {
                            $class .= ' ' . $val;

                            continue;
                        }
                        $attr .= ' ' . $key . '="' . $val . '"';
                    }
                    if (isset($attributes['title'])) {
                        $content .= '<span class="hidden">' . $attributes['title'] . '</span>';
                    }
                    ?>
                    <a class="dropdown__link <?=$class?>" <?=$attr?>><?=$attributes['title']?><?=$content?></a>
                <?php endforeach ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="button-toolbar toolbar">
          <div class="button-group button-group-xsmall">
                <?php foreach ($toolbar_items as $type => $attributes):
                    if (isset($attributes['type'])) {
                        $type = $attributes['type'];
                    }
                    $class = $type;
                    $attr = '';
                    $content = '';
                    foreach ($attributes as $key => $val) {
                        if ($key == 'content') {
                            $content = $val;

                            continue;
                        }
                        if ($key == 'class') {
                            $class .= ' ' . $val;

                            continue;
                        }
                        $attr .= ' ' . $key . '="' . $val . '"';
                    }
                    if (isset($attributes['title'])) {
                        $content .= '<span class="hidden">' . $attributes['title'] . '</span>';
                    }
                    ?>
                    <a class="<?=$class?> button button--default" <?=$attr?>><?=$content?></a>
                <?php endforeach ?>
          </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

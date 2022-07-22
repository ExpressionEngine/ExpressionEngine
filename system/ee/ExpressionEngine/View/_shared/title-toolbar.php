<?php if (! empty($toolbar_items)): ?>
<div class="filters-toolbar title-bar__extra-tools">
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
        <a class="btn button button--primary <?=$class?>" <?=$attr?>><?=$content?></a>
    <?php endforeach ?>
</div>
<?php endif; ?>

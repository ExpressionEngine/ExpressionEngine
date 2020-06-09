<?php if ( ! empty($toolbar_items)): ?>
<div class="button-toolbar toolbar">
  <div class="button-group button-group-small">
    	<?php foreach ($toolbar_items as $type => $attributes):
    		if (isset($attributes['type']))
    		{
    			$type = $attributes['type'];
    		}
    		$attr = '';
    		$content = '';
    		foreach ($attributes as $key => $val)
    		{
    			if ($key == 'content')
    			{
    				$content = $val;
    				continue;
    			}
    			$attr .= ' ' . $key . '="' . $val . '"';
    		} ?>
    		<a class="<?=$type?> button button--default" <?=$attr?>><?=$content?></a>
    	<?php endforeach ?>
  </div>
</div>
<?php endif; ?>

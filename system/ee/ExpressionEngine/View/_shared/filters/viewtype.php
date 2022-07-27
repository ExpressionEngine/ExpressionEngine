<div class="filter__viewtype">
  <div class="button-group">
  	<?php foreach ($options as $option => $info): ?>
  	<?php switch ($option) {
  		case 'bigthumb':
  			$class = 'fa-th-large';
  			break;
  		case 'thumb':
  			$class = 'fa-th';
  			break;
  		case 'list':
  		default:
  			$class = 'fa-list';
  			break;
  	}
  	?>
  		<a class="filter-bar__button button button--default button--small<?=($value == $option) ? ' active' : ''?>" href="<?=$info['url']?>" title="<?=lang('view_as') . $info['label']?>">
  			<i class="fal <?=$class?>"></i>
  		</a>
  	<?php endforeach; ?>
  </div>
</div>

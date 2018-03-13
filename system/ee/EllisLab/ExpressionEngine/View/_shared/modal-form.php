<div class="app-modal app-modal--side<?=isset($full) && $full ? ' app-modal--full' : ''?><?=isset($iframe) && $iframe ? ' app-modal--iframe' : ''?>" rel="<?=$name?>">
	<div class="app-modal__content">
		<div class="app-modal__dismiss">
			<a class="js-modal-close" rel="<?=$name?>" href="#"><?=lang('close_modal')?></a> <span class="txt-fade">[esc]</span>
		</div>
		<div class="contents">
			<?=$contents?>
		</div>
	</div>
</div>

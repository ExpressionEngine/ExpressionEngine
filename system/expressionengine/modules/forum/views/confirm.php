<?=form_open($url, '', $hidden); ?>

<p class="notice"><?=lang($msg)?></p>
<p><?=$item?></p>

<p><?=form_submit('submit', lang('delete'), 'class="submit"')?></p>
<?=form_close(); ?>
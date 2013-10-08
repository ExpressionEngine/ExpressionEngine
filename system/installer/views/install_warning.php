<h2 class="important"><?=lang('install_detected')?></h2>

<div class="shade">

<p class="pad"><?=lang('install_detected_msg')?></p>

<p class="important"><?=lang('continuing_will_destroy')?></p>

<p class="pad"><?=lang('do_not_click_if_updating')?></p>

<p class="pad"><?=lang('click_if_sure')?></p>

<form action="<?=$action?>" method="post">
<input type="hidden" name="install_override" value="y" />
<?=$hidden_fields?>

<p class="pad"><input type="submit" value=" <?=lang('yes_install_ee')?> "></p>

</form>

</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="translate" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=tools"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
<?php $this->load->view('_shared/right_nav')?>

<?php $this->load->view('_shared/message');?>
<?php if ( ! $language_list): ?>
	<p class="notice pad"><?=lang('no_lang_keys')?></p>
<?php else: ?>	
<?=form_open('C=tools_utilities'.AMP.'M=translation_save', '', $form_hidden)?>

<?php

foreach ($language_list as $label => $value): ?>

	<div class="label">
		<?=form_label($value['original'], $label)?>
	</div>
	<ul>
		<li><?=form_input(array('id' => $label,
			 'name' => $label,
			 'value' => $value['trans']))?></li>
	</ul>
<?php endforeach; ?>

<?=form_submit('translate', lang('update'), 'class="whiteButton"')?>

<?=form_close()?>
<?php endif; ?>
</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_email_log.php */
/* Location: ./themes/cp_themes/mobile/tools/translate.php */
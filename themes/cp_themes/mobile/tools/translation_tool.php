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

<?php $this->load->view('_shared/message');?>

<?php if (isset($not_writeable)):?>
	<p class="notice"><?=$not_writeable?></p>
<?php endif; ?>
<p><?=lang('choose_translation_file')?></p>

<ul>
<?php foreach($language_files as $file):?>

	<li><a href="<?=BASE.AMP.'C=tools_utilities'.AMP.'M=translate'.AMP.'language_file='.$file?>"><?=$file?></a></li>

<?php endforeach;?>
</ul>

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_email_log.php */
/* Location: ./themes/cp_themes/mobile/tools/translation_toolz.php */
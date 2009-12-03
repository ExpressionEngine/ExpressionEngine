<?php
if ($EE_view_disable !== TRUE)
{
    $this->load->view('_shared/header');
}
?>
<div id="forms">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=homepage#tools" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>

	<h3 class="pad"><?=$subject?></h3>

	<div class="container pad">
		<?=$message?>
	</div>


</div>  
<?php $this->load->view('_shared/footer');?>

<?php
/* End of file view_email.php */
/* Location: ./themes/cp_themes/mobile/tools/view_email.php */	
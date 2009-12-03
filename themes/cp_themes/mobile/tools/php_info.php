<?php
if ($EE_view_disable !== TRUE)
{
    $this->load->view('_shared/header');
} 
?>
<div id="phpinfo" selected="true">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=tools"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
<label><?=$cp_page_title?></label>
<?=$php_info?>

</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End File:  php_info.php */
/* Location:  ./themes/cp_themes/mobile/tools/php_info.php */
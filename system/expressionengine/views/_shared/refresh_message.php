<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta http-equiv='refresh' content='<?=$refresh_rate?>; url=<?=$redirect_url?>'>
	
	<title><?=$cp_page_title?> | ExpressionEngine</title>

	<link rel="stylesheet" href="<?=BASE.AMP.'C=css'?>" type="text/css" media="screen" title="Global Styles" charset="utf-8" />
    
	<link rel="stylesheet" href="<?=BASE.AMP.'C=css'.AMP.'M=datepicker'?>" type="text/css" media="screen" />
	<!--[if lte IE 7]>
	<link rel="stylesheet" href="<?=BASE.AMP.'C=css'.AMP.'M=iefix'?>" type="text/css" media="screen" charset="utf-8" />
	<![endif]-->

	<?php 
    if (isset($library_src))
    {
        echo $library_src;
    }

    if (isset($script_head))
    {
        echo $script_head;
    }

	foreach ($this->cp->its_all_in_your_head as $item)
	{
		echo $item."\n";
	}
    ?>

	<script type="text/javascript" src="<?=BASE.AMP.'C=javascript'?>"></script>

</head>

<body onload="<?=$cp_page_onload?>">

<div id="branding"><a href="http://expressionengine.com/"><img src="<?=PATH_CP_GBL_IMG?>ee_logo_branding.gif" width="250" height="28" alt="<?=lang('powered_by')?> ExpressionEngine" /></a></div>

<?php
if ($EE_view_disable !== TRUE)
{
	// custom header on this page
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">
		<div class="heading"><h2 class="edit"><?=$refresh_heading?></h2></div>
		<div class="pageContents">
			
			<?php if (isset($refresh_notice)): ?>
			<p class="notice"><?=$refresh_notice?></p>
			<?php endif; ?>
			
			<p><?=$refresh_message?></p>
		</div>
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	// no accessories on this page
	$this->load->view('_shared/footer');
}

/* End of file refresh_message.php */
/* Location: ./themes/cp_themes/default/_shared/refresh_message.php */
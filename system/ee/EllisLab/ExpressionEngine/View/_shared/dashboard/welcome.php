<style type="text/css">

</style>

<div class="dashboard__item dashboard__item--full color: var(--ee-text-normal);" id="welcome-screen" style="display: none">
	<div class="widget__title-bar">
		

		
	</div>

	<div style="margin: auto; text-align: center; width:225px">
		<img src="/themes/ee/asset/img/expressionengine-logo.png">
	</div>
	<div style="margin: auto; text-align: left; width:600px; padding-top: 50px">
	<h3 style="text-align: center;">Welcome to EE 6 Alpha</h3>
	<p style="padding-top: 25px;">We want to take this opportunity to introduce you to what we believe is one of the largest innovations added to ExpressionEngine in the last 5 years.</p>
	<h5 style="padding-top: 25px;">The Jump Menu</h5>
	<p style="padding-top: 25px;">Q. What is it?
		<br>
		A. It's kind of like Alphred for a CMS, and the fastest way to use the control panel -- by a long shot.
		<br>
		<br>
		<br>
		Q. How powerful is it?
		<br>
		A. Powerful enough that we created a navless control panel option.
		<br>
		<br>
		<br>
		<h1 style="text-align: center;">⌘ + J</h1>
	</p>

	<?=form_open(ee('CP/URL')->make('homepage/set-viewmode'))?>
	<div class="button-group" style="padding-top: 35px;">
		<?=form_button(['name' => 'ee_cp_viewmode', 'value' => 'jumpmenu', 'type' => 'submit'], "Proceed with Jump Menu only", 'class="btn" data-submit-text="Proceed with Jump Menu only" data-work-text="Please wait..."')?>
		<?=form_button(['name' => 'ee_cp_viewmode', 'value' => 'classic', 'type' => 'submit'], "Proceed with Jump Menu and Main Navigation", 'class="btn" data-submit-text="Proceed with Jump Menu and Main Navigation" data-work-text="Please wait..."')?>
	</div>
	<?=form_close()?>

</div>


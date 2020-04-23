<style type="text/css">
.ee-main--dashboard {
	background: var(--ee-bg-0);
}
</style>

<div class="dashboard__item dashboard__item--full widget" id="welcome-screen" style="display: none">
	<div class="widget__title-bar">
		<h2 class="widget__title">Welcome to new ExpressionEngine</h2>

		<div>
			<a class="button button--secondary-alt js-jump-menu-trigger" href="#">Show Jump Menu</a>
			<a class="button button--secondary-alt show-sidebar" href="#">Show Main Navigation</a>
		</div>
	</div>

	<div style="margin: auto; text-align: center">
	<img src="/themes/ee/asset/img/expressionengine-logo.png">
	</div>

	<p>Welcome Alpha testers!</p>
	<p>ExpressionEngine stores your content inside <em>Channels</em>. A channel is simply a container that holds information. This information might be the text of an article or blog post, or it could be an image, or some other type of information. Channel information exists independently from the pages your visitors see.</p><p class="alert alert--hint"> <strong>Important Concept:</strong> A channel is just data. There is no assumed association between this data and any particular page of your site.</p><p>In your control panel, Channels are managed and configured in the <a href="../control-panel/channels.html">Channel Manager</a>. ExpressionEngine supports a wide variety of <a href="../fieldtypes/overview.html">Field Types</a>, so that the information in each of your channels can be stored appropriately for that data type.</p><p>For example, you might need a “staff” page with employee biographies. To manage this you could create a channel with fields for the name, biography, an image, and any other relevant info.</p><p>Content is added to a channel by clicking the <strong>Edit</strong> button in the main control panel navigation.</p>
	<p>In ExpressionEngine, a page (or a page component such as a header or footer) is called a <em>Template</em>.</p><p>The simplest way to think of a Template is as a container that represents a single page of your site. As such, a Template may contain anything that a webpage might contain: HTML, JavaScript, etc. A Template can also be a smaller component of your page. Through the use of the <a href="../templates/embedding.html">Embed Tag</a> you can insert a Template into another Template. This allows you to reuse components such as headers or footers.</p><p>In addition to HTML and other markup, Templates will usually contain ExpressionEngine Tags. These Tags allow you to pull data from your channels (or from any other module, plugin, or add-on) and display it in a template.</p><p>Templates are organized into Template Groups. A Template Group is analogous to a folder on your server.</p>
	<ul>
	<li>A Channel consists of “information”–your articles, comments, preferences, and other related “data.”</li>
	<li>A Template represents a single page or a smaller section of your site.</li>
	<li>A Template Group contains a collection of Templates.</li>
	<li>ExpressionEngine Tags permit you to show data from a Channel, or any other Module or add-on, in your Templates.</li>
	</ul>
	<p class="alert alert--hint"> <strong>Tip:</strong> Check out our <a href="../getting-started/ten-minute-primer.html">10 Minute Primer</a> to get you started fast.</p>
	<?=form_open(ee('CP/URL')->make('homepage/set-viewmode'))?>
	<div class="button-group">
		<?=form_button(['name' => 'ee_cp_viewmode', 'value' => 'jumpmenu', 'type' => 'submit'], "Proceed with Jump Menu only", 'class="btn" data-submit-text="Proceed with Jump Menu only" data-work-text="Please wait..."')?>
		<?=form_button(['name' => 'ee_cp_viewmode', 'value' => 'classic', 'type' => 'submit'], "Proceed with Jump Menu and Main Navigation", 'class="btn" data-submit-text="Proceed with Jump Menu and Main Navigation" data-work-text="Please wait..."')?>
	</div>
	<?=form_close()?>

</div>


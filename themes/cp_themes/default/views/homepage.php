<?php extend_template('wrapper'); ?>

<div class="home-layout">
	<div class="col-group snap mb">
		<div class="col w-16 last">
			<div class="box full">
				<form class="tbl-ctrls">
					<fieldset class="tbl-search right">
						<input placeholder="type phrase..." type="text" value="">
						<input class="btn submit" type="submit" value="search content">
					</fieldset>
					<h1>
						<?=ee()->config->item('site_name')?> Overview
						<ul class="toolbar">
							<li class="solo settings"><a href="http://localhost/el-projects/ee-cp/views/settings-general.php" title="Settings"></a></li>
						</ul>
					</h1>
				</form>
			</div>
		</div>
	</div>
	<div class="col-group snap mb">
		<div class="col w-16 last">
			<div class="box">
				<h1>Comments <a class="btn action" href="http://localhost/el-projects/ee-cp/views/publish-comments.php">Review All New</a></h1>
				<div class="info">
					<p>There were <b>27</b> <a href="http://localhost/el-projects/ee-cp/views/publish-comments.php"><em>new</em> comments</a> since your last login (March, 19th 2014)</p>
					<p class="last"><b><?=$comment_validation_count?></b> are <a href="http://localhost/el-projects/ee-cp/views/publish-comments-pending.php">awaiting moderation</a>, and <b>7</b> have been <a href="http://localhost/el-projects/ee-cp/views/publish-comments-spam.php">flagged as potential spam</a>.</p>
				</div>
			</div>
		</div>
	</div>
	<div class="col-group snap mb">
		<div class="col w-8">
			<div class="box">
				<h1>Channels <a class="btn action" href="http://localhost/el-projects/ee-cp/views/channel-new.php">Create New</a></h1>
				<div class="info">
					<p>Channels are used to store content for your website. For example, if you want a Blog. You would first need to create a Channel to store the entries. Think of them as folders, or directories.</p>
					<h2><?=ee()->config->item('site_name')?> has:</h2>
					<ul class="arrow-list">
						<li><a href="http://localhost/el-projects/ee-cp/views/channel.php"><b>15</b> Channels</a></li>
						<li><a href="http://localhost/el-projects/ee-cp/views/channel-field.php"><b>200</b> Channel Fields</a></li>
					</ul>
				</div>
			</div>
		</div>
		<div class="col w-8 last">
			<div class="box">
				<h1>Members <a class="btn action" href="http://localhost/el-projects/ee-cp/views/members-new.php">Register New</a></h1>
				<div class="info">
					<p>Members are registered users of your site. You <em>must</em> have at least one member, an administrator. You may <a href="http://localhost/el-projects/ee-cp/views/settings-members.php">change the settings</a> to allow or disallow, new member registrations.</p>
					<h2><?=ee()->config->item('site_name')?> has:</h2>
					<ul class="arrow-list">
						<li><a href="http://localhost/el-projects/ee-cp/views/members.php"><b>10</b> Members</a></li>
						<li><a href="http://localhost/el-projects/ee-cp/views/members-ban.php"><b>5</b> Banned Members</a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div class="col-group snap">
		<div class="col w-16 last">
			<div class="box">
				<h1>Content <a class="btn action" href="http://localhost/el-projects/ee-cp/views/publish.php">Create New</a></h1>
				<div class="info">
					<p>Entries in channels, this is what folks visit to experience.</p>
					<h2><?=ee()->config->item('site_name')?> has:</h2>
					<ul class="arrow-list">
						<li><a href="http://localhost/el-projects/ee-cp/views/publish-edit.php"><b>7589</b> Entries with 20,000 comments</a></li>
						<li><a href="http://localhost/el-projects/ee-cp/views/publish-edit-closed.php"><b>10</b> Closed entries with 213 comments.</a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>

<?php

/* End of file homepage.php */
/* Location: ./themes/cp_themes/default/homepage.php */
<?php $page_title = 'Kirk\'s Phasers';
include(dirname(__FILE__) . '/_wrapper-head.php'); ?>

<script>
	document.querySelector('.ee-main').classList.add('ee-main--dashboard')
</script>

<div class="dashboard">

  <a href="" class="dashboard__item dashboard__item--full beta-welcome-banner beta-fade-in">
    <img src="../app/assets/images/beta-starburst.svg" class="beta-starburst" alt="v6">
    <div class="v6-wrapper">
      <img src="../app/assets/images/ee-6.svg" class="v6 beta-puff-in-center" alt="v6">
    </div>
    <div class="beta-copy">
      <img src="../app/assets/images/ee-logotype-white.svg" class="logotype beta-slide-in-top" alt="ExpressionEngine">
      <span class="beta-intro beta-slide-in-bottom">Welcome to the beta!</span>
    </div>
    <i class="fal fa-times beta-banner-close"></i>
  </a>

  <div class="dashboard__item dashboard__item--full widget widget--chart">
		<div class="widget__title-bar">
			<h2 class="widget__title">Analytics</h2>

			<div class="button-group button-group-small">
				<a href="admin.php?/cp/publish/edit" class="button button--default">Visitors <i class="fal fa-sm fa-chevron-down"></i></a>
				<a href="admin.php?/cp/publish/edit" class="button button--default"><i class="fal fa-calendar-alt"></i></a>
			</div>
		</div>

		<img src="../app/assets/images/fake-chart.png" alt="">
	</div>

	<div class="dashboard__item">
		<div class="widget">
			<div class="widget__title-bar">
				<h2 class="widget__title">Recent Entries</h2>

				<div>
					<a href="admin.php?/cp/publish/edit" class="button button--default button--small">View All</a>
				</div>
			</div>

			<ul class="simple-list">
				<li><a class="normal-link" href="admin.php?/cp/publish/edit/entry/7">The Art of Brazilian Jiu Jitsu <span class="meta-info float-right">03/11/19</span></a></li>
				<li><a class="normal-link" href="admin.php?/cp/publish/edit/entry/8">Intro to Drones <span class="meta-info float-right">01/11/19</span></a></li>
				<li><a class="normal-link" href="admin.php?/cp/publish/edit/entry/9">Flying Drones <span class="meta-info float-right">30/10/19</span></a></li>
				<li><a class="normal-link" href="admin.php?/cp/publish/edit/entry/10">How to Recover a Lost Drone <span class="meta-info float-right">29/10/19</span></a></li>
				<li><a class="normal-link" href="admin.php?/cp/publish/edit/entry/11">Music History - Definitive, Refined <span class="meta-info float-right">28/10/19</span></a></li>
				<li><a class="normal-link" href="admin.php?/cp/publish/edit/entry/12">A Day in New York <span class="meta-info float-right">18/10/19</span></a></li>
				<li><a class="normal-link" href="admin.php?/cp/publish/edit/entry/6">Javascript - A Beginners Guide <span class="meta-info float-right">02/10/19</span></a></li>

				<li><a class="normal-link" href="admin.php?/cp/publish/edit/entry/6">Another article about something<span class="meta-info float-right">02/10/19</span></a></li>

			</ul>
		</div>
	</div>

	<div class="dashboard__item">
		<div class="widget">
			<div class="widget__title-bar">
				<h2 class="widget__title">Members</h2>

				<div class="">
					<div class="button-group">
						<a class="button button--default button--small" href="admin.php?/cp/members/create">Register New</a>
						<!-- <a class="button button--secondary-alt" href="admin.php?/cp/members/create">View All</a> -->
					</div>
				</div>
			</div>

			<!-- <div class="list-group">
				<div class="list-item list-item--action">
					<a href="" class="list-item__content">
						<b>208</b> Members
						<i class="fal fa-chevron-right float-right" style="margin-top: 2px;"></i>
					</a>
				</div>
				<div class="list-item list-item--action">
					<a href="" class="list-item__content">
						<b>4</b> Banned Members
						<i class="fal fa-chevron-right float-right" style="margin-top: 2px;"></i>
					</a>
				</div>
			</div> -->

			<!-- <p>Recently logged in</p> -->
			<ul class="simple-list">
				<li>
					<a href="admin.php?/cp/members" class="d-flex align-items-center normal-link">
						<img src="../app/assets/images/profile-icon.png" class="avatar-icon add-mrg-right" alt="">
						<div class="flex-grow">John Doe <span class="meta-info float-right">Yesterday</span></div>
					</a>
				</li>
				<li>
					<a href="admin.php?/cp/members" class="d-flex align-items-center normal-link">
						<img src="../app/assets/images/profile-icon.png" class="avatar-icon add-mrg-right" alt="">
						<div class="flex-grow">Tim Toe <span class="meta-info float-right">Yesterday</span></div>
					</a>
				</li>
				<li>
					<a href="admin.php?/cp/members" class="d-flex align-items-center normal-link">
						<img src="../app/assets/images/profile-icon.png" class="avatar-icon add-mrg-right" alt="">
						<div class="flex-grow">Shmo Joe <span class="meta-info float-right">2 Days Ago</span></div>
					</a>
				</li>
				<li>
					<a href="admin.php?/cp/members" class="d-flex align-items-center normal-link">
						<img src="../app/assets/images/profile-icon.png" class="avatar-icon add-mrg-right" alt="">
						<div class="flex-grow">Marry Go <span class="meta-info float-right">8 Days Ago</span></div>
					</a>
				</li>
				<li>
					<a href="admin.php?/cp/members" class="d-flex align-items-center normal-link">
						<img src="../app/assets/images/profile-icon.png" class="avatar-icon add-mrg-right" alt="">
						<div class="flex-grow">Perry <span class="meta-info float-right">8 Days Ago</span></div>
					</a>
				</li>
			</ul>

			<div class="widget__bottom-buttons">
				<a href="" class="button">View All</a>
			</div>
		</div>
	</div>




	<div class="dashboard__item ">
		<div class="widget">
			<div class="widget__title-bar">
				<h2 class="widget__title">Comments</h2>

				<div class="">
					<div class="button-group">
						<a class="button button--default button--small" href="admin.php?/cp/publish/comments"><b>10</b> New Comments</a>
						<!-- <a class="button button--secondary-alt" href="admin.php?/cp/publish/comments">Spam</a> -->

					</div>
				</div>
			</div>

			<!-- <p class="">There are <b>10</b> new comments</p> -->
			<!--
			<div class="list-item list-item--action">
				<a href="" class="list-item__content">
					<b>4</b> comments awaiting moderation
					<i class="fal fa-chevron-right float-right" style="margin-top: 2px;"></i>
				</a>
			</div> -->

			<ul class="simple-list">
				<li>
					<div class="d-flex">
						<div style="margin-right: 10px;">
							<img src="../app/assets/images/profile-icon.png" class="avatar-icon float-left" alt="">
						</div>
						<div>
							<p class="meta-info">
								<a href="admin.php?/cp/members">John Doe</a>
								commented on <a href="">Some Article</a>
							</p>

							<p>In homero accumsan ius, cum saepe pertinacia maiestatis te. Dico fuisset et eam, ubique cotidieque sed id.</p>

						</div>
					</div>
				</li>
				<li>
					<div class="d-flex">
						<div style="margin-right: 10px;">
							<img src="../app/assets/images/profile-icon.png" class="avatar-icon float-left" alt="">
						</div>
						<div>
							<p class="meta-info">
								<a href="admin.php?/cp/members">Wilson</a>
								commented on <a href="">Another Article</a>
							</p>

							<p>In homero accumsan ius, cum saepe pertinacia maiestatis te. Dico fuisset et eam, ubique cotidieque sed id.</p>

						</div>
					</div>
				</li>
				<li>
					<div class="d-flex">
						<div style="margin-right: 10px;">
							<img src="../app/assets/images/profile-icon.png" class="avatar-icon float-left" alt="">
						</div>
						<div>
							<p class="meta-info">
								<a href="admin.php?/cp/members">Timmy, just Timmy</a>
								commented on <a href="">Some Article</a>
							</p>

							<p>In homero accumsan ius, cum saepe pertinacia maiestatis te. Dico fuisset et eam, ubique cotidieque sed id.</p>

						</div>
					</div>
				</li>
			</ul>


			<div class="widget__bottom-buttons">
				<a href="" class="button"><b>6</b> awaiting moderation</a>
				<a href="" class="button"><b>3</b> have been flagged as spam</a>
			</div>
		</div>
	</div>




	<div class="dashboard__item dashboard__item--half">
		<div class="widget">
			<div class="widget__title-bar">
				<h2 class="widget__title">ExpressionEngine News</h2>

				<div class="">
					<a class="button button--default button--small" href="http://alpha1.local/new-cp/index.php?URL=https%3A%2F%2Fexpressionengine.com%2Fblog%2Frss-feed%2Fcpnews%2F" rel="external">RSS</a>
				</div>
			</div>

			<ul class="simple-list">
				<li>
					<a class="normal-link" href="http://alpha1.local/new-cp/index.php?URL=https%3A%2F%2Fexpressionengine.com%2Fblog%2Fee-conf-early-bird-tickets-almost-gone" rel="external">EE CONF Early Bird Tickets Almost Gone! <span class="meta-info float-right">5th August, 2019</span></a>
				</li>
				<li>
					<a class="normal-link" href="http://alpha1.local/new-cp/index.php?URL=https%3A%2F%2Fexpressionengine.com%2Fblog%2Fadd-on-store-has-launched" rel="external">The Add-on Store has Launched! <span class="meta-info float-right">1st May, 2019</span></a>
				</li>
				<li>
					<a class="normal-link" href="http://alpha1.local/new-cp/index.php?URL=https%3A%2F%2Fexpressionengine.com%2Fblog%2Fannouncing-expressionengine-university" rel="external">Announcing ExpressionEngine University! <span class="meta-info float-right">1st March, 2019</span></a>
				</li>
				<li>
					<a class="normal-link" href="http://alpha1.local/new-cp/index.php?URL=https%3A%2F%2Fexpressionengine.com%2Fblog%2Fthe-brand-new-expressionengine-user-guide" rel="external">The Brand New ExpressionEngine User Guide <span class="meta-info float-right">6th February, 2019</span></a>
				</li>
				<li>
					<a class="normal-link" href="http://alpha1.local/new-cp/index.php?URL=https%3A%2F%2Fexpressionengine.com%2Fblog%2F5.1-brings-drag-drop-and-bulk-uploads" rel="external">5.1 brings Drag, Drop, and Bulk Uploads <span class="meta-info float-right">22nd January, 2019</span></a>
				</li>
				<li>
					<a class="normal-link" href="http://alpha1.local/new-cp/index.php?URL=https%3A%2F%2Fexpressionengine.com%2Fblog%2Fmake-upgrades-easy-with-these-simple-tips" rel="external">Make upgrades easy with these simple tips <span class="meta-info float-right">13th December, 2018</span></a>
				</li>
				<li>
					<a class="normal-link" href="http://alpha1.local/new-cp/index.php?URL=https%3A%2F%2Fexpressionengine.com%2Fblog%2Fellislab-acquired-by-digital-locations" rel="external">EllisLab Acquired by Digital Locations <span class="meta-info float-right">4th December, 2018</span></a>
				</li>
				<li>
					<a class="normal-link" href="http://alpha1.local/new-cp/index.php?URL=https%3A%2F%2Fexpressionengine.com%2Fblog%2Fwhy-the-apache-open-source-license" rel="external">Why the Apache Open-Source License? <span class="meta-info float-right">28th November, 2018</span></a>
				</li>
			</ul>
		</div>
	</div>

	<div class="dashboard__item dashboard__item--half">
		<div class="widget">
			<div class="widget__title-bar">
				<h2 class="widget__title">Spam</h2>

				<div class="">
					<!-- <a class="button button--secondary-alt" href="admin.php?/cp/addons/settings/spam">Review All</a> -->
				</div>
			</div>

			<!-- <p class="add-mrg-bottom">There are <b>0</b>
				<em>new</em> items in the Spam queue
				since your last login (11/1/2019 12:01 AM)
			</p>
			 -->
			<div class="list-group">
				<!-- <div class="list-item list-item--action">
				<a href="" class="list-item__content">

				</a>
			</div> -->
				<div class="list-item list-item--action">
					<a href="" class="list-item__content">
						There are <b>2</b> items are in the spam queue
						<i class="fal fa-chevron-right float-right" style="margin-top: 2px;"></i>
					</a>
				</div>
			</div>

			<ul class="list">
			</ul>
		</div>
	</div>


	<div class="dashboard__item dashboard__item--half">
		<div class="widget widget--support">
			<div class="widget__title-bar">
				<h2 class="widget__title">ExpressionEngine Support</h2>
			</div>

			<p>Get Direct, fast, unlimited support from the same team that builds your favorite CMS.</p>

			<p><a href="" class="button button--default">Learn More</a></p>
		</div>
	</div>
</div>

<?php include(dirname(__FILE__) . '/_wrapper-footer.php'); ?>

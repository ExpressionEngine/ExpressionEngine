<?php $page_title = 'Members'; $page_toolbar = '<a class="button button--action">New Member</a>'; include(dirname(__FILE__) . '/_wrapper-head.php'); ?>


<div class="secondary-sidebar-container">

	<div class="secondary-sidebar">
		<nav class="sidebar">
			<h2 class="sidebar__section-title">Members</h2>
			<a href="" class="sidebar__link active">All Members</a>
			<a href="" class="sidebar__link">Pending Activation</a>

			<div class="sidebar__section-title">Member Settings</div>
			<a href="" class="sidebar__link"><i class="fal fa-ban fa-fw"></i> Banned Settings</a>
			<a href="" class="sidebar__link"><i class="fal fa-user-tag fa-fw"></i> Roles</a>
			<a href="" class="sidebar__link"><i class="fal fa-bars fa-fw"></i> Fields</a>
		</nav>
	</div>

	<div class="container">

		<div class="tbl-wrap">

			<table cellspacing="0">
				<thead>
					<tr>
						<th class="id-col highlight">
							ID <a href="admin.php?/cp/members&amp;perpage=25&amp;search=&amp;sort_col=member_id&amp;sort_dir=asc" class="sort desc"></a>
						</th>
						<th>
							Username <a href="admin.php?/cp/members&amp;perpage=25&amp;search=&amp;sort_col=username&amp;sort_dir=asc" class="sort desc"></a>
						</th>
						<th>
							Dates <a href="admin.php?/cp/members&amp;perpage=25&amp;search=&amp;sort_col=dates&amp;sort_dir=asc" class="sort desc"></a>
						</th>
						<th>
							Member Group <a href="admin.php?/cp/members&amp;perpage=25&amp;search=&amp;sort_col=member_group&amp;sort_dir=asc" class="sort desc"></a>
						</th>
						<th>
							Manage </th>
						<th class="check-ctrl">
							<input type="checkbox" title="select all">
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>1</td>
						<td><a href="admin.php?/cp/members/profile&amp;id=1">jordan</a><br><span class="meta-info">— <a href="admin.php?/cp/utilities/communicate/member/1">jordan.ellis@ellislab.com</a></span></td>
						<td><span class="meta-info">
								<b>Joined</b>: 7/2/2019<br>
								<b>Last Visit</b>: 7/12/2019 10:57 PM
							</span></td>
						<td>Super Admin</td>
						<td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="edit"><a href="admin.php?/cp/members/profile&amp;id=1" title="profile"></a></li>
								</ul>
							</div>
						</td>
						<td>
							<!-- <div class="wrapper">
								<input name="selection[]" type="checkbox" value="no-issues" checked>
								<label for="selection[]"></label>
							</div> -->
							<input name="selection[]" value="1" data-confirm="Member: <b>jordan</b>" type="checkbox">
						</td>
					</tr>
				</tbody>
			</table>

		</div>
	</div>
</div>

<?php include(dirname(__FILE__) . '/_wrapper-footer.php'); ?>

<?php $page_title = "Entries";
$page_toolbar = '<a class="button button--action">New Entry</a>';
include(dirname(__FILE__) . '/_wrapper-head.php'); ?>

<div class="panel">
  <div class="tbl-ctrls">
    <div class="panel-heading">
      <div class="title-bar">
      	<h3 class="title-bar__title">Entries</h3>


      </div>
    </div>

    <!-- 'filter-search-bar' is placed after 'panel-heading' -->
    <div class="filter-search-bar">

      <!-- All filters (not including search input) are contained within 'filter-search-bar__filter-row' -->
      <div class="filter-search-bar__filter-row">

        <!-- Each filter is wrapped in 'filter-search-bar__item' -->
        <!-- Add 'in-use' class if the filter is currently being applied -->
        <div class="filter-search-bar__item in-use">
		  <button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="Channel" title="Channel (Channel Name)">Channel <span class="faded">(Channel Name)</span></button>
		  <a class="filter-clear" href="#"><i class="fal fa-times"></i></a>
          <div class="dropdown">
          	<div class="dropdown__scroll">
        			<a class="dropdown__link" href="">Channel Name</a>
        			<a class="dropdown__link" href="">Channel Name</a>
        			<a class="dropdown__link" href="">Channel Name</a>
        		</div>
          </div>
        </div>

        <div class="filter-search-bar__item in-use">
          <button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="Category" title="Category (Category Name)">Category <span class="faded">(Category Name)</span></button>
          <div class="dropdown">
          	<div class="dropdown__scroll">
        			<a class="dropdown__link" href="">Category Name</a>
        			<a class="dropdown__link" href="">Category Name</a>
        			<a class="dropdown__link" href="">Category Name</a>
        		</div>
          </div>
        </div>

        <div class="filter-search-bar__item in-use">
          <button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="Status" title="Status (Status Name)">Status <span class="faded">(Status Name)</span></button>
          <div class="dropdown">
          	<div class="dropdown__scroll">
        			<a class="dropdown__link" href="">Status Name</a>
        			<a class="dropdown__link" href="">Status Name</a>
        			<a class="dropdown__link" href="">Status Name</a>
        		</div>
          </div>
        </div>

        <div class="filter-search-bar__item in-use">
          <button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="Date" title="Date (Last 7 Days)">Date <span class="faded">(Last 7 Days)</span></button>
          <div class="dropdown">
          	<div class="dropdown__scroll">
        			<a class="dropdown__link" href="">Date Option</a>
        			<a class="dropdown__link" href="">Date Option</a>
        			<a class="dropdown__link" href="">Date Option</a>
        		</div>
          </div>
        </div>

        <div class="filter-search-bar__item in-use">
          <button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="Author" title="Author (Author Name)">Author <span class="faded">(Author Name)</span></button>
          <div class="dropdown">
          	<div class="dropdown__scroll">
        			<a class="dropdown__link" href="">Author Name</a>
        			<a class="dropdown__link" href="">Author Name</a>
        			<a class="dropdown__link" href="">Author Name</a>
        		</div>
          </div>
        </div>

      </div>

      <!-- The search input and non-filter controls are contained within 'filter-search-bar__search-row' -->
      <div class="filter-search-bar__search-row">

        <div class="filter-search-bar__item">
          <div class="field-control input-group input-group-sm with-icon-start with-icon-end">
            <input class="search-input__input input--small input-clear" type="text" name="filter_by_keyword" value="" placeholder="Search..." autofocus="autofocus">
            <i class="fal fa-search icon-start icon--small"></i>
            <span class="input-group-addon">
              <label class="checkbox-label">
                <input type="checkbox" class="checkbox--small" value="">
                <div class="checkbox-label__text">
                  <div>Search Titles Only</div>
                </div>
              </label>
            </span>
          </div>
        </div>

        <div class="filter-search-bar__item">
          <button type="button" class="filter-bar__button js-dropdown-toggle button button--default button--small dropdown-open open" title="Columns"><i class="fal fa-columns"></i></button>
          <div class="dropdown dropdown__scroll ui-sortable" rev="toggle-columns" x-placement="bottom-end">
          	<div class="dropdown__header">Columns</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="title" style="top: 3px; margin-right: 5px;"> Title</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="entry_date" style="top: 3px; margin-right: 5px;"> Date</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="status" style="top: 3px; margin-right: 5px;"> Status</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="author" style="top: 3px; margin-right: 5px;"> Author</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="entry_id" style="top: 3px; margin-right: 5px;"> ID#</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="channel" style="top: 3px; margin-right: 5px;"> Channel</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="expiration_date" style="top: 3px; margin-right: 5px;"> Expiration date</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" checked="" class="checkbox checkbox--small" name="columns[]" value="comments" style="top: 3px; margin-right: 5px;"> Comments</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="url_title" style="top: 3px; margin-right: 5px;"> URL title</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="categories" style="top: 3px; margin-right: 5px;"> Categories</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_1" style="top: 3px; margin-right: 5px;"> Text Input Field</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_2" style="top: 3px; margin-right: 5px;"> Checkboxes</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_4" style="top: 3px; margin-right: 5px;"> Date Field</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_5" style="top: 3px; margin-right: 5px;"> Duration</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_6" style="top: 3px; margin-right: 5px;"> Email Address</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_7" style="top: 3px; margin-right: 5px;"> File Field</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_12" style="top: 3px; margin-right: 5px;"> Radio Buttons</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_13" style="top: 3px; margin-right: 5px;"> Relationships</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_16" style="top: 3px; margin-right: 5px;"> Textarea</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_17" style="top: 3px; margin-right: 5px;"> Toggle</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_18" style="top: 3px; margin-right: 5px;"> URL Field</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_20" style="top: 3px; margin-right: 5px;"> Text Input 2</label></a>
          	</div>
          	<div class="dropdown__item">
          		<a class="dropdown-reorder ui-sortable-handle"><label><input type="checkbox" class="checkbox checkbox--small" name="columns[]" value="field_id_21" style="top: 3px; margin-right: 5px;"> Rich Text Editor</label></a>
          	</div>
          </div>
        </div>

      </div>

    </div>


<!-- <div class="filter-bar-tabs">
	<div class="filter-bar-tab left open">
		<div class="filter-bar">
			<div class="filter-bar__item">
				<div class="filter-bar__button has-sub">Channel</div>
			</div>
			<div class="filter-bar__item">
				<div class="filter-bar__button has-sub">Category</div>
			</div>
			<div class="filter-bar__item">
				<div class="filter-bar__button has-sub">Status</div>
			</div>
			<div class="filter-bar__item">
				<div class="filter-bar__button has-sub">Date</div>
			</div>
			<div class="filter-bar__item">
				<div class="filter-bar__button has-sub">Author</div>
			</div>
		</div>
	</div>
	<div class="filter-bar-tab">

	</div>
</div> -->

<div class="inner-inner-wrap">
	<!-- <div class="sidebar-secondary">
		<nav class="sidebar">
			<h2 class="sidebar__section-title">Upload Directories</h2>
			<a href="" class="sidebar__link active">All Members</a>
			<a href="" class="sidebar__link">Pending Activation</a>
			<a href="" class="sidebar__link">Banned Members</a>

			<div class="sidebar__section-title">Member Roles</div>
			<a href="" class="sidebar__link">Roles</a>
		</nav>

	</div> -->

	<div class="container">


		<div class="table-responsive table-responsive--collapsible">
			<table>
				<thead>
					<tr>
						<th class="column-sort-header">
							<a href="./entry.php" class="column-sort column-sort--desc">ID# </a>
						</th>
						<th class="column-sort-header column-sort-header--active">
							<a href="./entry.php" class="column-sort column-sort--asc">Title </a>
						</th>
						<th class="column-sort-header">
							<a href="./entry.php" class="column-sort column-sort--desc">Comments</a>
						</th>
						<th class="column-sort-header">
							<a href="./entry.php" class="column-sort column-sort--desc">Date</a>
						</th>
						<th class="column-sort-header">
							<a href="./entry.php" class="column-sort column-sort--desc">Status </a>
						</th>
						<!-- <th>
							Manage </th> -->
						<th class="check-ctrl">
							<input type="checkbox" class="checkbox checkbox--remove" title="select all">
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><span class="collapsed-label">ID</span> 24177</td>
						<td><span class="collapsed-label">Title</span> <a href="./entry.php">Spotlight: Greentech</a><br><span class="meta-info">— by: James Mathias, in: Blog</span></td>
						<td><span class="collapsed-label">Comments</span> 0</td>
						<td><span class="collapsed-label">Date</span> 5/24/2019</td>
						<td><span class="collapsed-label">Status</span> <span class="status-tag status-tag--open" style="">Open</span></td>
						<!-- <td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="view"><a href="./entry.php" title="Preview"></a></li>
									<li class="edit"><a href="./entry.php" title="Edit"></a></li>
								</ul>
							</div>
						</td> -->
						<td>
							<input name="selection[]" value="24177" data-channel-id="43" data-confirm="Entry: <b>Spotlight: Greentech</b>" type="checkbox">
						</td>
					</tr>
					<tr class="">
						<td><span class="collapsed-label">ID</span>24163</td>
						<td class="table-title"><span class="collapsed-label">Title</span><a href="./entry.php">The Add-on Store has Launched!</a><br><span class="meta-info">— by: Derek Jones, in: Blog</span></td>
						<td><span class="collapsed-label">Comments</span>0</td>
						<td><span class="collapsed-label">Date</span>5/1/2019</td>
						<td><span class="collapsed-label">Status</span><span class="status-tag status-tag--open" style="">Open</span></td>
						<!-- <td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="view"><a href="./entry.php" title="Preview"></a></li>
									<li class="edit"><a href="./entry.php" title="Edit"></a></li>
								</ul>
							</div>
						</td> -->
						<td>
							<input type="checkbox">
						</td>
					</tr>
					<tr>
						<td>24035</td>
						<td class="table-title"><a href="./entry.php">Announcing ExpressionEngine University!</a><br><span class="meta-info">— by: Rick Ellis, in: Blog</span></td>
						<td>0</td>
						<td>3/1/2019</td>
						<td><span class="status-tag status-tag--open" style="">Open</span></td>
						<!-- <td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="view"><a href="./entry.php" title="Preview"></a></li>
									<li class="edit"><a href="./entry.php" title="Edit"></a></li>
								</ul>
							</div>
						</td> -->
						<td>
							<input name="selection[]" value="24035" data-title="Announcing ExpressionEngine University!" data-channel-id="43" data-confirm="Entry: <b>Announcing ExpressionEngine University!</b>" type="checkbox">
						</td>
					</tr>
					<tr>
						<td>23943</td>
						<td class="table-title"><a href="./entry.php">Spotlight: McGough</a><br><span class="meta-info">— by: James Mathias, in: Blog</span></td>
						<td>0</td>
						<td>2/11/2019</td>
						<td><span class="status-tag status-tag--open" style="">Open</span></td>
						<!-- <td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="view"><a href="./entry.php" title="Preview"></a></li>
									<li class="edit"><a href="./entry.php" title="Edit"></a></li>
								</ul>
							</div>
						</td> -->
						<td>
							<input name="selection[]" value="23943" data-title="Spotlight: McGough" data-channel-id="43" data-confirm="Entry: <b>Spotlight: McGough</b>" type="checkbox">
						</td>
					</tr>
					<tr>
						<td>23941</td>
						<td class="table-title"><a href="./entry.php">The Brand New ExpressionEngine User Guide</a><br><span class="meta-info">— by: Jordan Ellis, in: Blog</span></td>
						<td>0</td>
						<td>2/6/2019</td>
						<td><span class="status-tag status-tag--open" style="">Open</span></td>
						<!-- <td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="view"><a href="./entry.php" title="Preview"></a></li>
									<li class="edit"><a href="./entry.php" title="Edit"></a></li>
								</ul>
							</div>
						</td> -->
						<td>
							<input name="selection[]" value="23941" data-title="The Brand New ExpressionEngine User Guide" data-channel-id="43" data-confirm="Entry: <b>The Brand New ExpressionEngine User Guide</b>" type="checkbox">
						</td>
					</tr>
					<tr>
						<td>23930</td>
						<td class="table-title"><a href="./entry.php">5.1 brings Drag, Drop, and Bulk Uploads</a><br><span class="meta-info">— by: Kevin Cupp, in: Blog</span></td>
						<td>0</td>
						<td>1/22/2019</td>
						<td><span class="status-tag status-tag--open" style="">Open</span></td>
						<!-- <td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="view"><a href="./entry.php" title="Preview"></a></li>
									<li class="edit"><a href="./entry.php" title="Edit"></a></li>
								</ul>
							</div>
						</td> -->
						<td>
							<input name="selection[]" value="23930" data-title="5.1 brings Drag, Drop, and Bulk Uploads" data-channel-id="43" data-confirm="Entry: <b>5.1 brings Drag, Drop, and Bulk Uploads</b>" type="checkbox">
						</td>
					</tr>
					<tr>
						<td>23848</td>
						<td class="table-title"><a href="./entry.php">Make upgrades easy with these simple tips</a><br><span class="meta-info">— by: James Mathias, in: Blog</span></td>
						<td>0</td>
						<td>12/13/2018</td>
						<td><span class="status-tag status-tag--closed" style="">Closed</span></td>
						<!-- <td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="view"><a href="./entry.php" title="Preview"></a></li>
									<li class="edit"><a href="./entry.php" title="Edit"></a></li>
								</ul>
							</div>
						</td> -->
						<td>
							<input name="selection[]" value="23848" data-title="Make upgrades easy with these simple tips" data-channel-id="43" data-confirm="Entry: <b>Make upgrades easy with these simple tips</b>" type="checkbox">
						</td>
					</tr>
					<tr>
						<td>23844</td>
						<td class="table-title"><a href="./entry.php">EllisLab Acquired by Digital Locations</a><br><span class="meta-info">— by: Rick Ellis, in: Blog</span></td>
						<td>0</td>
						<td>12/4/2018</td>
						<td><span class="status-tag status-tag--open" style="">Open</span></td>
						<!-- <td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="view"><a href="./entry.php" title="Preview"></a></li>
									<li class="edit"><a href="./entry.php" title="Edit"></a></li>
								</ul>
							</div>
						</td> -->
						<td>
							<input name="selection[]" value="23844" data-title="EllisLab Acquired by Digital Locations" data-channel-id="43" data-confirm="Entry: <b>EllisLab Acquired by Digital Locations</b>" type="checkbox">
						</td>
					</tr>
					<tr>
						<td>23840</td>
						<td class="table-title"><a href="./entry.php">New Executive Roles</a><br><span class="meta-info">— by: Rick Ellis, in: Blog</span></td>
						<td>0</td>
						<td>11/29/2018</td>
						<td><span class="status-tag status-tag--closed" style="">Closed</span></td>
						<!-- <td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="view"><a href="./entry.php" title="Preview"></a></li>
									<li class="edit"><a href="./entry.php" title="Edit"></a></li>
								</ul>
							</div>
						</td> -->
						<td>
							<input name="selection[]" value="23840" data-title="New Executive Roles" data-channel-id="43" data-confirm="Entry: <b>New Executive Roles</b>" type="checkbox">
						</td>
					</tr>
					<tr>
						<td>23837</td>
						<td class="table-title"><a href="./entry.php">Why the Apache Open-Source License?</a><br><span class="meta-info">— by: Derek Jones, in: Blog</span></td>
						<td>0</td>
						<td>11/28/2018</td>
						<td><span class="status-tag status-tag--open" style="">Open</span></td>
						<!-- <td>
							<div class="toolbar-wrap">
								<ul class="toolbar">
									<li class="view"><a href="./entry.php" title="Preview"></a></li>
									<li class="edit"><a href="./entry.php" title="Edit"></a></li>
								</ul>
							</div>
						</td> -->
						<td>
							<input name="selection[]" value="23837" data-title="Why the Apache Open-Source License?" data-channel-id="43" data-confirm="Entry: <b>Why the Apache Open-Source License?</b>" type="checkbox">
						</td>
					</tr>
				</tbody>
			</table>

		</div>



	</div>
</div>
</div>
</div>

<?php include(dirname(__FILE__) . '/_wrapper-footer.php'); ?>

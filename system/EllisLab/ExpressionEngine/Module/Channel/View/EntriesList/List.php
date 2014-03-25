<div class="heading">
	<h2 class="edit">Edit</h2>
</div>
<div class="pageContents group">
	<div id="filterMenu">
		<form action="" method="post" name="filterform" id="filterform">
			<fieldset>
				<legend>Search Entries</legend>
					<input type="hidden" name="XID" value="eafc83bd7420f0dba9ad72cd362e94c0f6ad34a7" />
					<div class="group">
						<select name="channel_id" id="f_channel_id">
							<option value="null">Filter by Channel</option>
							<option value="4">Blog</option>
						</select>&nbsp;&nbsp;			
						<select name="cat_id" id="f_cat_id">
							<option value="" selected="selected">Filter by Category</option>
							<option value="none">None</option>
						</select>&nbsp;&nbsp;			
						<select name="status" id="f_status">
							<option value="" selected="selected">Filter by Status</option>
							<option value="all">All</option>
							<option value="open">Open</option>
							<option value="closed">Closed</option>
						</select>&nbsp;&nbsp;
						<select name="date_range" id="date_range">
							<option value="" selected="selected">Date Range</option>
							<option value="1">Last 24 hours</option>
							<option value="7">Last 7 days</option>
							<option value="31">Last 30 days</option>
							<option value="182">Last 180 days</option>
							<option value="365">Last 365 days</option>
							<option value="custom_date">Custom Date Range</option>
						</select>&nbsp;&nbsp;
						<select name="perpage" id="f_perpage">
							<option value="10">10 results</option>
							<option value="25">25 results</option>
							<option value="50" selected="selected">50 results</option>
							<option value="75">75 results</option>
							<option value="100">100 results</option>
							<option value="150">150 results</option>
						</select>
					</div>
					<div id="custom_date_picker" style="display: none; margin: 0 auto 50px auto;width: 500px; height: 235px; padding: 5px 15px 5px 15px;border: 1px solid black;  background: #FFF;">
						<div id="cal1" style="width:250px; float:left; text-align:center;">
							<p style="text-align:left; margin-bottom:5px"><label for="custom_date_start">Start</label>:&nbsp; <input type="text" name="custom_date_start" id="custom_date_start" value="yyyy-mm-dd" size="12" tabindex="1" /></p>
							<span id="custom_date_start_span"></span>
						</div>
						<div id="cal2" style="width:250px; float:left; text-align:center;">
							<p style="text-align:left; margin-bottom:5px"><label for="custom_date_end">End</label>:&nbsp; <input type="text" name="custom_date_end" id="custom_date_end" value="yyyy-mm-dd" size="12" tabindex="2" /></p>
							<span id="custom_date_end_span"></span>          
						</div>
        			</div>
					<div>
						<label for="keywords" class="js_hide">Keywords </label><input type="text" name="keywords" value="" id="keywords" maxlength="200" class="field shun" placeholder="Keywords" /><br />
						<input type="checkbox" name="exact_match" value="yes" id="exact_match" /> 
						<label for="exact_match">Exact Match</label>&nbsp;&nbsp;			
						<select name="search_in" id="f_search_in">
							<option value="title" selected="selected">Search titles only</option>
							<option value="body">Search titles and entries</option>
							<option value="everywhere">Search titles, entries, and comments</option>
						</select>&nbsp;&nbsp;			
						<input type="submit" name="submit" value="Search" class="submit" id="search_button" />&nbsp;&nbsp;			
						<img src="http://develop.ee.dev/themes/cp_themes/default/images/indicator.gif" class="searchIndicator" alt="Edit Search Indicator" style="margin-bottom: -5px; visibility: hidden;" width="16" height="16" />
					</div>
				</form>	
			</fieldset>
		</div> <!-- filterMenu -->
		<form action="index.php?S=6eb13e289f963c908281f807f12d8bd5&amp;D=cp&amp;C=content_edit&amp;M=multi_edit_form" method="post" id="entries_form">
			<input type="hidden" name="XID" value="eafc83bd7420f0dba9ad72cd362e94c0f6ad34a7" />
			<p class="tbl_523b3e7300bff js_hide" id="paginationLinks"></p>	
			<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th data-table_column='entry_id'>#</th>
						<th data-table_column='title'>Title</th>
						<th data-table_column='view' class='no-sort'>View</th>
						<th data-table_column='comment_total'>Comments</th>
						<th data-table_column='screen_name'>Author</th>
						<th data-table_column='entry_date'>Date</th>
						<th data-table_column='channel_name'>Channel</th>
						<th data-table_column='status'>Status</th>
						<th data-table_column='_check' class='no-sort'>
							<input type="checkbox" name="select_all" value="true" class="toggle_all" />
						</th>
					</tr>
				</thead>
				<tbody>
				<?PHP
					foreach($rows as $row) 
					{
						echo $row;
					}	
				?>
				</tbody>
			</table>
			<div class="tableSubmit">
				<input type="submit" name="submit" value="Submit" class="submit" />&nbsp;&nbsp;
				<select name="action">
					<option value="edit">Edit Selected</option>
					<option value="delete">Delete Selected</option>
					<option value="------">------</option>
					<option value="add_categories">Add Categories</option>
					<option value="remove_categories">Remove Categories</option>
				</select>&nbsp;&nbsp;
			</div>
			<p class="tbl_523b3e7300bff js_hide" id="paginationLinks"></p>
		</form>	
		</div>
	</div>
</div>

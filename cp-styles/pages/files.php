<?php $page_title = 'Files'; $page_toolbar = '<a class="button button--action"><i class="fas fa-cloud-upload-alt icon-left"></i> Upload</a>'; include(dirname(__FILE__) . '/_wrapper-head.php'); ?>

<!-- <div class="dropdown">
	<div class="dropdown__search">
		<input type="text">
	</div>
	<a href="" class="dropdown__link">Test</a>
</div> -->

<div class="secondary-sidebar-container">
	<div class="secondary-sidebar">
		<nav class="sidebar">
			<h2 class="sidebar__section-title">Upload Directories</h2>
			<a href="" class="sidebar__link active"><i class="fas fa-folder"></i> Article Images</a>
			<a href="" class="sidebar__link"><i class="fas fa-folder"></i> Photography</a>
			<a href="" class="sidebar__link"><i class="fas fa-folder"></i> Team Photos</a>

			<div class="sidebar__section-divider"></div>
			<a href="" class="sidebar__link"><i class="fas fa-tint"></i> Watermarks</a>
		</nav>

	</div>

	<div class="container">

		<!-- <i class="fas fa-chevron-left"></i>  -->

		<div class="title-bar">
			<h2 class="title-bar__title">Files in <b>Article Images</b></h2>

			<div class="filter-bar">
			<div class="filter-bar__item filter-search-form">
			<div class="search-input">
	<input class="search-input__input" type="text" name="filter_by_keyword" value="" placeholder="Search">
</div>
		</div>
			<div class="filter-bar__item ">
			<div class="">
				<a class="filter-bar__button" href="admin.php?/cp/files&amp;viewtype=table&amp;perpage=25" title="View as List">
							<i class="fas fa-fw fa-list"></i>
					</a>
				</div>
		</div>
			<div class="filter-bar__item ">
			<button type="button" class="has-sub filter-bar__button js-dropdown-toggle" data-filter-label="show">
	show		<span class="faded">(25)</span>
	</button>
<div class="dropdown">
		<div class="dropdown__search">
		<div class="search-input">
		<input type="text" name="perpage" value="" placeholder="custom limit" data-threshold="1000" data-threshold-text="Viewing more than 1000 items at a time may result in reduced performance." class="search-input__input">
		</div>
	</div>
						<a class="dropdown__link" href="admin.php?/cp/files&amp;viewtype=thumb&amp;perpage=25">25 results</a>
							<a class="dropdown__link" href="admin.php?/cp/files&amp;viewtype=thumb&amp;perpage=50">50 results</a>
							<a class="dropdown__link" href="admin.php?/cp/files&amp;viewtype=thumb&amp;perpage=75">75 results</a>
							<a class="dropdown__link" href="admin.php?/cp/files&amp;viewtype=thumb&amp;perpage=100">100 results</a>
							<a class="dropdown__link" href="admin.php?/cp/files&amp;viewtype=thumb&amp;perpage=150">150 results</a>
							<a class="dropdown__link" href="admin.php?/cp/files&amp;viewtype=thumb&amp;perpage=11">All 11 files</a>
			</div>

		</div>
		</div>
		</div>

		<div class="file-card-wrapper">
			<a href class="file-card">
				<div class="file-card__preview">
					<!-- <a href="" class="button button--secondary file-card__preview-button">Edit</a> -->
					<div class="file-card__preview-image">
						<img src="../app/assets/images/file-1.jpg" alt="">
					</div>
				</div>
				<div class="file-card__info">
					<div class="file-card__info-name">Under The Sea</div>
					<div class="file-card__info-subtitle">1200x850 - 1MB</div>
				</div>
			</a>
			<div class="file-card">
				<div class="file-card__preview">
					<div class="file-card__preview-image">
						<img src="../app/assets/images/file-2.jpg" alt="">
					</div>
				</div>
				<div class="file-card__info">
					<div class="file-card__info-name">alley.png</div>
					<div class="file-card__info-subtitle">500x1000 - 48KB</div>
				</div>
			</div>
			<div class="file-card">
				<div class="file-card__preview">
					<div class="file-card__preview-image">
						<img src="../app/assets/images/file-3.jpg" alt="">
					</div>
				</div>
				<div class="file-card__info">
					<div class="file-card__info-name">Fish</div>
					<div class="file-card__info-subtitle">500x1000 - 80KB</div>
				</div>
			</div>
			<div class="file-card">
				<div class="file-card__preview">
					<div class="file-card__preview-image">
						<img src="../app/assets/images/file-4.jpg" alt="">
					</div>
				</div>
				<div class="file-card__info">
					<div class="file-card__info-name">Owl in a tree</div>
					<div class="file-card__info-subtitle">500x1000 - 1MB</div>
				</div>
			</div>
			<!-- <div class="file-card file-card--missing">
				<div class="file-card__preview">
					<div class="file-card__preview-icon">
						<i class="fas fa-lg fa-exclamation-triangle"></i>
						<div class="file-card__preview-icon-text">File Not Found</div>
					</div>
				</div>
				<div class="file-card__info">
					<div class="file-card__info-name">lost.png</div>
					<div class="file-card__info-subtitle">File Not Found</div>
				</div>
			</div> -->
			<div class="file-card">
				<div class="file-card__preview">
					<div class="file-card__preview-image">
						<img src="../app/assets/images/file-6.jpg" alt="">
					</div>
				</div>
				<div class="file-card__info">
					<div class="file-card__info-name">Mountain.jpg</div>
					<div class="file-card__info-subtitle">1200x1080 - 3MB</div>
				</div>
			</div>
			<!-- <div class="file-card">
				<div class="file-card__preview">
					<div class="file-card__preview-image">
						<img src="../app/assets/images/file-8.jpg" alt="">
					</div>
				</div>
				<div class="file-card__info">
					<div class="file-card__info-name">Tiny Image</div>
					<div class="file-card__info-subtitle">1200x1080 - 3MB</div>
				</div>
			</div> -->
			<div class="file-card">
				<div class="file-card__preview">
					<div class="file-card__preview-image">
						<img src="../app/assets/images/file-5.jpg" alt="">
					</div>
				</div>
				<div class="file-card__info">
					<div class="file-card__info-name">purple_trees_2_original.tiff</div>
					<div class="file-card__info-subtitle">1200x1080 - 3MB</div>
				</div>
			</div>
			<div class="file-card">
				<div class="file-card__preview">
					<div class="file-card__preview-icon">
						<i class="fas fa-file-archive fa-3x"></i>
					</div>
				</div>
				<div class="file-card__info">
					<div class="file-card__info-name">purple_trees_2_original.tiff</div>
					<div class="file-card__info-subtitle">1200x1080 - 3MB</div>
				</div>
			</div>
		</div>
	</div>
</div>


<?php include(dirname(__FILE__) . '/_wrapper-footer.php'); ?>

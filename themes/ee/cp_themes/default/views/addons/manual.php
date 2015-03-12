<?php extend_template('wrapper'); ?>

<div class="col-group">
	<div class="col w-16 last">
		<div class="box full mb">
			<div class="tbl-ctrls">
				<?=form_open(cp_url('addons'))?>
					<fieldset class="tbl-search right">
						<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="">
						<input class="btn submit" type="submit" value="<?=lang('search_addons_button')?>">
					</fieldset>
					<h1><?=$cp_page_title?></h1>
				<?=form_close()?>
			</div>
		</div>
	</div>
</div>

<div class="col-group">
	<div class="col w-16 last">
		<?php if (count($cp_breadcrumbs)): ?>
			<ul class="breadcrumb">
				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
					<li><a href="<?=$link?>"><?=$title?></a></li>
				<?php endforeach ?>
				<li class="last"><?=$cp_heading?></li>
			</ul>
		<?php endif ?>
		<div class="box">
			<h1><?=$name?> <?=$version?><br><i><?=lang('author')?>: <a href="<?=$author_url?>" rel="external"><?=$author?></a><br><?=$description?></i></h1>
			<form class="settings">
				<fieldset class="col-group last">
					<div class="setting-txt col w-16">
						<h3><?=lang('example_usage')?></h3>
						<em><?=$usage['description']?></em>
					</div>
					<div class="setting-field col w-16 last">
						<textarea cols="" rows=""><?=$usage['example']?></textarea>
					</div>
				</fieldset>
				<?php if (isset($parameters)): ?>
				<h2><?=lang('available_parameters')?></h2>
				<?php foreach ($parameters as $name => $details): ?>
				<fieldset class="col-group">
					<div class="setting-txt col w-8">
						<h3><mark><?=$name?></mark></h3>
						<em><?=$details['description']?></em>
					</div>
					<div class="setting-field col w-8 last">
						<textarea cols="" rows=""><?=$details['example']?></textarea>
					</div>
				</fieldset>
				<?php endforeach;?>
			<?php endif; ?>
			</form>
		</div>
	</div>
</div>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>
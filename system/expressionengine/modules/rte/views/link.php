<div class="modal-wrap modal-rte-link-dialog">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="box">
					<h1><?=lang('link')?> <span class="req-title"><?=lang('required_fields')?></span></h1>
					<form class="settings">
						<fieldset class="col-group required">
							<div class="setting-txt col w-8">
								<h3><?=lang('rte_url')?></h3>
							</div>
							<div class="setting-field col w-8 last">
								<input type="text" name="url" required="required">
							</div>
						</fieldset>
						<fieldset class="col-group">
							<div class="setting-txt col w-8">
								<h3><?=lang('rte_title')?></h3>
							</div>
							<div class="setting-field col w-8 last">
								<input type="text" name="title">
							</div>
						</fieldset>
						<fieldset class="col-group">
							<div class="setting-txt col w-8">
								<h3><?=lang('external_link')?></h3>
							</div>
							<div class="setting-field col w-8 last">
								<label class="choice mr yes"><input type="radio" name="external" value="y"> <?=lang('yes')?></label> <label class="choice chosen no"><input type="radio" name="external" value="n" checked="checked"> <?=lang('no')?></label>
							</div>
						</fieldset>
						<fieldset class="form-ctrls">
							<button id="rte-remove-link" class="btn hidden"><?=lang('remove_link')?></button>
							<input class="btn" type="submit" value="<?=lang('add_link')?>">
						</fieldset>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

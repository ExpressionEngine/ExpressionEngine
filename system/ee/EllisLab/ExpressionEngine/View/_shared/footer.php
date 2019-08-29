
		<div class="overlay"></div>
		<div class="app-overlay"></div>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>
<?php echo implode('', ee('CP/Modal')->getAllModals()); ?>

		<?=ee()->view->script_tag('jquery/jquery.js')?>
		<?=ee()->view->script_tag('common.js')?>
		<?php

		echo ee()->javascript->get_global();

		echo ee()->cp->render_footer_js();

		if (isset($_extra_library_src))
		{
			echo $_extra_library_src;
		}

		echo ee()->javascript->script_foot();

		foreach (ee()->cp->get_foot() as $item)
		{
			echo $item."\n";
		}

		?>
		<div id="idle-modal" class="modal-wrap modal-timeout hidden">
			<div class="modal">
				<div class="col-group snap">
					<div class="col w-16 last">
						<a class="m-close" href="#"></a>
						<div class="form-standard">
							<?=form_open(ee('CP/URL')->make('login/authenticate'))?>
								<div class="form-btns form-btns-top">
									<h1>Log into <?=ee()->config->item('site_name')?></h1>
								</div>
								<?=ee('CP/Alert')
									->makeInline()
									->asImportant()
									->addToBody(lang('session_timeout'))
									->render()?>
								<fieldset class="fieldset-required">
									<div class="field-instruct">
										<label><?=lang('username')?></label>
										<em></em>
									</div>
									<div class="field-control">
										<input type="text" value="<?=form_prep(ee()->session->userdata('username'))?>" disabled="disabled">
										<input type="hidden" name="username" value="<?=form_prep(ee()->session->userdata('username'))?>">
									</div>
								</fieldset>
								<fieldset class="fieldset-required">
									<div class="field-instruct">
										<label><?=lang('password')?></label>
										<em></em>
									</div>
									<div class="field-control">
										<input type="password" name="password" value="" id="logout-confirm-password">
									</div>
								</fieldset>
								<div class="form-btns">
									<?=form_submit('submit', lang('login'), 'class="btn" data-submit-text="'.lang('login').'" data-work-text="'.lang('authenticating').'"')?>
								</div>
							<?=form_close()?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?=ee('CP/Alert')->getStandard()?>
	</body>
</html>

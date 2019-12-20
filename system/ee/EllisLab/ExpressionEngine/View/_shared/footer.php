
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
		<div id="idle-modal" class="modal-wrap modal-wrap--small modal-timeout hidden">
			<div class="modal modal--no-padding dialog dialog--warning">

			<div class="dialog__header dialog__header--centered">
				<div class="dialog__icon"><i class="fas fa-user-clock"></i></div>
				<h2 class="dialog__title">Log into <?=ee()->config->item('site_name')?></h2>
			</div>

			<div class="dialog__body">
			<?=lang('session_timeout')?>
			</div>

			<?=form_open(ee('CP/URL')->make('login/authenticate'))?>
			<div class="dialog__actions">
				<input type="hidden" name="username" value="<?=form_prep(ee()->session->userdata('username'))?>">
				<fieldset class="fieldset-required">
					<div class="field-instruct">
						<label><?=sprintf(lang('password_for'), form_prep(ee()->session->userdata('username')));?></label>
					</div>
					<div class="field-control">
						<input type="password" name="password" value="" id="logout-confirm-password">
					</div>
				</fieldset>

				<div class="dialog__buttons">
					<div class="button-group">
						<?=form_submit('submit', lang('login'), 'class="btn" data-submit-text="'.lang('login').'" data-work-text="'.lang('authenticating').'"')?>
					</div>
				</div>
			</div>
			<?=form_close()?>
			</div>
		</div>

		<div id="jump-menu" class="modal-wrap modal-wrap--small modal-timeout hidden" style="display:none;">
			<div class="jump-menu">
				<div class="jump-menu__input" id="jumpMenu1">
					<input type="text" id="jumpEntry1" class="jump-to" placeholder="Go To..">
				</div>
				<div class="jump-menu__input" id="jumpMenu2" style="display:none;">
					<span id="jumpEntry1Selection" class="action-tag">Edit Entry Titled:</span>
					<input type="text" id="jumpEntry2" class="jump-to" placeholder="Search For..">
				</div>
				<div class="jump-menu__items" id="jumpMenuResults1"></div>
				<div class="jump-menu__items" id="jumpMenuResults2"></div>
				<div class="jump-menu__no-results" id="jumpMenuNoResults" style="display:none;"><div class="jump-menu__header text-center">No Results</div></div>

				<div class="jump-menu__footer">
					<span class="jump-menu__shortcut">Shortcut: &nbsp;&nbsp; <span class="key"><i class="fab fa-sm fa-windows"></i> J</span> or <span class="key">&#8984; J</span> or <span class="key">Ctrl J</span></span>
					<span class="jump-menu__close">Close <span class="key">ESC</span></span>
				</div>
			</div>
		</div>

		<?=ee('CP/Alert')->getStandard()?>
	</body>
</html>

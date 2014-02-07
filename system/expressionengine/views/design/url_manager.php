<?php extend_template('basic') ?>
		<div class="formArea">
			<div>
				<div class="templateEditorTop">
					<h2><?=lang('url_manager')?></h2>
				</div>
			</div>

			<div id="url_manager">
				<div class="formHeading">
					Match Route:
					<input type="text" name="template_route" value=""/>
				</div>
				<?php echo $table ?>
			</div>
		</div>
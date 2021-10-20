<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel">

	<div class="panel-heading">
		<div class="title-bar">
			<h2 class="title-bar__title"><?=lang('debug_tools_missing_addons')?></h2>
		</div>
	</div>

	<div class="panel-body">
        <?php
        if (count($missing_addons) > 0) :
            foreach ($missing_addons as $addon => $files) :
                $fts = '';
                foreach ($files as $file) :
                    $fts .= '<li class="last">' . lang($file) . '</li>';
                endforeach;
                echo ee('CP/Alert')
                    ->makeInline()
                    ->withTitle(ucfirst($addon))
                    ->addToBody(sprintf(lang('debug_tools_missing_addon'), ucfirst($addon)))
                    ->addToBody('<ul>' . $fts . '</ul>')
                    ->asImportant()
                    ->render();
                
            endforeach;
        else:
            echo ee('CP/Alert')
                ->makeInline()
                ->withTitle(lang('debug_tools_no_missing_addons_desc'))
                ->asSuccess()
                ->cannotClose()
                ->render();
        endif;

        ?>

	</div>

</div>

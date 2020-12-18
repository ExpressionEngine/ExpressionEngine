<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel">

	<div class="panel-heading">
		<div class="title-bar">
			<h2 class="title-bar__title"><?=lang('debug_tools_missing_fieldtypes')?></h2>
		</div>
	</div>

	<div class="panel-body">
        <?php
        if ($missing_fieldtype_count > 0) :
            $fts = '';
            foreach ($missing_fieldtypes as $fieldtype => $tables) :
                $fts .= '<li class="last">' . $fieldtype . ' (' . implode(", ", $tables) . ')</li>';
            endforeach;
            echo ee('CP/Alert')
                ->makeInline()
                ->withTitle(lang('debug_tools_missing_fieldtypes_desc'))
                ->addToBody('<ul>' . $fts . '</ul>')
                ->asImportant()
                ->render();
        else:
            echo ee('CP/Alert')
                ->makeInline()
                ->withTitle(lang('debug_tools_no_missing_fieldtypes_desc'))
                ->asSuccess()
                ->cannotClose()
                ->render();
        endif;

        $fts = '';
        foreach ($unused_fieldtypes as $fieldtype) :
            $fts .= '<li class="last">' . $fieldtype . '</li>';
        endforeach;
        echo ee('CP/Alert')
            ->makeInline()
            ->withTitle(lang('debug_tools_installed_unused_fieldtypes'))
            ->addToBody(lang('debug_tools_installed_unused_fieldtypes_desc') . '<ul>' . $fts . '</ul>')
            ->asImportant()
            ->render();

        $fts = '';
        foreach ($used_fieldtypes as $fieldtype) :
            $fts .= '<li class="last">' . $fieldtype . '</li>';
        endforeach;
        echo ee('CP/Alert')
            ->makeInline()
            ->withTitle(lang('debug_tools_all_used_fieldtypes'))
            ->addToBody('<ul>' . $fts . '</ul>')
            ->asSuccess()
            ->cannotClose()
            ->render();

        ?>

	</div>

</div>




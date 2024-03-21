<?php

$lang = array(

    // Settings
    'rte_file_browser' => 'File Browser',
    'rte_file_browser_desc' => 'Which file browser should be used when browsing for images and files from your RTE fields?',

    // Configs
    'rte_clone' => 'Clone',
    'rte_no_configs' => 'There are currently no configurations.',

    // Edit Config
    'rte_basic_settings' => 'Basic Settings',
    'rte_create_config' => 'Create a New Configuration',
    'rte_edit_config' => 'Edit Configuration',
    'rte_config_settings' => 'Configuration Settings',
    'rte_config_name' => 'Configuration Name',
    'rte_toolbar' => 'Customize the Toolbar',
    'custom_stylesheet' => 'Custom Stylesheet',
    'custom_stylesheet_desc' => 'CSS template with styles to be applied to fields using this tool set. All styles will be automatically prefixed with toolset class.',
    'custom_javascript' => 'Extra JavaScript',
    'custom_javascript_rte_desc' => 'JS template to be included with fields using this tool set. Typically used to include extra plugins when using advanced configuration.',
    'rte_min_height' => 'Minimal height',
    'rte_min_height_desc' => 'Enter the number of pixels, or leave empty',
    'rte_max_height' => 'Maximal height',
    'rte_max_height_desc' => 'Enter the number of pixels, or leave empty',
    'rte_limiter' => 'Limit characters',
    'rte_limiter_desc' => 'Limit the number of characters a user can enter.',
    'rte_upload_dir' => 'Upload Directory',
    'rte_advanced_settings' => 'Advanced Settings',
    'rte_advanced_config' => 'Advanced configuration',
    'rte_advanced_config_desc' => 'Edit configuration directly in JSON format',
    'rte_config_json' => 'Configuration JSON',
    'rte_config_json_desc' => 'Overrides visually constructed tool set',
    'rte_advanced_config_warning' => '<p><b>Warning</b>: <b class="no">Advanced users only.</b> Please be careful with using this feature and check your work.</p><p>Providing invalid configuration can make the RTE fields inaccessible.</p><p>Consult editing engine docs: <a href="https://ckeditor.com/docs/ckeditor5/latest/installation/getting-started/configuration.html" target="_blank">CKEditor</a>, <a href="https://imperavi.com/redactor/docs/settings/" target="_blank">Redactor</a>, <a href="https://imperavi.com/redactorx/docs/settings/" target="_blank">RedactorX</a>.</p><p>Note that some options might be not available or implemented differently. We suggest using Full configuration as starting base.<p>',
    'rte_config_saved' => 'Configuration Saved!',
    'rte_config_saved_desc' => 'Your configuration saved successfully.',
    'rte_custom_ckeditor_build' => 'Use custom CKEditor build?',
    'rte_custom_ckeditor_build_desc' => 'Allows using custom CKEditor build with extra plugins. If enabled, RTE instances running CKEditor will be created using <code>themes/user/rte/javascript/ckeditor.js</code> script. Check the User Guide for <a href="' . DOC_URL . 'add-ons/rte.html#ckeditor" rel="external">building instructions</a>.',

    // Delete Config
    'rte_delete_config' => 'Delete Configuration',
    'rte_delete_config_confirm' => 'Are you sure you want to permanently delete this configuration?',
    'rte_config_deleted' => 'Configuration Deleted',
    'rte_config_deleted_desc' => 'Your configuration was deleted successfully.',

    // -------------------------------------------
    //  Field Settings
    // -------------------------------------------

    'rte_editor_config' => 'Editor Configuration',
    'rte_edit_configs' => 'Edit&nbsp;Configurations',
    'rte_defer' => 'Defer Editor initialization?',
    'rte_defer_desc' => 'If you select “Yes”, RTE won’t initialize the field javascript until the field is clicked on.',

    // RTE

    'available_tool_sets' => 'Available Tool Sets',

    'btn_save_settings' => 'Save Settings',

    'choose_tools' => 'Include tools',

    'configuration' => 'Configuration',

    'create_new' => 'Create New',

    'create_tool_set' => 'Create Tool Set',

    'create_tool_set_header' => 'Create <abbr title="Rich Text Editor">RTE</abbr> Tool Set',

    'edit_tool_set' => 'Edit Tool Set',

    'edit_tool_set_header' => 'Edit <abbr title="Rich Text Editor">RTE</abbr> Tool Set',

    'no_tool_sets' => 'No Tool Sets Found',

    'rte_module_description' => '',

    'rte_module_name' => 'Rich Text Editor',

    'status' => 'Status',

    'tool_set' => 'Tool Set',

    'tool_set_name' => 'Name',

    'tool_type' => 'Editor Type',

    /* Headings */
    'create_new_toolset' => 'Create a New Tool Set',

    'edit_my_toolset' => 'Edit My Tool Set',

    'edit_toolset' => 'Edit Tool Set',

    'my_toolset' => 'My Tool Set',

    'nav_rte_settings' => 'Rich Text Editor Settings',

    'nav_rte_settings_short_desc' => 'Manage Rich Text Editor Tools and Tool Sets',

    'rte_prefs' => 'Rich Text Editor Preferences',

    'rte_settings' => 'Site Preferences',

    'tools' => 'Tools',

    'toolsets' => 'Tool Sets',

    /* Snippets */
    'cancel' => 'Cancel',

    'delete' => 'Delete',

    'tool' => 'Tool',

    'toolset' => 'Tool Set',

    /* Flashes */

    'cannot_remove_default_toolset' => 'The default RTE tool set cannot be removed',

    'disable_fail_desc' => 'The following tool sets were <b>not</b> disabled',

    'disable_success_desc' => 'The following tool sets were disabled',

    'enable_fail_desc' => 'The following tool sets were <b>not</b> enabled',

    'enable_success_desc' => 'The following tool sets were enabled',

    'name_required' => 'The tool set name is required.',

    'remove_fail_desc' => 'The following tool sets were <b>not</b> removed',

    'remove_success_desc' => 'The following tool sets were removed',

    'settings_error' => 'Error saving settings',

    'settings_error_desc' => 'Your Rich Text Editor Settings could not be saved. Please try again.',

    'settings_saved' => 'Settings saved',

    'settings_saved_desc' => 'Your Rich Text Editor Settings have been saved.',

    'tool_updated' => 'Tool updated',

    'toolset_created' => 'Tool set created',

    'toolset_created_desc' => '<b>%s</b> has been successfully created.',

    'toolset_updated_desc' => '<b>%s</b> has been successfully updated.',

    'toolset_deleted' => 'Tool set deleted successfully.',

    'toolset_edit_failed' => 'Tool set could not be opened for editing.',

    'toolset_error' => 'Tool set error',

    'toolset_error_desc' => 'We were unable to save the tool set, please review and fix errors below.',

    'toolset_json_error_desc' => 'The advanced configuration provided is not valid JSON.',

    'toolset_not_deleted' => 'Tool set could not be deleted.',

    'toolset_update_failed' => 'Tool set update failed. Please try again.',

    'toolset_updated' => 'Tool set updated',

    'toolsets_removed' => 'Tool sets removed',

    'toolsets_removed_desc' => '%d tool sets were removed.',

    'toolsets_updated' => 'Tool sets updated',

    'unique_name_required' => 'The tool set name must be unique.',

    'valid_name_required' => 'The tool set name must not include special characters.',

    'valid_url_required' => 'A valid URL is required.',

    /* Labels */
    'available_tools' => 'Available Tools (not being used)',

    'default_toolset' => 'Default <abbr title="Rich Text Editor">RTE</abbr> tool set',

    'default_toolset_details' => 'Shown for users that have not created their own or chosen another.',

    'enable_rte_for_field' => 'Enable Rich Text Editor',

    'enable_rte_globally' => 'Enable Rich Text Editor',

    'enable_rte_in_forum' => 'Enable the Rich Text Editor for use in the Forums?',

    'enable_rte_myaccount' => 'Enable Rich Text Editor',

    'rte_image_caption' => 'Image Caption:',

    'rte_relationship' => 'Relationship',

    'rte_selection_error' => 'Please select some text or images first.',

    'rte_title' => 'Title',

    'rte_url' => 'URL',

    'tools_in_toolset' => 'In This Tool set',

    'toolset_builder_instructions' => 'Select one or more tools and drag them to the desired location.',

    'toolset_builder_label' => 'Which tools should be available in this Tool set?',

    'toolset_name' => 'Tool set Name',

    /* tool names */

    'paragraph_rte' => 'Paragraph',

    'heading_h1_rte' => 'Heading H1',

    'heading_h2_rte' => 'Heading H2',

    'heading_h3_rte' => 'Heading H3',

    'heading_h4_rte' => 'Heading H4',

    'heading_h5_rte' => 'Heading H5',

    'heading_h6_rte' => 'Heading H6',

    'bold_rte' => 'Bold',

    'italic_rte' => 'Italic',

    'deleted_rte' => 'Deleted',

    'lists_rte' => 'Lists',

    'image_rte' => 'Image',

    'imageposition_rte' => 'Image position',

    'imageresize_rte' => 'Image resize',

    'file_rte' => 'File',

    'strikethrough_rte' => 'Strikethrough',

    'underline_rte' => 'Underline',

    'subscript_rte' => 'Subscript',

    'sub_rte' => 'Subscript',

    'superscript_rte' => 'Superscript',

    'sup_rte' => 'Superscript',

    'code_rte' => 'Code',

    'blockcode_rte' => 'Code',

    'blockquote_rte' => 'Block quote',

    'quote_rte' => 'Quote',

    'heading_rte' => 'Heading',

    'format_rte' => 'Format',

    'inlineformat_rte' => 'Format',

    'removeFormat_rte' => 'Remove formatting',

    'removeformat_rte' => 'Remove formatting',

    'undo_rte' => 'Undo',

    'redo_rte' => 'Redo',

    'numberedList_rte' => 'Numbered list',

    'ol_rte' => 'Numbered list',

    'bulletedList_rte' => 'Bulleted list',

    'ul_rte' => 'Bulleted list',

    'outdent_rte' => 'Decrease indent',

    'indent_rte' => 'Increase indent',

    'link_rte' => 'Link',

    'horizontalrule_rte' => 'Horizontal rule',

    'line_rte' => 'Horizontal rule',

    'filemanager_rte' => 'Image',

    'insertTable_rte' => 'Table',

    'selector_rte' => 'Class & ID',

    'table_rte' => 'Table',

    'mediaEmbed_rte' => 'Media',

    'htmlEmbed_rte' => 'HTML',

    'html_rte' => 'HTML',

    'alignment_rte' => 'Align',

    'alignment:left_rte' => 'Align left',

    'alignment:right_rte' => 'Align right',

    'alignment:center_rte' => 'Align center',

    'alignment:justify_rte' => 'Justify',

    'horizontalLine_rte' => 'Horizontal line',

    'specialCharacters_rte' => 'Special characters',

    'specialchars_rte' => 'Special characters',

    'readMore_rte' => '"Read More" separator',

    'readmore_rte' => '"Read More" separator',

    'fontColor_rte' => 'Font color',

    'fontBackgroundColor_rte' => 'Font background',

    'codeBlock_rte' => 'Code block',

    'sourceEditing_rte' =>  'Source editing',

    'open_in_new_tab' => 'Open in a new tab',

    'source_rte' => 'View Source',

    'showBlocks_rte' => 'Show blocks',

    'video_rte' => 'Video',

    'fullscreen_rte' => 'Fullscreen',

    'properties_rte' => 'Properties',

    'textdirection_rte' => 'Text Direction',

    'codemirror_rte' => 'Codemirror',

    'widget_rte' => 'Widget',

    'inlinestyle_rte' => 'Style',

    'rte_plugins' => 'Plugins',
    'rte_toolbar_buttons' => 'Toolbar Buttons',

    'rte_definedlinks_rte' => 'Pages links',

    'filebrowser_rte' => 'File Browser',

    'counter_rte' => 'Counter',

    'pages_rte' => 'Pages',

    'fontcolor_rte' => 'Text color',

    'rte_spellcheck' => 'Spell check',

    'rte_spellcheck_desc' => 'Enable spell check in the editor (needs to be enabled in browser as well)',

    'browser' => 'Browser',

    'grammarly' => 'Grammarly',

    'rte_control_bar' => 'Show control bar?',

    'rte_control_bar_desc' => 'Control bar is the collapsed menu shown at the left of focused element with some common actions',

    'rte_format' => 'Formatting options',

    'rte_format_desc' => 'Tags allowed in the Format dropdown',

    'add_rte' => 'Add',

    'shortcut_rte' => 'Shortcut',

    'embed_rte' => 'Embed',

    'mark_rte' => 'Mark',

    'kbd_rte' => 'kbd',

    'pre_rte' => 'Preformatted',

    'rte_show_context' => 'Show context bar?',

    'rte_show_context_desc' => 'The context bar appears when text is selected',

    'rte_context' => 'Context bar',

    'rte_show_addbar' => 'Show addbar?',

    'rte_show_addbar_desc' => 'The addbar appears when clicking Add button',

    'rte_addbar' => 'Addbar',

    'rte_show_topbar' => 'Show top bar?',

    'rte_show_topbar_desc' => 'Displayed to the right of main toolbar',

    'rte_topbar' => 'Top bar',

    'rte_toolbar_sticky' => 'Make toolbar sticky?',

    'rte_show_main_toolbar_desc' => 'Keeps the toolbar always visible when scrolling',

    'rte_show_main_toolbar' => 'Show main toolbar',

    'rte_show_main_toolbar_desc' => 'Can be disabled while keeping the functionality accessible using other toolbars or keyboard shortcuts',

    'rte_main_toolbar' => 'Main toolbar',

    '' => ''
);

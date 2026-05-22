import RteSettings from './RteSettings'

class RteEditToolset extends RteSettings {
    constructor() {
        super()
        this.url = 'admin.php?/cp/addons/settings/rte/edit_toolset';

        // Default toolbars configuration from RedactorService.php
        this.defaultToolbars = {
            'Redactor Basic': {
                'toolbar_hide': 'y',
                'toolbar_extrabar': 'n',
                'toolbar_addbar': 'n',
                'toolbar_context': 'n',
                'toolbar_control': 'n',
                'hide': [],
                'extrabar': [
                    'hotkeys'
                ],
                'addbar': [
                    'text',
                    'image',
                    'table'
                ],
                'context': [
                    'bold',
                    'italic',
                    'mark',
                    'link'
                ],
                'editor': [
                    'format',
                    'bold',
                    'italic',
                    'link'
                ],
                'format': [
                    'text',
                    'bulletlist',
                    'numberedlist'
                ],
                'plugins': [
                    'filebrowser',
                    'rte_definedlinks',
                    'pages',
                    'blockclass',
                ]
            },
            'Redactor Full': {
                'toolbar_hide': 'y',
                'toolbar_extrabar': 'y',
                'toolbar_addbar': 'y',
                'toolbar_context': 'y',
                'toolbar_control': 'y',
                'hide': [],
                'editor': [
                    'html',
                    'format',
                    'bold',
                    'italic',
                    'deleted',
                    'moreinline',
                    'list',
                    'link'
                ],
                'extrabar': [
                    'undo',
                    'redo',
                    'hotkeys'
                ],
                'addbar': [
                    'ai-image',
                    'heading',
                    'todo',
                    'list',
                    'embed',
                    'table',
                    'quote',
                    'pre',
                    'line',
                    'layout',
                    'wrapper'
                ],
                'context': [
                    'format',
                    'bold',
                    'italic',
                    'deleted',
                    'moreinline',
                    'link'
                ],
                'format': [
                    'text',
                    'h1',
                    'h2',
                    'h3',
                    'h4',
                    'quote',
                    'bulletlist',
                    'numberedlist',
                    'todo'
                ],
                'plugins': [
                    'underline',
                    'alignment',
                    'blockid',
                    'blockclass',
                    'blockcode',
                    'rte_definedlinks',
                    'pages',
                    'readmore',
                    'filebrowser',
                    'imageposition',
                    'imageresize',
                ]
            }
        }

        this.selectors = Object.assign(this.selectors, {
            // Page title and heading
            "page_title": '.main-nav__title',
            "edit_configuration_heading": 'h3:contains("Edit Configuration")',
            
            // Basic Settings
            "toolset_name": 'input[name="toolset_name"]',
            "toolset_type": 'select[name="toolset_type"]',
            "upload_dir": 'select[name="settings[upload_dir]"]',
            "text_direction_ltr": 'input[name="settings[field_text_direction]"][value="ltr"]',
            "text_direction_rtl": 'input[name="settings[field_text_direction]"][value="rtl"]',
            
            // Toolbar Configuration
            "main_toolbar": '.field-inputs[data-input-value*="toolbar"]',
            "main_toolbar_fieldset": '#fieldset-settings\\[redactor_toolbar\\]\\[hide\\]',
            "toolbar_sticky": 'input[name="settings[redactor_toolbar][sticky]"]',
            "toolbar_extrabar": 'input[name="settings[redactor_toolbar][toolbar_extrabar]"]',
            "extrabar_fieldset": '#fieldset-settings\\[redactor_toolbar\\]\\[extrabar\\]',
            "toolbar_addbar": 'input[name="settings[redactor_toolbar][toolbar_addbar]"]',
            "addbar_fieldset": '#fieldset-settings\\[redactor_toolbar\\]\\[addbar\\]',
            "toolbar_context": 'input[name="settings[redactor_toolbar][toolbar_context]"]',
            "context_bar_fieldset": '#fieldset-settings\\[redactor_toolbar\\]\\[context\\]',
            "toolbar_control": 'input[name="settings[redactor_toolbar][toolbar_control]"]',
            "control_bar_fieldset": '#fieldset-settings\\[redactor_toolbar\\]\\[control\\]',
            
            // Formatting Options
            "formatting_options": '.field-inputs[data-input-value*="formatting"]',
            "formatting_options_fieldset": '#fieldset-settings\\[redactor_toolbar\\]\\[format\\]',
            
            // Plugins
            "plugins": '.field-inputs[data-input-value*="plugins"]',
            "plugins_fieldset": '#fieldset-settings\\[redactor_toolbar\\]\\[plugins\\]',
            
            // Advanced Settings
            "custom_stylesheet": 'input[name="settings[custom_stylesheet]"]',
            "minimal_height": 'input[name="settings[height]"]',
            "maximal_height": 'input[name="settings[max_height]"]',
            "advanced_config": 'input[name="settings[rte_advanced_config]"]',
            
            // Save Buttons
            "save_button": '.form-btns-top button[value="save"]',
            "save_close_button": '.form-btns-top button[value="save_and_close"]',
            "save_dropdown": '.form-btns-top .saving-options'
        })
    }

    confirmEditToolset() {
        this.get('breadcrumb').contains('Add-Ons')
        this.get('breadcrumb').contains('Rich Text Editor')
        this.get('breadcrumb').contains('Edit Tool Set')

        cy.get('h1').contains('Rich Text Editor')
        this.get('edit_configuration_heading').should('exist')
        this.get('toolset_name').should('exist')
        this.get('save_button').should('exist')
    }
}

export default RteEditToolset;

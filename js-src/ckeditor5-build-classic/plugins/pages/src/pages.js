/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * @module pages/pages
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import LinkUI from '@ckeditor/ckeditor5-link/src/linkui';
import { LabeledFieldView, createLabeledDropdown } from '@ckeditor/ckeditor5-ui';

/**
 * The page break feature.
 *
 * It provides the possibility to insert a page break into the rich-text editor.
 *
 * For a detailed overview, check the {@glink features/readmore Page break feature} documentation.
 *
 * @extends module:core/plugin~Plugin
 */
export default class Pages extends Plugin {
    /**
     * @inheritDoc
     */
    init() {

       // ensure LinkUI is initialized fully
        if (this.editor.plugins.get('LinkUI')._createViews) {
            this.editor.plugins.get('LinkUI')._createViews();
        }

        this.linkFormView = this.editor.plugins.get( LinkUI ).formView;

        this.pageDropdown = this._createDropdown();

        this.linkFormView.once( 'render', () => {
            // Render button's tamplate.
            this.pageDropdown.render();

            // Register the button under the link form view, it will handle its destruction.
            this.linkFormView.registerChild( this.pageDropdown );

            // Inject the element into DOM.
            this.linkFormView.element.insertBefore( this.pageDropdown.element, this.linkFormView.urlInputView.element );
        } );
    }

    _createDropdown() {
        var dropdown = button.set( {
          label: 'A dropdown',
          withText: true
        } );
        .buttonView.set( {
          label: 'A dropdown',
          withText: true
        } );
        const extraFieldView = new LabeledFieldView(
            this.editor.locale,
            createLabeledDropdown,
        );
        extraFieldView.label = 'Page Link';


        return extraFieldView;
    }

    _changeFormToVertical() {
        const linkFormView = this.editor.plugins.get('LinkUI').formView;
        linkFormView.extendTemplate({
            attributes: {
                class: ['ck-vertical-form', 'ck-link-form_layout-vertical'],
            },
        });
    }

    _addExtraFormFields() {
        const { editor } = this;

        editor.plugins
            .get('ContextualBalloon')
            .on('set:visibleView', (evt, propertyName, newValue, oldValue) => {
                console.log('set:visibleView', propertyName, newValue, oldValue);
                const linkFormView = editor.plugins.get('LinkUI').formView;
                if (newValue === oldValue || newValue !== linkFormView) {
                  return;
                }

                this._createExtraFormField(
                    pageLink,
                    {
                        label: 'Title',
                        viewAttribute: 'title',
                    }
                );
            /*this._handleExtraFormFieldSubmit(enabledModelNames);
            // Add groups to form view last to ensure they're not beetween fields.
            this._addGroupsToFormView();
            this._moveTargetDecoratorToAdvancedGroup();*/
        });
    }

    _createExtraFormField(modelName, options) {
        const { editor } = this;
        const { locale } = editor;
        const linkFormView = editor.plugins.get('LinkUI').formView;
        const linkCommand = editor.commands.get('link');
        if (typeof linkFormView[modelName] === 'undefined') {
          const fieldParent = options.group
            ? this._getGroup(options.group)
            : linkFormView;
    
          const extraFieldView = new LabeledFieldView(
            locale,
            createLabeledDropdown,
          );
          extraFieldView.label = options.label;
          fieldParent.children.add(extraFieldView, 1);
    
          if (!options.group) {
            linkFormView._focusables.add(extraFieldView, 1);
            linkFormView.focusTracker.add(extraFieldView.element);
          }
    
          linkFormView[modelName] = extraFieldView;
          linkFormView[modelName].fieldView
            .bind('value')
            .to(linkCommand, modelName);
          // Note: Copy & pasted from LinkUI.
          // https://github.com/ckeditor/ckeditor5/blob/f0a093339631b774b2d3422e2a579e27be79bbeb/packages/ckeditor5-link/src/linkui.js#L333-L333
          linkFormView[modelName].fieldView.element.value =
            linkCommand[modelName] || '';
        }
    }

    /**
     * @inheritDoc
     */
    static get pluginName() {
        return 'Pages';
    }
}

define([
    'jquery',
    'taoQtiItem/qtiCreator/widgets/states/factory',
    'taoMediaManager/qtiCreator/widgets/static/states/Active',
    'taoMediaManager/qtiCreator/editor/ckEditor/htmlEditor',
    'taoQtiItem/qtiCreator/editor/gridEditor/content',
    'taoQtiItem/qtiCreator/widgets/helpers/formElement',
    'tpl!taoMediaManager/qtiCreator/tpl/forms/static/text',
    'taoMediaManager/qtiCreator/editor/styleEditor/styleEditor',
    'taoMediaManager/qtiCreator/editor/styleEditor/fontSelector',
    'taoMediaManager/qtiCreator/editor/styleEditor/colorSelector',
    'taoMediaManager/qtiCreator/editor/styleEditor/fontSizeChanger'
], function (
    $,
    stateFactory,
    Active,
    htmlEditor,
    content,
    formElement,
    formTpl,
    styleEditor,
    fontSelector,
    colorSelector,
    fontSizeChanger
) {
    'use strict';

    const wrapperCls = 'custom-text-box';

    const TextActive = stateFactory.extend(
        Active,
        function () {
            this.buildStylesEditor();
            this.buildEditor();
            this.initForm();
        },
        function () {
            this.destroyEditor();
            this.destroyStylesEditor();
            this.widget.$form.empty();
        }
    );

    TextActive.prototype.buildEditor = function () {
        const widget = this.widget;
        const $editableContainer = widget.$container;
        const container = widget.element;
        const changeCallback = content.getChangeCallback(container);

        $editableContainer.attr('data-html-editable-container', true);

        if (!htmlEditor.hasEditor($editableContainer)) {
            htmlEditor.buildEditor($editableContainer, {
                change: function (data) {
                    changeCallback.call(this, data);

                    // remove the form value if there is no content to apply on
                    if (!data) {
                        widget.$form.find('[name="textBlockCssClass"]').val('');
                    }
                },
                blur: function () {
                    widget.changeState('sleep');
                },
                data: {
                    widget: widget,
                    container: container
                }
            });
        }
    };

    TextActive.prototype.buildStylesEditor = function () {
        const widget = this.widget;
        const $editableContainer = widget.$container;

        let $block = $editableContainer.find(`.${wrapperCls}`);
        if (!$block.length) {
            // no wrapper found, insert one
            $block = widget.$container.find('[data-html-editable="true"]').wrapInner('<div />').children();
            const hashClass = styleEditor.generateHashClass();
            $block.attr('class', `${wrapperCls} ${hashClass}`);
        } else {
            const blockCls = widget.$container.find(`.${wrapperCls}`).attr('class').split(' ');
            let hashClass = blockCls.find(className => /^tao-*/.test(className));
            if (hashClass) {
                styleEditor.setHashClass(hashClass);
            } else {
                hashClass = styleEditor.generateHashClass();
                $block.addClass(hashClass);
            }
        }

        const $textEditor = $('#item-editor-text-property-bar');
        fontSelector($textEditor);
        colorSelector($textEditor);
        fontSizeChanger($textEditor);
    };

    TextActive.prototype.destroyEditor = function () {
        //search and destroy the editor
        htmlEditor.destroyEditor(this.widget.$container);
    };

    TextActive.prototype.destroyStylesEditor = function () {
        const hashClass = styleEditor.getHashClass();
        const $block = this.widget.$container.find(`.${wrapperCls}`);
        const textBlockCssClass = ($block.attr('class') || '').replace(wrapperCls, '').replace(hashClass, '').trim();
        const styles = styleEditor.getStyle();
        const regex = new RegExp(hashClass);
        if (!textBlockCssClass && !Object.keys(styles).find(selector => regex.test(selector))) {
            // no need to have a wrapper if no block class is set
            $block.children().unwrap();
        }
    };

    TextActive.prototype.initForm = function () {
        const widget = this.widget;
        const $form = widget.$form;
        const blockCls = widget.$container.find(`.${wrapperCls}`).attr('class');
        const hashClass = styleEditor.getHashClass();

        $form.html(
            formTpl({
                textBlockCssClass: (blockCls || '').replace(wrapperCls, '').replace(hashClass, '').trim()
            })
        );

        formElement.initWidget($form);

        formElement.setChangeCallbacks($form, widget.element, {
            textBlockCssClass: function (element, value) {
                let $block = widget.$container.find(`.${wrapperCls}`);

                // prevent to have the wrapper class twice
                value = value.trim();
                if (value === wrapperCls) {
                    value = '';
                }

                if (value) {
                    if (!$block.length) {
                        // no wrapper found, insert one
                        $block = widget.$container.find('[data-html-editable="true"]').wrapInner('<div />').children();
                    }

                    // replace the block class
                    $block.attr('class', `${wrapperCls} ${value}`);
                }
            }
        });
    };

    return TextActive;
});

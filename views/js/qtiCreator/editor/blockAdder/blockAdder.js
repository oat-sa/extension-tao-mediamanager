define([
    'jquery',
    'lodash',
    'tpl!taoQtiItem/qtiCreator/editor/blockAdder/tpl/addColumnRow',
    'taoQtiItem/qtiItem/core/Element',
    'taoMediaManager/qtiCreator/helper/creatorRenderer',
    'taoQtiItem/qtiCreator/model/helper/container',
    'taoQtiItem/qtiCreator/editor/gridEditor/content',
    'taoMediaManager/qtiCreator/widgets/static/text/Widget'
], function ($, _, adderTpl, Element, creatorRenderer, containerHelper, contentHelper, TextWidget) {
    'use strict';

    const _wrap = '<div class="colrow"></div>';
    const _placeholder = '<div class="placeholder">';

    /**
     * Init the block adder on the item editor panel
     *
     * @param {Object} item - standard qti js object
     * @param {JQuery} $editorPanel - the container the selector popup will be located in
     */
    function create(item, $editorPanel) {
        /**
         * Get the qti item body dom
         *
         * @returns {JQuery}
         */
        function _getItemBody() {
            return $editorPanel.find('.qti-itemBody');
        }

        /**
         * Init insertion relative to a widget container
         *
         * @param {JQuery} $widget
         */
        function _initInsertion($widget) {
            const $wrap = $(_wrap);
            let $colRow = $widget.parent('.colrow');

            //trigger event to restore all currently active widget back to sleep state
            $editorPanel.trigger('beforesave.qti-creator.active');

            if (!$colRow.length) {
                $widget.wrap(_wrap);
                $colRow = $widget.parent('.colrow');
            }
            $colRow.after($wrap);

            const $placeholder = $(_placeholder);
            $wrap.addClass('tmp').prepend($placeholder);
            _insertElement('_container', $placeholder);
        }

        /**
         * End the current insertion state
         *
         * @returns {undefined}
         */
        function _endInsertion() {
            //need to update item body
            item.body(contentHelper.getContent(_getItemBody()));
        }

        /**
         * Function to define behaviour when the insertion is completed
         *
         * @param {JQuery} $wrap
         */
        function _done($wrap) {
            //remove tmp class
            $wrap.removeClass('tmp');
            _endInsertion();
        }

        /**
         * Append the "plus" button into a widget
         *
         * @param {JQuery} $widget
         * @returns {undefined}
         */
        function _appendButton($widget) {
            //only append button to no-tmp widget and only add it once:
            if (!$widget.children('.add-block-element').length && !$widget.parent('.colrow.tmp').length) {
                const $adder = $(adderTpl());
                $widget.append($adder);
                $adder.on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const $widgetParent = $(this).parents('.widget-box');
                    _initInsertion($widgetParent);
                });
            }
        }

        $editorPanel.find('.widget-textBlock').each(function () {
            _appendButton($(this));
        });

        //bind add event
        $editorPanel.on('ready.qti-widget', function (e, _widget) {
            const qtiElement = _widget.element;

            if (
                qtiElement.is('_container')
            ) {
                let $colRow = _widget.$container.parent('.colrow');
                if ($colRow.hasClass('tmp')) {
                    _done($colRow);
                }
                if (!$colRow.length) {
                    _widget.$container.wrap(_wrap);
                }
                _appendButton(_widget.$container);
            }
        });
    }

    /**
     * Create a new qti element in place of the give $placehoder
     *
     * @param {String} qtiClass
     * @param {JQuery} $placeholder
     */
    function _insertElement(qtiClass, $placeholder) {
        //a new qti element has been added: update the model + render
        $placeholder.removeAttr('id'); //prevent it from being deleted

        $placeholder.addClass('widget-box'); //required for it to be considered as a widget during container serialization
        $placeholder.attr({
            'data-new': true,
            'data-qti-class': qtiClass
        }); //add data attribute to get the dom ready to be replaced by rendering

        const $widget = $placeholder.parent().closest('.widget-box, .qti-item');
        const $editable = $placeholder.closest('[data-html-editable], .qti-itemBody');
        const widget = $widget.data('widget');
        const element = widget.element;
        const container = Element.isA(element, '_container') ? element : element.getBody();

        if (!element || !$editable.length) {
            throw new Error('cannot create new element');
        }

        containerHelper.createElements(container, contentHelper.getContent($editable), function (newElts) {
            const creator = creatorRenderer.get();
            creator.load(function () {
                for (const serial in newElts) {
                    const elt = newElts[serial];
                    let $widgetElem;
                    let widgetElem;
                    let $colParent = $placeholder.parent();

                    elt.setRenderer(this);

                    //the text widget is "inner-wrapped" so need to build a temporary container:
                    $placeholder.replaceWith('<div class="text-block"></div>');
                    const $textBlock = $colParent.find('.text-block');
                    $textBlock.html(elt.render());

                    //build the widget
                    widgetElem = TextWidget.build(elt, $textBlock, creator.getOption('textOptionForm'), {
                        ready: function () {
                            //remove the temorary container
                            if (this.$container.parent('.text-block').length) {
                                this.$container.unwrap();
                            }
                            this.changeState('active');
                        }
                    });
                    $widgetElem = widgetElem.$container;

                    //inform height modification
                    $widgetElem.trigger('contentChange.gridEdit');
                    $widgetElem.trigger('resize.gridEdit');
                }
            }, this.getUsedClasses());
        });
    }

    return {
        create: create
    };
});

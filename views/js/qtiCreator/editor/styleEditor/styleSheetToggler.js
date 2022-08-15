/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2015 (original work) Open Assessment Technologies SA ;
 *
 */
define([
    'jquery',
    'taoMediaManager/qtiCreator/editor/styleEditor/styleEditor',
    'i18n',
    'lodash',
    'util/url',
    'taoQtiItem/qtiCreator/model/Stylesheet',
    'tpl!taoQtiItem/qtiCreator/tpl/notifications/genericFeedbackPopup',
    'ui/resourcemgr'
], function ($, styleEditor, __, _, urlUtil, Stylesheet, genericFeedbackPopup) {
    'use strict';

    var $doc = $(document);

    var styleSheetToggler = (function () {

        var init = function (itemConfig) {

            const _createInfoBox = function (data) {
                var $messageBox = $(genericFeedbackPopup(data)),
                    closeTrigger = $messageBox.find('.close-trigger');

                $('body').append($messageBox);

                closeTrigger.on('click', function () {
                    $messageBox.fadeOut(function () {
                        $(this).remove();
                    });
                });

                setTimeout(function () {
                    closeTrigger.trigger('click');
                }, 4000);

                return $messageBox;
            };

            const cssToggler = $('#style-sheet-toggler');
            const uploader = $('#stylesheet-uploader');
            const customCssToggler = $('[data-custom-css]');
            const getContext = function (trigger) {
                trigger = $(trigger);
                const li = trigger.closest('li');
                const stylesheetObj = li.data('stylesheetObj') || new Stylesheet({ href: li.data('css-res') });
                const input = li.find('.style-sheet-label-editor');
                const labelBox = input.prev('.file-label');
                const label = input.val();

                return {
                    li: li,
                    input: input,
                    label: label,
                    labelBox: labelBox,
                    isCustomCss: !!li.data('custom-css'),
                    isDisabled: li.find('.icon-preview').hasClass('disabled'),
                    stylesheetObj: stylesheetObj,
                    cssUri: stylesheetObj.attr('href')
                };
            };



            /**
             * Upload custom stylesheets
             */
            uploader.on('click', function () {

                uploader.resourcemgr({
                    className: 'stylesheets',
                    appendContainer: '#mediaManager',
                    pathParam: 'path',
                    path: 'taomedia://mediamanager/',
                    root: 'local',
                    browseUrl: urlUtil.route('getStylesheets', 'SharedStimulusStyling', 'taoMediaManager'),
                    uploadUrl: urlUtil.route('upload', 'SharedStimulusStyling', 'taoMediaManager'),
                    deleteUrl: urlUtil.route('fileDeleteUrl', 'SharedStimulusStyling', 'taoMediaManager'),
                    downloadUrl: urlUtil.route('loadStylesheet', 'SharedStimulusStyling', 'taoMediaManager'),
                    params: {
                        uri: itemConfig.id,
                        lang: itemConfig.lang,
                        filters: 'text/css'
                    },
                    select: function (e, files) {
                        let styleListNames = ['/tao-user-styles.css'];
                        let styleList = $('[data-css-res]');
                        if (styleList.length > 0) {
                            styleList.each((i, style) => {
                                let styleGroup = style.dataset && style.dataset.cssRes && style.dataset.cssRes.match(/stylesheet=(?<groupName>.+\.css)?/);
                                if (!styleGroup) {
                                    // new added files, don't have 'stylesheet=' in cssRes
                                    styleGroup = style.dataset && style.dataset.cssRes && style.dataset.cssRes.match(/(?<groupName>.+\.css)?/);
                                }
                                if (styleGroup && styleGroup.groups && styleGroup.groups.groupName) {
                                    styleListNames.push(`/${decodeURIComponent(styleGroup.groups.groupName)}`);
                                }
                            });
                        }

                        const l = files.length;
                        for (let i = 0; i < l; i++) {
                            if (styleListNames.includes(files[i].file)) {
                                _createInfoBox({
                                    message: __('A stylesheet named <b>%s</b> is already attached to the passage.').replace('%s', files[i].file.substring(1)),
                                    type: 'error'
                                });
                            } else {
                                styleEditor.addStylesheet(files[i].file, itemConfig);
                            }
                        }
                    }
                });
            });

            /**
             * Confirm to save the item
             * @param {Object} trigger
             */
            const deleteStylesheet = function(trigger) {
                var context = getContext(trigger),
                    attr = context.isDisabled ? 'disabled-href' : 'href',
                    cssLinks = $('head link');

                let cssUri = context.cssUri;
                if (context.cssUri[0] === '/') {
                    cssUri = context.cssUri.split('/').reverse()[0];
                }
                const deleteFile = context.stylesheetObj.attr('title') || cssUri;
                styleEditor.deleteStylesheet(deleteFile);

                // remove file from resourceMgr if it is cached
                const mediaManager = $('#mediaManager');
                if (mediaManager.children.length) {
                    $('#mediaManager').children().trigger('filedelete.resourcemgr', [`/${context.label}`]);
                }

                cssLinks.filter(`[${attr}*="${cssUri}"]`).remove();
                context.li.remove();


                $('.feedback-info').hide();
                _createInfoBox({
                    message: __('Style Sheet <b>%s</b> removed<br> Click <i>Add Style Sheet</i> to re-apply.').replace('%s', context.label),
                    type: 'info'
                });
            };


            /**
             * Download current stylesheet
             *
             * @param {Object} trigger
             */
            const downloadStylesheet = function(trigger) {
                styleEditor.download(getContext(trigger).stylesheetObj.attributes.href, getContext(trigger).stylesheetObj.attributes.title);
            };

            /**
             * Modify stylesheet title (save modification)
             * @param {Object} trigger
             * @returns {Boolean}
             */
            const saveLabel = function (trigger) {
                var context = getContext(trigger),
                    title = $.trim(context.input.val());

                if (!title) {
                    context.stylesheetObj.attr('title', '');
                    return false;
                }

                context.stylesheetObj.attr('title', title);
                context.input.hide();
                context.labelBox.html(title).show();
            };

            /**
             * Dis/enable style sheets
             * @param {Object} trigger
             */
            const handleAvailability = function (trigger) {
                const context = getContext(trigger);

                // custom styles are handled in a style element, not in a link
                if (context.isCustomCss || context.label === 'tao-user-styles.css') {
                    if (context.isDisabled) {
                        $('#item-editor-user-styles')[0].disabled = false;
                        customCssToggler.removeClass('not-available');
                    } else {
                        $('#item-editor-user-styles')[0].disabled = true;
                        customCssToggler.addClass('not-available');
                    }
                    // add some visual feed back to the triggers
                    $(trigger).toggleClass('disabled');
                } else {
                    // all other styles are handled via their link element
                    const myLink = $(`link[data-serial=${context.stylesheetObj.serial}`);
                    myLink.ready(() => {
                        if (context.isDisabled) {
                            myLink[0].sheet.disabled = false;
                            context.li.removeClass('not-available');
                        } else {
                            myLink[0].sheet.disabled = true;
                            context.li.addClass('not-available');
                        }
                        // add some visual feed back to the triggers
                        $(trigger).toggleClass('disabled');
                    });
                }
            };

            /**
             * Distribute click events
             */
            cssToggler.on('click', function (e) {
                var target = e.target,
                    className = target.className;

                // distribute click actions
                if (className.indexOf('icon-preview') > -1) {
                    handleAvailability(e.target);
                } else if (target.parentElement.className !== 'not-available') {
                    if (className.indexOf('icon-bin') > -1) {
                        deleteStylesheet(e.target);
                    } else if (className.indexOf('icon-download') > -1) {
                        downloadStylesheet(e.target);
                    }
                }
            });


            /**
             * Handle renaming on enter
             */
            cssToggler.on('keydown', 'input', function (e) {
                if (e.keyCode === 13) {
                    $(e.target).trigger('blur');
                }
            });

            /**
             * Handle renaming on blur
             */
            cssToggler.on('blur', 'input', function (e) {
                saveLabel(e.target);
            });


        };

        return {
            init: init
        };

    })();

    return styleSheetToggler;
});


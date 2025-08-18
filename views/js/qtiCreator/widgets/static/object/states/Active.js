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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 *
 */
define([
    'lodash',
    'jquery',
    'i18n',
    'services/features',
    'taoQtiItem/qtiCreator/widgets/states/factory',
    'taoQtiItem/qtiCreator/widgets/static/states/Active',
    'tpl!taoQtiItem/qtiCreator/tpl/forms/static/object',
    'taoQtiItem/qtiCreator/widgets/helpers/formElement',
    'taoQtiItem/qtiCreator/widgets/static/helpers/inline',
    'ui/mediaEditor/mediaEditorComponent',
    'ui/previewer',
    'ui/resourcemgr',
    'ui/tooltip'
], function (_, $, __, features, stateFactory, Active, formTpl, formElement, inlineHelper, mediaEditorComponent) {
    'use strict';
    /**
     * media Editor instance if has been initialized
     * @type {null}
     */
    let mediaEditor = null;
    let $panelObjectSize = null;
    let $panelMediaSize = null;

    const _config = {
        renderingThrottle: 1000,
        fileFilters:
            'image/jpeg,image/png,image/gif,image/svg+xml,video/mp4,video/avi,video/ogv,video/mpeg,video/ogg,video/quicktime,video/webm,video/x-ms-wmv,video/x-flv,audio/mp3,audio/vnd.wav,audio/ogg,audio/vorbis,audio/webm,audio/mpeg,application/ogg,audio/aac,application/pdf'
    };

    const ObjectStateActive = stateFactory.extend(
        Active,
        function () {
            this.initForm();
        },
        function () {
            if (mediaEditor) {
                mediaEditor.destroy();
            }
            mediaEditor = null;
            $panelObjectSize = null;
            $panelMediaSize = null;
            this.widget.$original.off('playerready');
            this.widget.$form.empty();
        }
    );

    const refreshRendering = _.throttle(function refreshRendering(widget) {
        const obj = widget.element;
        const $container = widget.$original;
        const previewOptions = {
            url: obj.renderer.resolveUrl(obj.attr('data')),
            mime: obj.attr('type')
        };
        if (obj.attr('height')) {
            previewOptions.height = obj.attr('height');
        }
        if (obj.attr('width')) {
            previewOptions.width = obj.attr('width');
        }
        if (previewOptions.url && previewOptions.mime) {
            $container.previewer(previewOptions);
        }
    }, _config.renderingThrottle);

    const setMediaSizeEditor = widget => {
        const qtiObject = widget.element;
        const type = qtiObject.attr('type');
        if (/video/.test(type)) {
            const $container = widget.$original;
            const mediaplayer = $container.data('player');
            let width = qtiObject.attr('width');
            let height = qtiObject.attr('height');
            if (!/%/.test(width)) {
                // if we don't have % width we need calculate default % width
                const originalSize = mediaplayer.getMediaOriginalSize();
                if (!originalSize.width) {
                    // video is not loaded yet
                    return;
                }
                const containerWidth = $container.closest('.widget-textBlock').width();
                // the default % and by that the size of the video is based on the original video size compared to the container size
                if (!width) {
                    width = Math.round(100 / (containerWidth / originalSize.width));
                    height = 0;
                } else if (height) {
                    // for old format (px and height is set) the default % is calculated on rendered width and height
                    const scaleHeight =
                        (Math.max(height || 0, 200) - $container.find('.mediaplayer .controls').height()) /
                        originalSize.height;
                    const scaleWidth = Math.max(width || 0, 200) / originalSize.width;
                    const scale = Math.min(scaleHeight, scaleWidth);
                    width = Math.round(100 / (containerWidth / (scale * originalSize.width)));
                    qtiObject.removeAttr('height');
                    height = 0;
                }
            }
            const onChange = _.debounce(nMedia => {
                if (qtiObject.attr('width') !== `${nMedia['width']}%`) {
                    const newWidth = `${Math.round(nMedia['width'])}%`;
                    qtiObject.attr('width', newWidth);
                    mediaplayer.resize(newWidth, 'auto');
                }
            }, 200);
            if (mediaEditor) {
                mediaEditor.destroy();
            }
            mediaEditor = mediaEditorComponent(
                $panelMediaSize,
                {
                    $node: $container.find('.mediaplayer .media'),
                    $container: $container,
                    type: qtiObject.attr('type'),
                    width,
                    height,
                    responsive: true
                },
                {
                    mediaDimension: {
                        active: true,
                        showResponsiveToggle: false
                    }
                }
            ).on('change', onChange);
        }
    };
    const hideShowPanels = type => {
        if (/video/.test(type)) {
            $panelObjectSize.hide();
            $panelMediaSize.show();
        } else {
            if (mediaEditor) {
                mediaEditor.destroy();
            }
            $panelObjectSize.show();
            $panelMediaSize.hide();
        }
    };
    const _initUpload = function (widget) {
        const $form = widget.$form,
            options = widget.options,
            qtiObject = widget.element,
            $uploadTrigger = $form.find('[data-role="upload-trigger"]'),
            $src = $form.find('input[name=src]');

        const _openResourceMgr = function _openResourceMgr() {
            $uploadTrigger.resourcemgr({
                title: __(
                    'Please select a media file from the resource manager. You can add files from your computer with the button "Add file(s)".'
                ),
                appendContainer: options.mediaManager.appendContainer,
                mediaSourcesUrl: options.mediaManager.mediaSourcesUrl,
                browseUrl: options.mediaManager.browseUrl,
                uploadUrl: options.mediaManager.uploadUrl,
                deleteUrl: options.mediaManager.deleteUrl,
                downloadUrl: options.mediaManager.downloadUrl,
                fileExistsUrl: options.mediaManager.fileExistsUrl,
                params: {
                    uri: options.uri,
                    lang: options.lang,
                    filters: _config.fileFilters
                },
                pathParam: 'path',
                path: options.mediaManager.path,
                root: options.mediaManager.root,
                select: function (e, files) {
                    let file, type;
                    if (files && files.length) {
                        file = files[0].file;
                        type = files[0].mime;
                        qtiObject.attr('type', type);
                        $src.val(file).trigger('change');
                    }
                },
                open: function () {
                    //hide tooltip if displayed
                    if ($src.data('$tooltip')) {
                        $src.blur().data('$tooltip').hide();
                    }
                },
                close: function () {
                    //triggers validation :
                    $src.blur();
                }
            });
        };

        $uploadTrigger.on('click', _openResourceMgr);

        //if empty, open file manager immediately
        if (!$src.val()) {
            _openResourceMgr();
        }
    };

    ObjectStateActive.prototype.initForm = function initForm() {
        const _widget = this.widget,
            $form = _widget.$form,
            qtiObject = _widget.element,
            baseUrl = _widget.options.baseUrl;
        const $container = _widget.$original;
        const compactAppearance = !!qtiObject.hasClass('compact-appearance');
        const isAudio = /audio/.test(qtiObject.attr('type'));
        const isCompactAppearanceAvailable = features.isVisible('taoQtiItem/creator/interaction/media/property/compactAppearance');

        $form.html(
            formTpl({
                baseUrl: baseUrl || '',
                src: qtiObject.attr('data'),
                alt: qtiObject.attr('alt'),
                height: qtiObject.attr('height'),
                width: qtiObject.attr('width'),
                isAudio,
                isCompactAppearanceAvailable,
                compactAppearance
            })
        );

        if (isAudio && compactAppearance && isCompactAppearanceAvailable){
            $container.parent().addClass('compact-appearance');
        }

        //init resource manager
        _initUpload(_widget);

        //init standard ui widget
        formElement.initWidget($form);

        $panelObjectSize = $('.size-panel', $form);
        $panelMediaSize = $('.media-size-panel', $form);
        hideShowPanels(qtiObject.attr('type'));

        $container.off('playerready').on('playerready', function () {
            setMediaSizeEditor(_widget);
            if (isCompactAppearanceAvailable) {
                if (/audio/.test(qtiObject.attr('type'))) {
                    $form.find('.compact-appearance').show();
                } else {
                    qtiObject.attr('compact-appearance', false);
                    $form.find('.compact-appearance').hide();
                    $form.find('.compact-appearance input[name="compactAppearance"]').prop("checked", false);
                    $container.parent().removeClass('compact-appearance');
                }
            }
        });

        //init data change callbacks
        formElement.setChangeCallbacks($form, qtiObject, {
            src: function (object, value) {
                if (value !== qtiObject.attr('data')) {
                    qtiObject.attr('data', value);
                    qtiObject.removeAttr('width');
                    qtiObject.removeAttr('height');
                    $form.find('input[name=width]').val('');
                    $form.find('input[name=height]').val('');
                    $container.removeData('ui.previewer');
                    hideShowPanels(qtiObject.attr('type'));
                    inlineHelper.togglePlaceholder(_widget);
                    refreshRendering(_widget);
                }
            },
            width: function (object, value) {
                const val = parseInt(value, 10);
                if (_.isNaN(val)) {
                    qtiObject.removeAttr('width');
                } else {
                    qtiObject.attr('width', val);
                }
                refreshRendering(_widget);
            },
            height: function (object, value) {
                const val = parseInt(value, 10);
                if (_.isNaN(val)) {
                    qtiObject.removeAttr('height');
                } else {
                    qtiObject.attr('height', val);
                }
                refreshRendering(_widget);
            },
            alt: function (qtiObjectAlt, value) {
                qtiObjectAlt.attr('alt', value);
            },
            align: function (qtiObjectAlign, value) {
                inlineHelper.positionFloat(_widget, value);
            },
            compactAppearance: function (object, value) {
                if(value) {
                    if(!$container.hasClass('compact-appearance')) {
                        qtiObject.addClass('compact-appearance');
                        $container.parent().addClass('compact-appearance');
                    }
                    $panelObjectSize.hide();
                } else {
                    qtiObject.removeClass('compact-appearance');
                    $container.parent().removeClass('compact-appearance');
                    $panelObjectSize.show();
                }
            }
        });
    };

    return ObjectStateActive;
});

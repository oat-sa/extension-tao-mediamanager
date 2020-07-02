/**
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
 */

/**
 * @author Juan Luis Gutierrez Dos Santos <juanluis.gutierrezdossantos@taotesting.com>
 */

define([
    'lodash',
    'jquery',
    'i18n',
    'layout/actions/binder',
    'uri',
    'layout/section',
    'core/request',
    'core/router',
    'core/logger',
    'ui/feedback',
    'ui/dialog/confirm',
    'ui/dialog/alert',
    'util/url',
    'tpl!taoMediaManager/qtiCreator/tpl/relatedItemsPopup',
    'tpl!taoMediaManager/qtiCreator/tpl/relatedItemsClassPopup',
    'tpl!taoMediaManager/qtiCreator/tpl/forbiddenClassAction',
    'css!taoMediaManagerCss/media.css'
], function (
    _,
    $,
    __,
    binder,
    uri,
    section,
    request,
    router,
    loggerFactory,
    feedback,
    confirmDialog,
    alertDialog,
    urlUtil,
    relatedItemsPopupTpl,
    relatedItemsClassPopupTpl,
    forbiddenClassActionTpl
) {
    'use strict';

    const logger = loggerFactory('taoMediaManager/manageMedia');

    binder.register('newSharedStimulus', function instanciate(actionContext) {
        const self = this;
        const classUri = uri.decode(actionContext.classUri);

        return request({
            url: self.url,
            data: JSON.stringify({ classId: classUri }),
            method: 'POST'
        })
            .then(function (response) {
                //backward compat format for jstree
                if (actionContext.tree) {
                    $(actionContext.tree).trigger('addnode.taotree', [
                        {
                            uri: uri.decode(response.data.id),
                            label: response.data.name,
                            parent: uri.decode(actionContext.classUri),
                            cssClass: 'node-instance'
                        }
                    ]);
                }

                //return format (resourceSelector)
                return {
                    uri: uri.decode(response.data.id),
                    label: response.data.name,
                    classUri: uri.decode(actionContext.classUri),
                    type: 'instance'
                };
            })
            .catch(err => {
                if (!_.isUndefined(err.message)) {
                    feedback().error(err.message);
                }
                logger.error(err);
            });
    });
    binder.register('sharedStimulusAuthoring', function sharedStimulusAuthoring(actionContext) {
        section
            .updateContentBlock('')
            .create({
                id: 'authoring',
                name: __('Authoring'),
                url: this.url,
                content: ' ',
                visible: false
            })
            .show();
        const $panel = $('#panel-authoring');
        $panel.attr('data-id', actionContext.id);
        $panel.attr('data-uri', actionContext.uri);
        // duplicate behaviour of $doc.ajaxComplete in tao/views/js/controller/backoffice.js
        // as in old way - request html from server
        router.dispatch(`${this.url}?id=${actionContext.id}`);
    });
    binder.register('deleteSharedStimulus', function remove(actionContext) {
        const self = this;
        let data = {};
        let mediaRelationsData = {};

        if (actionContext.context[0] === 'instance') {
            mediaRelationsData.sourceId = actionContext.id
        } else {
            mediaRelationsData.classId = actionContext.id
        }
        data.uri        = uri.decode(actionContext.uri);
        data.classUri   = uri.decode(actionContext.classUri);
        data.id         = actionContext.id;
        data.signature  = actionContext.signature;
        return request({
            url: urlUtil.route('relations', 'MediaRelations', 'taoMediaManager'),
            data: mediaRelationsData,
            method: 'GET',
            noToken: true
        }).then(function (responseRelated) {
            let message;
            const haveItemReferences = responseRelated.data;
            const name = $('a.clicked', actionContext.tree).text().trim();
            if (actionContext.context[0] === 'instance') {
                if (haveItemReferences.length === 0) {
                    message = `${__('Are you sure you want to delete this')} <b>${name}</b>?`;
                } else {
                    message = relatedItemsPopupTpl({
                        name,
                        number: haveItemReferences.length,
                        items: haveItemReferences
                    });
                }
            } else if (actionContext.context[0] !== 'instance') {
                if (haveItemReferences.length === 0) {
                    message = `${__('Are you sure you want to delete this class and all of its content?')}`;
                } else if (haveItemReferences.length !== 0) {
                    message = relatedItemsClassPopupTpl({
                        name,
                        number: haveItemReferences.length,
                        items: haveItemReferences
                    });
                }
            }
            return callConfirmModal(actionContext, message, self.url, data)
        }).catch(errorObject => {
            let message;
            if (actionContext.context[0] === 'class' && errorObject.response.code === 999) {
                message = forbiddenClassActionTpl();
            }
            return callAlertModal(actionContext, message)
        });
    });

    function callConfirmModal(actionContext, message, url, data) {
        return confirmDialog(
            message,
            () => accept(actionContext, url, data),
            () => cancel
        );
    }

    function callAlertModal(actionContext, message) {
        return alertDialog(
            message,
            () => cancel
        );
    }

    function accept(actionContext, url, data) {
        return new Promise(function (resolve, reject) {
            return request({
                url: url,
                method: 'POST',
                data: data,
                dataType: 'json'
            }).then(function (response) {
                if (response.success && response.deleted) {
                    feedback().success(response.message || __('Resource deleted'));

                    if (actionContext.tree) {
                        $(actionContext.tree).trigger('removenode.taotree', [
                            {
                                id: actionContext.uri || actionContext.classUri
                            }
                        ]);
                    }
                    return resolve({
                        uri: actionContext.uri || actionContext.classUri
                    });
                } else {
                    reject(response.msg || __('Unable to delete the selected resource'));
                }
            });
        });
    }

    function cancel() {
        return new Promise(function (resolve, reject) {
            reject({ cancel: true });
        });
    }
});


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
    'jquery',
    'i18n',
    'layout/actions/binder',
    'ui/previewer',
    'util/url',
    'core/dataProvider/request',
], function($, __, binder, previewer, urlUtil, request) {


    const manageMediaController =  {

        /**
         * Controller entry point
         */
        start() {

            const $previewer = $('.previewer');
            const file = {};
            file.url = $previewer.data('url');
            file.mime = $previewer.data('type');

            if (!$previewer.data('xml')) {
                $previewer.previewer(file);
            } else{
                request(file.url, { xml:true }, "POST")
                    .then(function(response){
                        file.xml = response;
                        $previewer.previewer(file);
                    });
            }

            if (file.mime !== 'application/qti+xml') {
                $('#media-authoring').hide();
            } else {
                $('#media-authoring').show();
            }

            $('#edit-media').off()
                .on('click', function() {
                    const action = {binding : "load", url: urlUtil.route('editMedia', 'MediaImport', 'taoMediaManager')};
                    binder.exec(action, {classUri : $(this).data('classuri'), id : $(this).data('uri')} || this._resourceContext);
                });
        }
    };

    return manageMediaController;
});

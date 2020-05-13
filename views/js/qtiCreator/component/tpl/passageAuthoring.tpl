<div id="passage-authoring-scope" data-content-target="wide">

    <nav class="action-bar plain content-action-bar horizontal-action-bar">
        <ul class="menu-left action-group plain item-editor-menu"></ul>

        <ul class="menu action-group plain item-editor-menu"></ul>

        <ul class="menu-right action-group plain item-editor-menu">
            <!--

            <li id="appearance-trigger" class="btn-info small rgt">
                <span class="li-inner">
                    <span class="icon-item"></span>
                    <span class="icon-style"></span>
                    <span class="menu-label" data-item="{{__ 'Passage Properties'}}"
                          data-style="{{__ 'Style Editor'}}">{{__ 'Style Editor'}}</span>
                </span>
            </li>

            -->
        </ul>
    </nav>
    <div class="wrapper clearfix content sidebar-popup-parent" id="item-editor-wrapper">

        <!-- interaction panel -->
        <div class="item-editor-sidebar-wrapper left-bar">
            <form class="item-editor-sidebar" id="item-editor-interaction-bar" autocomplete="off"></form>
        </div>

        <!-- item panel -->
        <main id="item-editor-panel" class="clearfix">

            <div class="item-editor-bar">
                <h1 class="truncate"></h1>
                <div id="toolbar-top"></div>
            </div>

            <div id="item-editor-scoll-container">
                <div id="item-editor-scroll-outer">
                    <div id="item-editor-scroll-inner">
                        <!-- item goes here -->
                    </div>
                </div>
            </div>

        </main>

        <!-- properties panel -->
        <div class="item-editor-sidebar-wrapper right-bar sidebar-popup-parent">
            <div class="item-editor-sidebar" id="item-editor-item-widget-bar">
                <div class="item-editor-item-related sidebar-right-section-box" id="item-editor-item-property-bar" style="display: block">
                    <section class="tool-group clearfix" id="sidebar-right-item-properties">
                        <h2>{{__ 'Passage Properties'}}</h2>

                        <div class="panel" style="display: block">
                            <label for="xml:lang">
                                {{__ 'Language'}}
                            </label>
                            <span class="icon-help tooltipstered" data-tooltip="~ .tooltip-content:first" data-tooltip-theme="info"></span>
                            <span class="tooltip-content">{{__ 'Define asset language'}}</span>
                            <div class="select2-container select2" id="s2id_autogen2" style="width: 100%;">
                                <a href="javascript:void(0)" class="select2-choice" tabindex="-1">
                                    <span class="select2-chosen" id="select2-chosen-3">English</span>
                                    <abbr class="select2-search-choice-close"></abbr>
                                    <span class="select2-arrow" role="presentation"><b role="presentation"></b></span>
                                </a>
                                <label for="s2id_autogen3" class="select2-offscreen"></label>
                                <input class="select2-focusser select2-offscreen" type="text" aria-haspopup="true" role="button" aria-labelledby="select2-chosen-3" id="s2id_autogen3">
                                <div class="select2-drop select2-display-none">
                                    <div class="select2-search select2-search-hidden select2-offscreen">
                                        <label for="s2id_autogen3_search" class="select2-offscreen"></label>
                                        <input type="text" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" class="select2-input" role="combobox" aria-expanded="true" aria-autocomplete="list" aria-owns="select2-results-3" id="s2id_autogen3_search" placeholder="">
                                    </div>
                                    <ul class="select2-results" role="listbox" id="select2-results-3"></ul>
                                </div>
                            </div>
                            <select name="xml:lang" class="select2 select2-offscreen" data-has-search="false" tabindex="-1" title="">
                                <option value="da-DK">Danish</option>
                                <option value="de-DE">German</option>
                                <option value="el-GR">Greek</option>
                                <option value="en-GB">British English</option>
                                <option value="en-US" selected="selected">English</option>
                                <option value="es-ES">Spanish</option>
                                <option value="es-MX">Mexican Spanish</option>
                                <option value="fr-CA">French Canadian</option>
                                <option value="fr-FR">French</option>
                                <option value="is-IS">Icelandic</option>
                                <option value="it-IT">Italian</option>
                                <option value="ja-JP">Japanese</option>
                                <option value="lt-LT">Lithuanian</option>
                                <option value="nl-BE">Flemish</option>
                                <option value="nl-NL">Dutch</option>
                                <option value="pt-PT">Portuguese</option>
                                <option value="ru-RU">Russian</option>
                                <option value="sv-SE">Swedish</option>
                                <option value="uk-UA">Ukrainian</option>
                                <option value="zh-CN">Simplified Chinese from China</option>
                                <option value="zh-TW">Traditional Chinese from Taiwan</option>
                            </select>
                        </div>
                    </section>
                </div>
                <div class="item-editor-item-related sidebar-right-section-box" id="item-editor-text-property-bar">
                    <section class="tool-group clearfix" id="sidebar-right-text-block-properties">
                        <h2>{{__ 'Passage Properties'}}</h2>

                        <div class="panel">
                            <label for="">{{__ 'Text Block CSS Class'}}</label>
                            <span class="icon-help tooltipstered" data-tooltip="~ .tooltip-content:first" data-tooltip-theme="info"></span>
                            <div class="tooltip-content">{{__ 'Set a CSS class name in order to customize the display style'}}</div>
                            <input type="text" name="identifier" value="astronomy" placeholder="e.g. my-item_123456" data-validate="$notEmpty; $qtiIdentifier; $availableIdentifier(serial=item_5eb93041e9fa0418550555);">
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <!-- /properties panel -->


    </div>
    <!-- preview: item may needed to be saved before -->
    <div class="preview-modal-feedback modal">
        <div class="modal-body clearfix">
            <p>{{__ 'The item needs to be saved before it can be previewed'}}</p>

            <div class="rgt">
                <button class="btn-regular small cancel" type="button">{{__ 'Cancel'}}</button>
                <button class="btn-info small save" type="button">{{__ 'Save'}}</button>
            </div>
        </div>
    </div>

    <div id="mediaManager"></div>
    <div id="modal-container"></div>
</div>
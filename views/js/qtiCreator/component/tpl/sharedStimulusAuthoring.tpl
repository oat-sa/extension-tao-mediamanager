<div id="item-editor-scope" data-content-target="wide">

    <nav class="action-bar plain content-action-bar horizontal-action-bar">
        <ul class="menu-left action-group plain item-editor-menu"></ul>

        <ul class="menu action-group plain item-editor-menu"></ul>

        <ul class="menu-right action-group plain item-editor-menu">
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
                <div class="item-editor-item-related sidebar-right-section-box" id="item-editor-item-property-bar">
                    <section class="tool-group clearfix" id="sidebar-right-css-manager" style="display: none;">

                        <h2>{{__ 'Style Sheet Manager'}}</h2>

                        <div class="panel">

                            <ul class="none" id="style-sheet-toggler">
                                <!-- TAO style sheet -->
                                <li data-css-res="taoQtiItem/views/css/themes/default.css" data-custom-css="custom-css">
                                    <span class="icon-preview style-sheet-toggler"
                                        title="{{__ 'Disable this stylesheet temporarily'}}"></span>
                                    <span>{{__ 'TAO default styles'}}</span>
                                </li>

                            </ul>
                            <button id="stylesheet-uploader" class="btn-info small block">{{__ 'Add Style Sheet'}}</button>
                        </div>
                    </section>
                    <section class="tool-group clearfix" id="sidebar-right-passage-properties">
                        <h2>{{__ 'Passage Properties'}}</h2>
                        <div class="panel"></div>
                    </section>
                    <section class="tool-group clearfix" id="sidebar-right-style-editor">

                        <h2>{{__ 'Passage Style'}}</h2>

                        <div class="panel color-picker-panel">
                            <div class="item-editor-color-picker sidebar-popup-container-box">
                                <div class="color-picker-container sidebar-popup">
                                    <div class="sidebar-popup-title">
                                        <h3 class="color-picker-title"></h3>
                                        <a class="closer" href="#" data-close="#color-picker-container"></a>
                                    </div>
                                    <div class="sidebar-popup-content">
                                        <div class="color-picker"></div>
                                        <input class="color-picker-input" type="text" value="#000000">
                                    </div>
                                </div>
                                <div class="reset-group">
                                    <div class="clearfix">
                                        <label for="initial-bg" class="truncate">{{__ 'Background color'}}</label>
                                        <button class="icon-eraser reset-button" data-value="background-color"
                                            aria-label="{{__ 'Remove custom background color'}}"></button>
                                        <button class="color-trigger" id="initial-bg" data-value="background-color"
                                            data-target="body div.qti-item .mainClass" data-additional="padding:20px"></button>
                                    </div>
                                    <div class="clearfix">
                                        <label for="initial-color" class="truncate">{{__ 'Text color'}}</label>
                                        <button class="icon-eraser reset-button" data-value="color"
                                              aria-label="{{__ 'Remove custom text color'}}"></button>
                                        <button class="color-trigger" id="initial-color" data-value="color"
                                              data-target="body div.qti-item .mainClass *"></button>
                                    </div>
                                    <div class="clearfix">
                                        <label for="initial-color" class="truncate">{{__ 'Border color'}}</label>
                                        <button class="icon-eraser reset-button" data-value="color"
                                              aria-label="{{__ 'Remove custom border color'}}"></button>
                                        <button class="color-trigger" id="initial-color" data-value="border-color"
                                              data-target="body div.qti-item .mainClass" data-additional="border-width:4px;border-style:solid;padding:20px"></button>
                                    </div>
                                    <div class="clearfix">
                                        <label for="initial-color" class="truncate">{{__ 'Table headings'}}</label>
                                        <button class="icon-eraser reset-button" data-value="color"
                                              aria-label="{{__ 'Remove custom background color'}}"></button>
                                        <button class="color-trigger" id="initial-color" data-value="background-color"
                                              data-target="body div.qti-item .mainClass table th"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="panel">

                            <div>{{__ 'Font family'}}</div>

                            <div class="reset-group">
                                <select
                                    data-target="body div.qti-item .mainClass *"
                                    id="item-editor-font-selector"
                                    data-has-search="false"
                                    data-placeholder="{{__ 'Default'}}"
                                    class="select2 has-icon"
                                    data-role="font-selector"></select>
                                <button class="icon-eraser reset-button" data-role="font-selector-reset"
                                      aria-label="{{__ 'Remove custom font family'}}"></button>
                            </div>

                        </div>
                        <div class="panel">
                            <div>{{__ 'Font size'}}</div>
                            <div class="reset-group">
                                <span id="item-editor-font-size-changer" data-target="body div.qti-item .mainClass *">
                                    <button data-action="reduce" aria-label="{{__ 'Reduce font size'}}"
                                        class="icon-smaller"></button>
                                    <button data-action="enlarge" aria-label="{{__ 'Enlarge font size'}}"
                                        class="icon-larger"></button>
                                </span>

                                <span id="item-editor-font-size-manual-input" class="item-editor-unit-input-box">
                                    <input type="text" class="item-editor-font-size-text has-icon"
                                            placeholder="{{__ 'e.g. 13'}}">
                                    <span class="unit-indicator">px</span>
                                </span>
                                <button class="icon-eraser reset-button" data-role="font-size-reset"
                                      aria-label="{{__ 'Remove custom font size'}}"></button>
                            </div>

                        </div>

                    </section>
                </div>
                <div class="item-editor-item-related sidebar-right-section-box" id="item-editor-text-property-bar">
                    <section class="tool-group clearfix" id="sidebar-right-style-editor">

                        <h2>{{__ 'Text Block Style'}}</h2>

                        <div class="panel color-picker-panel">
                            <div class="item-editor-color-picker sidebar-popup-container-box">
                                <div class="color-picker-container sidebar-popup">
                                    <div class="sidebar-popup-title">
                                        <h3 id="color-picker-title"></h3>
                                        <a class="closer" href="#" data-close="#color-picker-container"></a>
                                    </div>
                                    <div class="sidebar-popup-content">
                                        <div class="color-picker"></div>
                                        <input class="color-picker-input" type="text" value="#000000">
                                    </div>
                                </div>
                                <div class="reset-group">
                                    <div class="clearfix">
                                        <label for="initial-bg" class="truncate">{{__ 'Background color'}}</label>
                                        <button class="icon-eraser reset-button" data-value="background-color"
                                              aria-label="{{__ 'Remove custom background color'}}"></button>
                                        <button class="color-trigger" id="initial-bg" data-value="background-color"
                                              data-target="body div.qti-item .mainClass .custom-text-box.hashClass *" data-additional="padding:20px;margin-bottom: 0;"></button>
                                    </div>
                                    <div class="clearfix">
                                        <label for="initial-color" class="truncate">{{__ 'Text color'}}</label>
                                        <button class="icon-eraser reset-button" data-value="color"
                                              aria-label="{{__ 'Remove custom text color'}}"></button>
                                        <button class="color-trigger" id="initial-color" data-value="color"
                                              data-target="body div.qti-item .mainClass .custom-text-box.hashClass *"></button>
                                    </div>
                                    <div class="clearfix">
                                        <label for="initial-color" class="truncate">{{__ 'Border color'}}</label>
                                        <button class="icon-eraser reset-button" data-value="color"
                                              aria-label="{{__ 'Remove custom border color'}}"></button>
                                        <button class="color-trigger" id="initial-color" data-value="border-color"
                                              data-target="body div.qti-item .mainClass .custom-text-box.hashClass *" data-additional="border-width:4px;border-style:solid;padding:20px"></button>
                                    </div>
                                    <div class="clearfix">
                                        <label for="initial-color" class="truncate">{{__ 'Table headings'}}</label>
                                        <button class="icon-eraser reset-button" data-value="color"
                                              aria-label="{{__ 'Remove custom background color'}}"></button>
                                        <button class="color-trigger" id="initial-color" data-value="background-color"
                                              data-target="body div.qti-item .mainClass .custom-text-box.hashClass table th"></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="panel">

                            <div>{{__ 'Font family'}}</div>

                            <div class="reset-group">
                                <select
                                    data-target="body div.qti-item .mainClass .custom-text-box.hashClass .custom-text-box.hashClass *"
                                    id="item-editor-font-selector"
                                    data-has-search="false"
                                    data-placeholder="{{__ 'Default'}}"
                                    class="select2 has-icon"
                                    data-role="font-selector"></select>
                                <button class="icon-eraser reset-button" data-role="font-selector-reset"
                                      aria-label="{{__ 'Remove custom font family'}}"></button>
                            </div>

                        </div>
                        <div class="panel">
                            <div>{{__ 'Font size'}}</div>
                            <div class="reset-group">
                                        <span id="item-editor-font-size-changer" data-target="body div.qti-item .mainClass .custom-text-box.hashClass *">
                                            <button data-action="reduce" aria-label="{{__ 'Reduce font size'}}"
                                               class="icon-smaller"></button>
                                            <button data-action="enlarge" aria-label="{{__ 'Enlarge font size'}}"
                                               class="icon-larger"></button>
                                        </span>

                                <span id="item-editor-font-size-manual-input" class="item-editor-unit-input-box">
                                            <input type="text" class="item-editor-font-size-text has-icon"
                                                   placeholder="{{__ 'e.g. 13'}}">
                                                <span class="unit-indicator">px</span>
                                        </span>
                                <button class="icon-eraser reset-button" data-role="font-size-reset"
                                      aria-label="{{__ 'Remove custom font size'}}"></button>
                            </div>

                        </div>

                    </section>
                    <section class="tool-group clearfix" id="sidebar-right-text-block-properties">
                        <h2>{{__ 'Text Block Properties'}}</h2>

                        <div class="panel"></div>
                    </section>
                </div>
                <div class="item-editor-body-element-related sidebar-right-section-box" id="item-editor-body-element-property-bar">
                    <section class="tool-group clearfix" id="sidebar-right-body-element-properties">
                        <h2><?= __('Element Properties') ?></h2>

                        <div class="panel"></div>
                    </section>
                </div>
            </div>
        </div>
        <!-- /properties panel -->


    </div>

    <div id="mediaManager"></div>
    <div id="modal-container"></div>
</div>

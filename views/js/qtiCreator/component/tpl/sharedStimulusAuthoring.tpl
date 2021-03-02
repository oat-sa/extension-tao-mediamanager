<div id="item-editor-scope" data-content-target="wide">

    <nav class="action-bar plain content-action-bar horizontal-action-bar">
        <ul class="menu-left action-group plain item-editor-menu"></ul>

        <ul class="menu action-group plain item-editor-menu"></ul>

        <ul class="menu-right action-group plain item-editor-menu">
            <li id="appearance-trigger" class="btn-info small rgt">
                <span class="li-inner">
                    <span class="icon-item"></span>
                    <span class="icon-style"></span>
                    <span class="menu-label" data-item="{{__ 'Item properties'}}"
                          data-style="{{__ 'Style Editor'}}">{{__ 'Style Editor'}}</span>
                </span>
            </li>
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
                <div class="item-editor-item-related sidebar-right-section-box" id="item-style-editor-bar">
                    <section class="tool-group clearfix" id="sidebar-right-style-editor">

                        <h2>{{__ 'Style Editor'}}</h2>

                        <div class="panel color-picker-panel">
                            <div class="item-editor-color-picker sidebar-popup-container-box">
                                <div class="color-picker-container sidebar-popup">
                                    <div class="sidebar-popup-title">
                                        <h3 id="color-picker-title"></h3>
                                        <a class="closer" href="#" data-close="#color-picker-container"></a>
                                    </div>
                                    <div class="sidebar-popup-content">
                                        <div class="color-picker"></div>
                                        <input id="color-picker-input" type="text" value="#000000">
                                    </div>
                                </div>
                                <div class="reset-group">
                                    <div class="clearfix">
                                        <label for="initial-bg" class="truncate">{{__ 'Background color'}}</label>
                                        <span class="icon-eraser reset-button" data-value="background-color"
                                              title="{{__ 'Remove custom background color'}}"></span>
                                        <span class="color-trigger" id="initial-bg" data-value="background-color"
                                              data-target="body div.qti-item"></span>
                                    </div>
                                    <div class="clearfix">
                                        <label for="initial-color" class="truncate">{{__ 'Text color'}}</label>
                                        <span class="icon-eraser reset-button" data-value="color"
                                              title="{{__ 'Remove custom text color'}}"></span>
                                        <span class="color-trigger" id="initial-color" data-value="color"
                                              data-target="body div.qti-item"></span>
                                    </div>
                                    <div class="clearfix">
                                        <label for="initial-color" class="truncate">{{__ 'Border color'}}</label>
                                        <span class="icon-eraser reset-button" data-value="color"
                                              title="{{__ 'Remove custom border color'}}"></span>
                                        <span class="color-trigger" id="initial-color" data-value="border-color"
                                              data-target="body div.qti-item .solid,body div.qti-item .matrix, body div.qti-item table.matrix th, body div.qti-item table.matrix td"></span>
                                    </div>
                                    <div class="clearfix">
                                        <label for="initial-color" class="truncate">{{__ 'Table headings'}}</label>
                                        <span class="icon-eraser reset-button" data-value="color"
                                              title="{{__ 'Remove custom background color'}}"></span>
                                        <span class="color-trigger" id="initial-color" data-value="background-color"
                                              data-target="body div.qti-item .matrix th"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="panel">

                            <div>{{__ 'Font family'}}</div>

                            <div class="reset-group">
                                <select
                                    data-target="body div.qti-item"
                                    id="item-editor-font-selector"
                                    data-has-search="false"
                                    data-placeholder="{{__ 'Default'}}"
                                    class="select2 has-icon"
                                    data-role="font-selector"></select>
                                <span class="icon-eraser reset-button" data-role="font-selector-reset"
                                      title="{{__ 'Remove custom font family'}}"></span>
                            </div>

                        </div>
                        <div class="panel">
                            <div>{{__ 'Font size'}}</div>
                            <div class="reset-group">
                                        <span id="item-editor-font-size-changer" data-target="body div.qti-item">
                                            <a href="#" data-action="reduce" title="{{__ 'Reduce font size'}}"
                                               class="icon-smaller"></a>
                                            <a href="#" data-action="enlarge" title="{{__ 'Enlarge font size'}}"
                                               class="icon-larger"></a>
                                        </span>

                                <span id="item-editor-font-size-manual-input" class="item-editor-unit-input-box">
                                            <input type="text" id="item-editor-font-size-text" class="has-icon"
                                                   placeholder="{{__ 'e.g. 13'}}">
                                                <span class="unit-indicator">px</span>
                                        </span>
                                <span class="icon-eraser reset-button" data-role="font-size-reset"
                                      title="{{__ 'Remove custom font size'}}"></span>
                            </div>

                        </div>

                    </section>

                </div>
                <div class="item-editor-item-related sidebar-right-section-box" id="item-editor-text-property-bar">
                    <section class="tool-group clearfix" id="sidebar-right-text-block-properties">
                        <h2>{{__ 'Passage Properties'}}</h2>
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
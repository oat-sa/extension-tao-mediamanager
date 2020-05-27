<div id="passage-authoring-scope" data-content-target="wide">

    <nav class="action-bar plain content-action-bar horizontal-action-bar">
        <ul class="menu-left action-group plain item-editor-menu"></ul>

        <ul class="menu action-group plain item-editor-menu"></ul>

        <ul class="menu-right action-group plain item-editor-menu"></ul>
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
                    <section class="tool-group clearfix" id="sidebar-right-item-properties">
                        <h2>{{__ 'Passage Properties'}}</h2>
                        <div class="panel"></div>
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
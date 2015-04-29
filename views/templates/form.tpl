<?php
use oat\tao\helpers\Template;
Template::inc('form_context.tpl', 'tao');
?>
    <div class="main-container flex-container-main-form">
        <header class="section-header flex-container-full">
            <h2><?=get_data('formTitle')?></h2>
        </header>
        <div class="form-content">
            <?=get_data('myForm')?>
        </div>
    </div>
    <div class="data-container-wrapper flex-container-remaining">
        <header class="section-header flex-container-full">
            <h2><?=__('Preview')?></h2>
        </header>
        <?php if(strpos(get_data('mimeType'), 'image') === 0):?>
            <img src="data:<?=get_data('mimeType')?>;base64,<?=get_data('base64Data')?>" alt="preview"/>
        <?php else:?>
            <?php if(strpos(get_data('mimeType'), 'video') === 0):?>
            <video width="320" height="240" controls>
                <source src="data:<?=get_data('mimeType')?>;base64,<?=get_data('base64Data')?>" type="<?=get_data('mimeType')?>">
                Your browser does not support the video tag.
            </video>
            <?php endif;?>
            <?php if(has_data('data')):?>
                <pre><?=get_data('data')?></pre>
            <?php endif;?>
        <?php endif;?>
    </div>

<?php Template::inc('footer.tpl', 'tao'); ?>

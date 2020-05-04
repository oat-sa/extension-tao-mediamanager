<?php
use oat\tao\helpers\Template;
Template::inc('form_context.tpl', 'tao');
?>
<div class="main-container flex-container-main-form">
    <?php if(has_data('error')):?>
    <div class="feedback-error small">
        <span class="icon-error small"></span>
        <?=get_data('error')?>
    </div>
    <?php endif;?>
</div>
<?php Template::inc('footer.tpl', 'tao'); ?>
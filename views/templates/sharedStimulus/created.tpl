<?php
use oat\tao\helpers\Template;
Template::inc('form_context.tpl', 'tao');
?>
<link rel="stylesheet" href="../../../../index.php"/>
    <div class="main-container flex-container-main-form">
        <?php if(has_data('error')):?>
            <div class="feedback-error small">
                <span class="icon-error small"></span>
                <?=get_data('error')?>
            </div>
        <?php endif;?>

        <?php if(has_data('redirectUrl')):?>
            <script type="text/javascript">
                window.location = "<?=get_data('redirectUrl')?>";
            </script>
        <?php endif;?>

    </div>
<?php Template::inc('footer.tpl', 'tao'); ?>

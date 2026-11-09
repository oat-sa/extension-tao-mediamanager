<?php
use oat\tao\helpers\Template;

Template::inc('form_context.tpl', 'tao');
?>
<div id="panel-authoring" class="flex-container-main-form"></div>
<?php if (!empty(get_data('wproofreaderRequireConfig'))) : ?>
<script>
requirejs.config({
    config: <?= json_encode(get_data('wproofreaderRequireConfig'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
});
</script>
<?php endif; ?>
<?php
Template::inc('footer.tpl', 'tao');

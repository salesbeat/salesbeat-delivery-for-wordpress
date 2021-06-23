<?php
/**
 * @var array $arDeliveryInfo
 * @var object $method
 * @var array $arItems
 */
?>
<div data-cache="sb_<?= time() ?>">
    <?php if (!empty($arDeliveryInfo)): ?>
        <div id="sb-cart-widget"></div>
        <div id="sb-cart-widget-result">
            <?php
            foreach ($arDeliveryInfo as $field):
                if (empty($field['value']) && !is_numeric($field['value'])) continue;
                ?>
                <p><span class="salesbeat-summary-label"><?= $field['name']; ?>:</span> <?= $field['value']; ?></p>
            <?php endforeach; ?>

            <p><a href="" class="sb-reshow-cart-widget"><?= __('Изменить данные доставки', 'salesbeat'); ?></a></p>
        </div>
    <?php else: ?>
        <div id="sb-cart-widget"></div>
        <div id="sb-cart-widget-result"></div>
    <?php endif; ?>
</div>

<div data-shipping-init
     data-name="<?= $method->id; ?>"
     data-token="<?= get_option('salesbeat_system_api_token') ?>"
     data-city-code=""
     data-products="<?= esc_attr(wp_json_encode($arItems)); ?>"></div>

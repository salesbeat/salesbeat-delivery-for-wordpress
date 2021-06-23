<?php
/**
 * Plugin Name: Salesbeat - Один модуль для всех служб доставки
 * Plugin URI: https://salesbeat.pro/
 * Description: Плагин позволяет выводить виджет расчета доставок на странице оформления заказа и в товарной карточке.
 * Version: 1.1.9
 * Author: Salesbeat
 * Author URI: https://salesbeat.pro
 */

defined('ABSPATH') || exit;

$actPlugins = apply_filters('active_plugins', get_option('active_plugins'));
if (!in_array('woocommerce/woocommerce.php', $actPlugins))
    exit;

if (!defined('SALESBEAT_PLUGIN_FILE'))
    define('SALESBEAT_PLUGIN_FILE', __FILE__);

define('SALESBEAT_PLUGIN_NAME', basename(__DIR__));

// Подключаем lib классы Salesbeat
include __DIR__ . '/lib/api.class.php';
include __DIR__ . '/lib/http.class.php';
include __DIR__ . '/lib/storage.class.php';
include __DIR__ . '/lib/tools.class.php';

// Взаимодействие с плагином
include __DIR__ . '/includes/plugin.class.php';
register_activation_hook(__FILE__, ['Salesbeat\Inc\Plugin', 'activate']); // При активации плагина
register_deactivation_hook(__FILE__, ['Salesbeat\Inc\Plugin', 'deactivate']); // При деактивации плагина
register_uninstall_hook(__FILE__, ['Salesbeat\Inc\Plugin', 'uninstall']); // При удалении плагина
add_filter('plugin_action_links', ['Salesbeat\Inc\Plugin', 'settingsLink'], 10, 4);

// Inc
include __DIR__ . '/includes/adminOrdersTable.class.php';
include __DIR__ . '/includes/adminOrders.class.php';
include __DIR__ . '/includes/adminSettings.class.php';
include __DIR__ . '/includes/adminMenu.class.php';
include __DIR__ . '/includes/ajax.class.php';
include __DIR__ . '/includes/product.class.php';
include __DIR__ . '/includes/adminOrderShipping.class.php';

function shippingMethodInit()
{
    include __DIR__ . '/includes/shipping.class.php';

    add_filter('woocommerce_shipping_methods', ['Salesbeat\Inc\Shipping', 'shippingMethod']);
    add_action('woocommerce_after_shipping_rate', ['Salesbeat\Inc\Shipping', 'afterShippingRate']);
    add_filter('woocommerce_available_payment_gateways', ['Salesbeat\Inc\Shipping', 'paymentsOnShipping']);
}

add_action('woocommerce_shipping_init', 'shippingMethodInit');

// Валидация доставки
function validationShipping()
{
    $chosenShipping = WC()->session->get('chosen_shipping_methods');
    $chosenList = explode(':', $chosenShipping[0]);

    $storage = \Salesbeat\Lib\Storage::main()->getByID('public');
    if (!$storage && $chosenList[0] == 'salesbeat')
        wc_add_notice(__('Пожалуйста, уточните способ доставки'), 'error');
}

add_action('woocommerce_checkout_process', 'validationShipping');

// Добавляем контейнер Salesbeat в информациюю о заказе
add_action('add_meta_boxes', [new Salesbeat\Inc\AdminOrderShipping, 'addMetaBoxes']);

// Добавляем скрипты и стили в паблик
function outputHead()
{
    wp_enqueue_style('sbPublicShipping', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/css/sbPublicShipping.css'), [], '1.0.0');
    wp_enqueue_style('sbPublicProduct', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/css/sbPublicProduct.css'), [], '1.0.0');

    wp_enqueue_script('sbWidget', '//app.salesbeat.pro/static/widget/js/widget.js');
    wp_enqueue_script('sbCartWidget', '//app.salesbeat.pro/static/widget/js/cart_widget.js');
    wp_enqueue_script('sbTools', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/js/sbTools.js'));
    wp_enqueue_script('sbPublicShipping', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/js/sbPublicShipping.js'));
    wp_enqueue_script('sbPublicProduct', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/js/sbPublicProduct.js'));

    wp_localize_script('sbTools', 'sbAjaxOption', ['url' => admin_url('admin-ajax.php')]);
}

add_action('wp_enqueue_scripts', 'outputHead');

// Добавляем скрипты и стили в админку
function outputHeadAdmin()
{
    wp_enqueue_style('sbAdminSettings', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/css/sbAdminSettings.css'), [], '1.0.0');
    wp_enqueue_style('sbAdminOrders', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/css/sbAdminOrders.css'), [], '1.0.0');

    wp_enqueue_script('sbTools', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/js/sbTools.js'), [], '1.0.0');
    wp_enqueue_script('sbAdminSettings', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/js/sbAdminSettings.js'), [], '1.0.0');
    wp_enqueue_script('sbAdminOrders', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/js/sbAdminOrders.js'), [], '1.0.0');
    wp_enqueue_script('sbAdminSettings', plugins_url(SALESBEAT_PLUGIN_NAME . '/assets/js/sbAdminSettings.js'), [], '1.0.0');

    wp_localize_script('sbTools', 'sbAjaxOption', ['url' => admin_url('admin-ajax.php')]);
}

add_action('admin_enqueue_scripts', 'outputHeadAdmin');

// Подключаем Ajax обработчики
add_action('wp_ajax_nopriv_sbCallBack', [new Salesbeat\Inc\Ajax, 'callBack']);
add_action('wp_ajax_nopriv_sbGetProductInfo', [new Salesbeat\Inc\Ajax, 'getProductInfo']);
add_action('wp_ajax_sbCallBack', [new Salesbeat\Inc\Ajax, 'callBack']);
add_action('wp_ajax_sbGetProductInfo', [new Salesbeat\Inc\Ajax, 'getProductInfo']);
add_action('wp_ajax_sbSendOrder', [new Salesbeat\Inc\Ajax, 'sendOrder']);
add_action('wp_ajax_sbSyncPaySystem', [new Salesbeat\Inc\Ajax, 'syncPaySystem']);

// Скрываем свойства в заказе
function hiddenCheckoutFields($fields)
{
    $fieldsHidden = get_option('salesbeat_property_checkout_hidden');
    if (!$fieldsHidden || !is_array($fieldsHidden)) return $fields;

    foreach ($fieldsHidden as $fieldHidden) {
        $fields['billing'][$fieldHidden]['required'] = false;
        unset($fields['billing'][$fieldHidden]);

        if ($fieldHidden = 'billing_country')
            unset($fields['shipping']['shipping_country']);
    }

    return $fields;
}

add_filter('woocommerce_checkout_fields', 'hiddenCheckoutFields');

// Изменяем заказ и удаляем транзит
function beforeCheckoutCreateOrder($order, $data) {
    $storage = \Salesbeat\Lib\Storage::main()->getByID('public');
    \Salesbeat\Lib\Storage::main()->deleteById('public');

    if (empty($storage)) return false;

    // Добавляем мета данные в персональное свойство
    $order->update_meta_data('_shipping_salesbeat', $storage);

    // Обновляем мета данные адреса
    if (!empty($storage['pvz_address'])) {
        $order->set_shipping_address_1($storage['pvz_address']);
        $order->set_billing_address_1($storage['pvz_address']);
    } else if (!empty($storage['street']) || !empty($storage['house'])) {
        $address = '';
        $address .= !empty($storage['street']) ? __('ул. ', 'salesbeat') . $storage['street'] : '';
        $address .= !empty($storage['street']) && !empty($storage['house']) ? ', ' : '';
        $address .= !empty($storage['house']) ? __('д. ', 'salesbeat') . $storage['house'] : '';
        $address .= !empty($storage['house_block']) ? __(' корпус ', 'salesbeat') . $storage['house_block'] : '';
        $address .= !empty($storage['flat']) ? __(', кв. ', 'salesbeat') . $storage['flat'] : '';

        $order->set_shipping_address_1($address);
        $order->set_billing_address_1($address);
    }

    // Обновляем мета данные города
    if (!empty($storage['city_name'])) {
        $shortName = !empty($storage['short_name']) ? $storage['short_name'] . '. ' : '';
        $order->set_shipping_city($shortName . $storage['city_name']);
        $order->set_billing_city($shortName . $storage['city_name']);
    }

    // Обновляем мета данные города
    if (!empty($storage['region_name'])) {
        $order->set_shipping_state($storage['region_name']);
        $order->set_billing_state($storage['region_name']);
    }

    // Обновляем мета данные индекса
    if (!empty($storage['index'])) {
        $order->set_shipping_postcode($storage['index']);
        $order->set_billing_postcode($storage['index']);
    }

    // Обновляем мета данные страны
    $order->set_shipping_country('RU');
    $order->set_billing_country('RU');

    if (!empty($storage['comment']))
        $order->set_customer_note($storage['comment']);

    // Обновляем название доставки
    foreach($order->get_shipping_methods() as &$shipping) {
        $shipping['name'] = $storage['delivery_method_name'] . ', ' . \Salesbeat\Lib\Tools::suffixToNumber(
            $storage['delivery_days'],
            [__('день', 'salesbeat'), __('дня', 'salesbeat'), __('дней', 'salesbeat')]
        );
    };

    return true;
}
add_action('woocommerce_checkout_create_order', 'beforeCheckoutCreateOrder', 20, 2);

// Добавление информации о доставке
function getDeliveryInfo($product, $mainDivId = 'salesbeat-deliveries')
{
    $params = [
        'product_id' => $product->get_id(),
        'token' => get_option('salesbeat_system_api_token'),
        'city_by' => 'ip',
        'params_by' => 'params',
        'main_div_id' => $mainDivId,
    ];

    ob_start();
    include 'includes/views/product.php';
    ob_end_flush();
}

add_action('salesbeat_product_widget', 'getDeliveryInfo', 10, 2);

// Добавляем вкладку с доставкой
function addProductTabDelivery($tabs)
{
    $tabs['sb_delivery'] = [
        'title' => __('Доставка', 'salesbeat'),
        'priority' => 100,
        'callback' => function () {
            ob_start();
            include 'includes/views/productTabDelivery.php';
            ob_end_flush();
        }
    ];

    return $tabs;
}
add_filter('woocommerce_product_tabs', 'addProductTabDelivery', 99);
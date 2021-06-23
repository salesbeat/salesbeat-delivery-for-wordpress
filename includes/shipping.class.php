<?php

namespace Salesbeat\Inc;

use \Salesbeat\Lib\Api;
use \Salesbeat\Lib\Storage;
use \Salesbeat\Lib\Tools;

defined('ABSPATH') || exit;

if (class_exists('Salesbeat\\Inc\\Shipping', false))
    return new Shipping();

class Shipping extends \WC_Shipping_Method
{
    /**
     * Shipping constructor.
     * @param int $instanceId
     */
    public function __construct($instanceId = 0)
    {
        parent::__construct();

        $this->supports = [
            'shipping-zones',
            'instance-settings'
        ];

        $this->instance_id = absint($instanceId);
        $deliverySettings = get_option('woocommerce_salesbeat_' . $this->instance_id . '_settings');

        $this->id = 'salesbeat';
        $this->title = !empty($deliverySettings['title']) ? $deliverySettings['title'] : __('Salesbeat', 'salesbeat');

        $this->method_title = __('Salesbeat', 'salesbeat');
        $this->method_description = __('Интеграция служб доставки', 'salesbeat');

        $this->error = false;

        $this->init();
    }

    public function init()
    {
        $params = [
            'title' => [
                'title' => __('Название доставки', 'salesbeat'),
                'type' => 'text',
                'default' => $this->method_title,
            ]
        ];

        if (is_array($this->instance_form_fields)) {
            $this->instance_form_fields = array_merge($this->instance_form_fields, $params);
        } else {
            $this->instance_form_fields = $params;
        }

        if ($this->error === false)
            add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Растчеты стоимости доставки
     * @param array $package
     */
    public function calculate_shipping($package = [])
    {
        $storage = Storage::main()->getByID('public');

        self::calcDeliveryPrice(); // Используется исключительно для перерасчета стоимости
        $cost = !empty($storage['delivery_price']) ? $storage['delivery_price'] : 0;
        $this->add_rate([
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => $cost,
            'package' => $package,
            'taxes' => false
        ]);
    }

    /**
     * Добавляем метод доставки
     * @param $methods
     * @return mixed
     */
    public static function shippingMethod($methods)
    {
        $methods['salesbeat'] = 'Salesbeat\Inc\Shipping';
        return $methods;
    }

    /**
     * Выводим информацию после формирования доставки
     * @param $method
     */
    public static function afterShippingRate($method)
    {
        if ($method->method_id !== 'salesbeat')
            return;

        $arItems = self::getCartProducts();

        $storage = Storage::main()->getByID('public');
        $arDeliveryInfo = self::formattingDeliveryInfo($storage);

        ob_start();
        include 'views/shipping.php';
        ob_end_flush();
    }

    /**
     * Получаем список товаров из корзины
     * @return array
     */
    public static function getCartProducts()
    {
        global $woocommerce;

        $Product = new Product();
        return $Product->getProductList($woocommerce->cart->get_cart());
    }

    /**
     * Метод подсчитывает складывает параметры товаров из корзины
     * @param array $arItems
     * @return array
     */
    public static function sumCartItemsParams($arItems = [])
    {
        $result = [
            'price_to_pay' => 0,
            'price_insurance' => 0,
            'weight' => 0,
            'x' => 0,
            'y' => 0,
            'z' => 0,
            'quantity' => 0,
        ];

        if (empty($arItems)) return $result;

        foreach ($arItems as $key => $arItem) {
            $result['price_to_pay'] += $arItem['price_to_pay'] * $arItem['quantity'];
            $result['price_insurance'] += $arItem['price_insurance'] * $arItem['quantity'];
            $result['weight'] += $arItem['weight'] * $arItem['quantity'];
        }

        $apiToken = get_option('salesbeat_system_api_token');

        $sbApi = new Api();
        return array_merge($result, $sbApi->packer($apiToken, $arItems));
    }

    /**
     * Метод на расчет стоимости доставки по Api
     * @return array
     */
    public static function calcDeliveryPrice()
    {
        $sumCart = self::sumCartItemsParams(self::getCartProducts());
        $storage = Storage::main()->getByID('public');

        if (empty($storage)) return [];

        $arCity = [];
        if (isset($storage['city_code']))
            $arCity['city_id'] = $storage['city_code'];

        $arDelivery = [];
        if (isset($storage['delivery_method_id']))
            $arDelivery['delivery_method_id'] = $storage['delivery_method_id'];
        if (isset($storage['pvz_id']))
            $arDelivery['pvz_id'] = $storage['pvz_id'];

        $arProfile = [];
        if (isset($sumCart['weight']))
            $arProfile['weight'] = $sumCart['weight'];
        if (isset($sumCart['x']))
            $arProfile['x'] = $sumCart['x'];
        if (isset($sumCart['y']))
            $arProfile['y'] = $sumCart['y'];
        if (isset($sumCart['z']))
            $arProfile['z'] = $sumCart['z'];

        $arPrice = [];
        if (isset($sumCart['price_to_pay']))
            $arPrice['price_to_pay'] = $sumCart['price_to_pay'];
        if (isset($sumCart['price_insurance']))
            $arPrice['price_insurance'] = $sumCart['price_insurance'];

        $apiToken = get_option('salesbeat_system_api_token');

        $sbApi = new Api();
        $result = $sbApi->getDeliveryPrice(
            $apiToken,
            $arCity,
            $arDelivery,
            $arProfile,
            $arPrice
        );

        if ($result['success'])
            Storage::main()->append('public', $result);

        return $result;
    }

    /**
     * Фильтруем платежные системы после выбора доставки
     * @param $payments
     * @return array
     */
    public static function paymentsOnShipping($payments)
    {
        if (is_admin() || is_wc_endpoint_url('order-pay'))
            return $payments;

        $chosenShipping = WC()->session->get('chosen_shipping_methods');
        $storage = Storage::main()->getByID('public');

        if (empty($storage)) return $payments;
        if ($chosenShipping[0] !== $storage['delivery_id']) return $payments;

        // Получаем данные из настроек модуля
        $arPaySystemsCash = get_option('salesbeat_pay_systems_cash') ?: [];
        $arPaySystemsCard = get_option('salesbeat_pay_systems_card') ?: [];
        $arPaySystemsOnline = get_option('salesbeat_pay_systems_online') ?: [];

        // Фильтруем платежные системы из системы на доступность
        foreach ($payments as $key => &$payment) {
            $paymentId = $payment->id;

            if (in_array($key, $arPaySystemsCash))
                $paymentId = 'cash';

            if (in_array($key, $arPaySystemsCard))
                $paymentId = 'card';

            if (in_array($key, $arPaySystemsOnline))
                $paymentId = 'online';

            if (!in_array($paymentId, $storage['payments']))
                unset($payments[$key]);
        }

        return $payments;
    }

    /**
     * Форматируем информации о доставке
     * @param array $data
     * @return array
     */
    public static function formattingDeliveryInfo($data = [])
    {
        $result = [];

        if (!empty($data['delivery_method_name'])) {
            $result['method_name'] = [
                'name' => __('Cпособ доставки', 'salesbeat'),
                'value' => $data['delivery_method_name']
            ];
        }

        if (isset($data['delivery_price'])) {
            $result['price'] = [
                'name' => __('Cтоимость доставки', 'salesbeat'),
                'value' => $data['delivery_price'] > 0 ? $data['delivery_price'] . __(' руб.', 'salesbeat') : __('Бесплатно', 'salesbeat')
            ];
        }

        if (isset($data['delivery_days'])) {
            if ($data['delivery_days']) {
                if ($data['delivery_days'] === 0) {
                    $data['delivery_days'] = __('Cегодня', 'salesbeat');
                } else if ($data['delivery_days'] === 1) {
                    $data['delivery_days'] = __('Завтра', 'salesbeat');
                } else {
                    $data['delivery_days'] = Tools::suffixToNumber(
                        $data['delivery_days'], [__('день', 'salesbeat'), __('дня', 'salesbeat'), __('дней', 'salesbeat')]
                    );
                }
            } else {
                $data['delivery_days'] = __('Не известно', 'salesbeat');
            }

            $result['days'] = [
                'name' => __('Срок доставки', 'salesbeat'),
                'value' => $data['delivery_days']
            ];
        }

        if (!empty($data['pvz_address'])) {
            $result['pvz_address'] = [
                'name' => __('Адрес выдачи', 'salesbeat'),
                'value' => $data['pvz_address']
            ];
        }

        if (!empty($data['street']) || !empty($data['house'])) {
            $address = '';
            $address .= !empty($data['street']) ? __('ул. ', 'salesbeat') . $data['street'] : '';
            $address .= !empty($data['street']) && !empty($data['house']) ? ', ' : '';
            $address .= !empty($data['house']) ? __('д. ', 'salesbeat') . $data['house'] : '';
            $address .= !empty($data['house_block']) ? __(' корпус ', 'salesbeat') . $data['house_block'] : '';
            $address .= !empty($data['flat']) ? __(', кв. ', 'salesbeat') . $data['flat'] : '';

            $result['address'] = [
                'name' => __('Адрес', 'salesbeat'),
                'value' => $address
            ];
        }

        if (!empty($data['comment'])) {
            $result['comment'] = [
                'name' => __('Комментарий', 'salesbeat'),
                'value' => $data['comment']
            ];
        }

        return $result;
    }
}

return new Shipping();
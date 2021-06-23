<?php

namespace Salesbeat\Inc;

use \Salesbeat\Lib\Storage;
use \Salesbeat\Lib\Tools;
use \Salesbeat\Lib\Api;

defined('ABSPATH') || exit;

if (class_exists('Salesbeat\\Inc\\Ajax', false))
    return new Ajax();

class Ajax
{
    private $data = [];
    private $errors = [];
    private $result = [];

    public function __construct()
    {
        /**
         * $this->validate на весь $_POST в данном случае: Исключение из правила.
         * Причина этому, метод callBack который может принять любой массив данных из нашего сериса
         */
        $this->data = !empty($_POST) && is_array($_POST) ?
            $this->validate($_POST) : [];
    }

    function validate($data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = (!empty($value) && is_array($value)) ?
                $this->validate($value) : esc_html($value);
        }

        return $data;
    }

    public function filter()
    {
        unset($this->data['action']);

        if (empty($this->data))
            $this->errors[] = wp_json_encode(['message' => __('Передайте аргументы', 'salesbeat')]);
    }

    public function view()
    {
        header('Content-Type: application/json;charset=UTF-8');

        if (!empty($this->errors)) {
            echo wp_json_encode($this->errors);
        } else {
            echo wp_json_encode($this->result);
        }

        die();
    }

    public function callBack()
    {
        $this->filter();

        if (!empty($this->errors))
            $this->view();

        Storage::main()->set('public', $this->data);
        $this->result = Storage::main()->getByID('public');

        $this->view();
    }

    public function sendOrder()
    {
        $this->filter();

        if (!empty($this->errors))
            $this->view();

        $orderId = (int)$this->data['order_id'];
        $order = wc_get_order($orderId);
        $arOrder = $order->get_data();

        $deliveryInfo = [];
        foreach ($arOrder['meta_data'] as $meta) {
            $metaData = $meta->get_data();
            if ($metaData['key'] === '_shipping_salesbeat') {
                $deliveryInfo = $metaData['value'];
                break;
            }
        }

        if (!empty($order) && !empty($deliveryInfo)) {
            $arFields = [];
            $arFields['secret_token'] = get_option('salesbeat_system_secret_token') ?: '';
            $arFields['test_mode'] = false;

            $arFields['order'] = [
                'delivery_method_code' => $deliveryInfo['delivery_method_id'],
                'id' => $orderId,
                'delivery_price' => $deliveryInfo['delivery_price'],
                'delivery_from_shop' => false
            ];

            $Product = new Product();
            $arFields['products'] = $Product->getProductList($order->get_items(), true);

            $recipient = [];
            $recipient['city_id'] = $deliveryInfo['city_code'];
            $recipient['full_name'] = trim($arOrder['billing']['last_name'] . ' ' . $arOrder['billing']['first_name']);
            $recipient['phone'] = Tools::phoneToTel($arOrder['billing']['phone']);
            $recipient['email'] = $arOrder['billing']['email'];

            unset($fullName);

            if (isset($deliveryInfo['pvz_id'])) {
                $recipient['pvz']['id'] = $deliveryInfo['pvz_id'];
            } else {
                $dateCourier = new DateTime();
                $dateCourier->add(new DateInterval('P1D'));

                $recipientCourier = [];
                $recipientCourier['street'] = $deliveryInfo['street'];
                $recipientCourier['house'] = $deliveryInfo['house'];
                $recipientCourier['flat'] = $deliveryInfo['flat'];
                $recipientCourier['date'] = $dateCourier->format('Y-m-d');
                $recipient['courier'] = $recipientCourier;
                unset($recipientCourier);
            }

            $arFields['recipient'] = $recipient;
            unset($recipient);
        } else {
            $arFields = [];
        }

        $sbApi = new Api();
        $resultApi = $sbApi->createOrder($arFields);

        if (!empty($resultApi['success'])) {
            global $wpdb;

            $sql = 'INSERT INTO `' . $wpdb->prefix . 'salesbeat_order` SET order_id = ' . $resultApi['order_id'] . ', sb_order_id = ' . $resultApi['salesbeat_order_id'] . ', track_code = ' . $resultApi['track_code'] . ',  date_order = NOW(), sent_courier = 0';
            $wpdb->query($sql);

            $this->result = [
                'status' => 'success',
                'message' => 'Заказ #' . $orderId . ' успешно выгружен',
                'data' => $resultApi
            ];
        } else {
            $message = !empty($resultApi['error_message']) ? $resultApi['error_message'] : '';
            $errorList = !empty($resultApi['error_list']) ? $resultApi['error_list'] : [];

            $this->result = [
                'status' => 'error',
                'message' => $message,
                'error_list' => $errorList
            ];
        }

        $this->view();
    }

    public function syncPaySystem()
    {
        if (!empty($this->errors))
            $this->view();

        $apiToken = get_option('salesbeat_system_api_token');

        $gateways = WC()->payment_gateways->payment_gateways();
        $paySystems = [];

        foreach ($gateways as $gateway)
            $paySystems[] = [
                'name' => $gateway->title,
                'code' => $gateway->id
            ];

        $exPaySystems = [
            'cash' => get_option('salesbeat_pay_systems_cash'),
            'card' => get_option('salesbeat_pay_systems_card'),
            'online' => get_option('salesbeat_pay_systems_online')
        ];

        $sbApi = new Api();
        $resultApi = $sbApi->syncDeliveryPaymentTypes($apiToken, $paySystems, $exPaySystems);

        if (!empty($resultApi['success'])) {
            $this->result = [
                'status' => 'success',
                'message' => 'Синхронизация прошла успешно',
                'data' => $resultApi
            ];
        } else {
            $message = !empty($resultApi['errorMessage']) ? $resultApi['errorMessage'] : 'Ошибка синхронизации';

            $this->result = [
                'status' => 'error',
                'message' => $message
            ];
        }

        $this->view();
    }

    public function getProductInfo()
    {
        $this->filter();

        if (!empty($this->errors))
            $this->view();

        $Product = new Product();
        $this->result = $Product->getProduct($this->data);

        $this->view();
    }
}

return new Ajax();
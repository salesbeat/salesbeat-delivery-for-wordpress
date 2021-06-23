<?php

namespace Salesbeat\Inc;

use \Salesbeat\Lib\Tools;

defined('ABSPATH') || exit;

if (class_exists('Salesbeat\\Inc\\AdminOrderShipping', false))
    return new AdminOrderShipping();

class AdminOrderShipping
{
    private $orderId = null;
    private $delivery = [];

    /**
     * Работаем с метабоксом
     * @param string $postType
     * @return bool
     */
    public function addMetaBoxes($postType)
    {
        if ($postType !== 'shop_order')
            return false;

        $this->orderId = !empty($_GET['post']) ? (int)$_GET['post'] : 0;
        $this->delivery = $this->getShippingOfMeta();

        if ($this->delivery)
            add_meta_box(
                'salesbeat',
                __('Salesbeat', 'salesbeat'),
                [$this, 'outputView'],
                'shop_order',
                'normal',
                'high'
            );

        return true;
    }

    public function getShippingOfMeta()
    {
        if (!$this->orderId) return [];

        $order = wc_get_order($this->orderId);
        $arOrder = $order->get_data();

        $arDeliveryInfo = [];
        foreach ($arOrder['meta_data'] as $meta) {
            $metaData = $meta->get_data();
            if ($metaData['key'] === '_shipping_salesbeat') {
                $arDeliveryInfo = $metaData['value']; // Array
                break;
            }
        }

        return $arDeliveryInfo;
    }

    /**
     * Выводим страницы
     */
    public function outputView()
    {
        $arDeliveryInfo = self::formattingDeliveryInfo($this->delivery);

        ob_start();
        include 'views/wcOrder.php';
        ob_end_flush();
    }

    /**
     * Форматируем информации о доставке
     * @param array $data
     * @return array
     */
    public static function formattingDeliveryInfo($data = [])
    {
        $result = [];

        if (!empty($data['city_code'])) {
            $result['city_code'] = [
                'name' => __('Код города', 'salesbeat'),
                'value' => $data['city_code']
            ];
        }

        if (!empty($data['city_name'])) {
            $location = $data['region_name'] ? $data['region_name'] . ', ' : '';
            $location .= $data['short_name'] ? $data['short_name'] . '. ' : '';
            $location .= $data['city_name'] ?: '';

            $result['location'] = [
                'name' => __('Местоположение', 'salesbeat'),
                'value' => $location
            ];
        }

        if (!empty($data['delivery_method_name'])) {
            $result['method_name'] = [
                'name' => __('Cпособ доставки', 'salesbeat'),
                'value' => $data['delivery_method_name']
            ];
        }

        if (!empty($data['delivery_price'])) {
            $result['price'] = [
                'name' => __('Cтоимость доставки', 'salesbeat'),
                'value' => $data['delivery_price'] > 0 ? $data['delivery_price'] . __(' руб.', 'salesbeat') : __('Бесплатно', 'salesbeat')
            ];
        }

        if (!empty($data['delivery_days'])) {
            if ($data['delivery_days'] == 0) {
                $data['delivery_days'] = __('Cегодня', 'salesbeat');
            } else if ($data['delivery_days'] == 1) {
                $data['delivery_days'] = __('Завтра', 'salesbeat');
            } else {
                $data['delivery_days'] = Tools::suffixToNumber(
                    $data['delivery_days'], [__('день', 'salesbeat'), __('дня', 'salesbeat'), __('дней', 'salesbeat')]
                );
            }

            $result['days'] = [
                'name' => __('Срок доставки', 'salesbeat'),
                'value' => $data['delivery_days']
            ];
        }

        if (!empty($data['sb_pvz_id'])) {
            $result['sb_pvz_id'] = [
                'name' => __('ID пункта выдачи', 'salesbeat'),
                'value' => $data['sb_pvz_id']
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

return new AdminOrderShipping();
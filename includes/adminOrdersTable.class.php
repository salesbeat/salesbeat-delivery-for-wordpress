<?php

namespace Salesbeat\Inc;

defined('ABSPATH') || exit;

if (class_exists('WP_List_Table') == false)
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

class AdminOrdersTable extends \WP_List_Table
{
    /**
     * Подготавливаем колонки таблицы для их отображения
     */
    public function prepare_items()
    {
        $action = $this->current_action(); // Получаем текущее значение action
        $perPage = $this->get_items_per_page('plance_per_page', 10); // Количество элементов на странице
        $data = $this->table_data(); // Получаем данные для формирования таблицы

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        // Устанавливаем данные для пагинации
        $this->set_pagination_args([
            'total_items' => count($data),
            'per_page' => $perPage
        ]);

        // Делим массив на части для пагинации
        $data = array_slice(
            $data,
            (($this->get_pagenum() - 1) * $perPage),
            $perPage
        );

        $this->_column_headers = array($columns, $hidden, $sortable); // Получаем заголовки
        $this->items = $data; // Устанавливаем данные таблицы
    }

    /**
     * Название колонок таблицы
     * @return array
     */
    public function get_columns()
    {
        return [
            'ex_order_id' => __('ID заказа', 'salesbeat'),
            'ex_date_created' => __('Дата создания', 'salesbeat'),
            'ex_customer' => __('Покупатель', 'salesbeat'),
            'ex_total' => __('Стоимость заказа', 'salesbeat'),
            'ex_sb_type_delivery' => __('Тип доставки', 'salesbeat'),
            'ex_sb_tracking_number' => __('Трек-номер', 'salesbeat'),
            'ex_action' => __('Действие', 'salesbeat'),
        ];
    }

    /**
     * Массив колонок которые нужно скрыть
     * @return array
     */
    public function get_hidden_columns()
    {
        return [];
    }

    /**
     * Массив названий колонок по которым выполняется сортировка
     * @return array
     */
    public function get_sortable_columns()
    {
        return [
            'ex_order_id' => ['id', false],
            'ex_date_created' => ['date_created', false],
            'ex_total' => ['total', false]
        ];
    }

    /**
     * Данные таблицы
     * @return array
     * @throws \Exception
     */
    private function table_data()
    {
        $query = new \WC_Order_Query([
            'limit' => -1,
            'orderby' => isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'id',
            'order' => isset($_GET['order']) && $_GET['order'] === 'asc' ? 'asc' : 'desc',
        ]);
        $orders = $query->get_orders();

        $arOrderIds = [];
        foreach ($orders as $order) {
            $arOrder = $order->get_data();

            $shippingList = $order->get_shipping_methods();
            if (!$shippingList) continue;

            $isSbDelivery = false;
            foreach ($shippingList as $shipping) {
                $isSbDelivery = ($shipping['method_id'] == 'salesbeat');
                if ($isSbDelivery) break;
            }
            if (!$isSbDelivery) continue;

            $arOrderIds[] = $arOrder['id'];
        }
        unset($order, $arOrder);

        $sbOrders = $this->getSbOrders($arOrderIds);

        $arResult = [];
        foreach ($orders as $order) {
            $arOrder = $order->get_data();
            if (!in_array($arOrder['id'], $arOrderIds)) continue;

            $sbDeliveryInfo = [];
            foreach ($arOrder['meta_data'] as $meta) {
                $metaData = $meta->get_data();
                if ($metaData['key'] === '_shipping_salesbeat') {
                    $sbDeliveryInfo = $metaData['value'];
                    break;
                }
            }
            unset($metaData);

            $action = !empty($sbOrders[$arOrder['id']]->track_code) ?
                '-' :
                '<a href="#" class="button" data-send-order data-order-id="' . $arOrder['id'] . '">' . __('Выгрузить заказ', 'salesbeat') . '</a>';

            $arResult[] = [
                'ex_order_id' => '<a href="/wp-admin/post.php?post=' . $arOrder['id'] . '&action=edit" target="__blank">' . $arOrder['id'] . '</a>',
                'ex_date_created' => $arOrder['date_created']->date('Y-m-d H:i'),
                'ex_customer' => trim($arOrder['billing']['last_name'] . ' ' . $arOrder['billing']['first_name']),
                'ex_total' => $arOrder['total'] . ' ₽',
                'ex_sb_type_delivery' => !empty($sbDeliveryInfo['delivery_method_name']) ? $sbDeliveryInfo['delivery_method_name'] : '-',
                'ex_sb_tracking_number' => !empty($sbOrders[$arOrder['id']]->track_code) ? $sbOrders[$arOrder['id']]->track_code : '',
                'ex_action' => $action
            ];
        }
        unset($order, $arOrder);

        return $arResult;
    }

    public function getSbOrders($arOrderIds = [])
    {
        global $wpdb;

        $orders = [];
        if (!$arOrderIds) return $orders;

        $sql = 'SELECT * FROM `' . $wpdb->prefix . 'salesbeat_order` WHERE  order_id IN (' . implode(',', $arOrderIds) . ')';
        $sbOrders = $wpdb->get_results($sql);

        if (empty($sbOrders)) return $orders;

        foreach ($sbOrders as $sbOrder)
            $orders[$sbOrder->order_id] = $sbOrder;

        return $orders;
    }

    /**
     * Отображаем сообщение если нет данных
     */
    public function no_items()
    {
        echo __('Data not found', 'plance');
    }

    /**
     * Возвращает содержимое колонки
     * @param array $item Массив данных таблицы
     * @param string $column_name Название текущей колонки
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'ex_order_id':
            case 'ex_date_created':
            case 'ex_customer':
            case 'ex_total':
            case 'ex_sb_type_delivery':
            case 'ex_sb_tracking_number':
            case 'ex_action':
                return $item[$column_name];
                break;
            default:
                return print_r($item, true);
                break;
        }
    }

    /**
     * Создаем чекбокс
     * @param array $item
     * @return string
     */
    function column_cb($item)
    {
        return '<input type="checkbox" name="id[]" value="' . $item['ex_id'] . '" />';
    }

    /**
     * Возвращает массив опций для групповых действий
     * @return array
     */
    function get_bulk_actions()
    {
        return [];
    }
}
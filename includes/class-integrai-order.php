<?php

class Integrai_Order {
    public function create($orderData, $customerId) {
        $orderData = json_decode(json_encode($orderData), true);

        $order = new WC_Order();
        $order->update_meta_data('_order_number', $orderData['order']['external_id']);
        $order->set_currency(get_woocommerce_currency());
        $order->set_prices_include_tax('yes' === get_option( 'woocommerce_prices_include_tax' ));
        $order->set_customer_id($customerId);

        // Add items
        foreach($orderData['items'] as $item) {
            $product = new WC_Product(wc_get_product_id_by_sku($item['sku']));
            $product->set_price($item['price']);
            $order->add_product($product, intval($item['qty']));
        }

        $order->set_address(array(
            'email' => $orderData['customer']['email'],
            'first_name' => $orderData['billing_address']['firstname'],
            'last_name' => $orderData['billing_address']['lastname'],
            'address_1' => $orderData['billing_address']['address_street'],
            'address_2' => $orderData['billing_address']['address_number'],
            'city' => $orderData['billing_address']['address_city'],
            'country' => 'BR',
            'state' => $orderData['billing_address']['address_state_code'],
            'postcode' => $orderData['billing_address']['address_zipcode'],
            'phone' => $orderData['billing_address']['telephone'],
        ), 'billing' );

        $order->set_address(array(
            'email' => $orderData['customer']['email'],
            'first_name' => $orderData['shipping_address']['firstname'],
            'last_name' => $orderData['shipping_address']['lastname'],
            'address_1' => $orderData['shipping_address']['address_street'],
            'address_2' => $orderData['shipping_address']['address_number'],
            'city' => $orderData['shipping_address']['address_city'],
            'country' => 'BR',
            'state' => $orderData['shipping_address']['address_state_code'],
            'postcode' => $orderData['shipping_address']['address_zipcode'],
            'phone' => $orderData['shipping_address']['telephone'],
        ), 'shipping' );

        // Add shipping costs
        $shippingPrice = $orderData['order']['shipping_amount'];
        $shippingDescription = $orderData['order']['shipping_carrier'] . ' - ' . $orderData['order']['shipping_method'];

        $rate = new WC_Shipping_Rate(
            'integrai_shipping_method_shipping',
            $shippingDescription,
            $shippingPrice,
            array(),
            'integrai_shipping_method'
        );
        $item = new WC_Order_Item_Shipping();
        $item->set_props(array(
                'method_title' => $rate->label,
                'method_id' => $rate->id,
                'total' => wc_format_decimal($rate->cost),
                'taxes' => $rate->taxes,
                'meta_data' => $rate->get_meta_data())
        );
        $order->add_item($item);

        // Add payment method
        $order->set_payment_method('integrai_marketplace');

        // Add marketplace info
        if ($orderData['marketplace']) {
            $order->update_meta_data('marketplace', $orderData['marketplace']);
        }

        // Add payments
        if ($orderData['payments']) {
            $order->update_meta_data('payments', $orderData['payments']);
        }

        $order->calculate_totals();
        $order->save();

        return $order->get_data();
    }

    public function getOrderId($orderData) {
        $order = $orderData['order'];

        if (isset($order['id'])) {
            return $order['id'];
        }

        if (isset($order['external_id'])) {
            $orderQuery = new WC_Order_Query(array(
                'limit' => 1,
                'meta_key' => '_order_number',
                'meta_value' => $order['external_id']
            ));

            $orders = $orderQuery->get_orders();

            if (count($orders)) {
                return $orders[0]->id;
            }
        }
    }
}
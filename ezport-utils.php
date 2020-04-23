<?php
	/**
	 * Return all field lists
	 */
	function ezport_get_field_list() {
		return array(
			"Order ID",
			"Order Status",
			"Date Created",
			"Date Modified",
			"Date Completed",
			"Date Paid",
			"Order Note",
			"Name (Billing)",
			"Company (Billing)",
			"Address 1 (Billing)",
			"Address 2 (Billing)",
			"City (Billing)",
			"State Code (Billing)",
			"Post Code (Billing)",
			"Country (Billing)",
			"Email (Billing)",
			"Phone (Billing)",
			"Name (Shipping)",
			"Company (Shipping)",
			"Address 1 (Shipping)",
			"Address 2 (Shipping)",
			"City (Shipping)",
			"State Code (Shipping)",
			"Post Code (Shipping)",
			"Country (Shipping)",
			"Payment Method",
			"Grand Total",
			"Item ID",
			"Item Name",
			"Item Quantity",
			"Item Subtotal",
			"Item Notes"
		);
	}
	
	/**
	 * Pretty print an underscore_case value to space-separated PascalCase counterpart
	 */
	function ezport_pretty_print($val) {
		$arr = preg_split("/[_-]/", $val);
		
		for ($i = 0; $i < count($arr); $i++) {
			$arr[$i] = ucfirst($arr[$i]);
		}
		
		return join(" ", $arr);
	}
	
	/**
	 * Pretty print a date with Timezone specifier
	 */
	function ezport_format_date($val) {
		if (isset($val)) {
			return date_format($val, "Y-m-d H:i:s e");
		}
		
		return "";
	}
	
	/**
	 * Pretty print item metadata into single-liner string value
	 */
	function ezport_format_item_meta($metadata) {
		$meta = array();
		
		foreach ($metadata as $key => $value) {
			$meta[] = "$value->value ($value->key)";
		}
		
		return join(" --- ", $meta);
	}
	
	/**
	 * Extract order data from WC_Order object and transform it into a CSV friendly array
	 */
	function ezport_extract_order_data($order) {
		$result = array(); // array 2 dimensi
		
		$base_array = array();
		
		// kuliin gayn~
		$base_array[] = $order->get_order_number();
		$base_array[] = ezport_pretty_print($order->get_status());
		$base_array[] = ezport_format_date($order->get_date_created());
		$base_array[] = ezport_format_date($order->get_date_modified());
		$base_array[] = ezport_format_date($order->get_date_completed());
		$base_array[] = ezport_format_date($order->get_date_paid());
		$base_array[] = $order->get_customer_note();
		$base_array[] = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
		$base_array[] = $order->get_billing_company();
		$base_array[] = $order->get_billing_address_1();
		$base_array[] = $order->get_billing_address_2();
		$base_array[] = $order->get_billing_city();
		$base_array[] = $order->get_billing_state();
		$base_array[] = $order->get_billing_postcode();
		$base_array[] = $order->get_billing_country();
		$base_array[] = $order->get_billing_email();
		$base_array[] = $order->get_billing_phone();
		$base_array[] = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
		$base_array[] = $order->get_shipping_company();
		$base_array[] = $order->get_shipping_address_1();
		$base_array[] = $order->get_shipping_address_2();
		$base_array[] = $order->get_shipping_city();
		$base_array[] = $order->get_shipping_state();
		$base_array[] = $order->get_shipping_postcode();
		$base_array[] = $order->get_shipping_country();
		$base_array[] = $order->get_payment_method_title();
		$base_array[] = $order->calculate_totals();
		
		foreach ($order->get_items() as $item) {
			$entry = $base_array;
			
			$entry[] = $item->get_product_id();
			$entry[] = $item->get_name();
			$entry[] = $item->get_quantity();
			$entry[] = $item->get_total();
			$entry[] = ezport_format_item_meta($item->get_formatted_meta_data());
			
			$result[] = $entry;
		}
		
		return $result;
	}
?>
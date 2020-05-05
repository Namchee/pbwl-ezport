<?php
	/**
	 * Return all field lists
	 */
	function ezport_get_field_list($orders) {
		$res=[
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
			"Item Subtotal"
		];
		
		foreach ($orders as $order) {
			foreach ($order->get_items() as $item) {
				$metadata = $item->get_formatted_meta_data();
				foreach ($metadata as $key => $value) {
					if (!in_array($value->key, $res)) {
						$res[] = $value->key;
					}
				}
			}
		}
		return $res;
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
	 * Check if the current order is on the requested date range
	 */
	function ezport_order_on_range($date_range, $order) {
		if (count($date_range) == 0) {
			return true;
		} else if (count($date_range) == 1) {
			if (isset($date_range['date-start'])) {
				return $date_range['date-start'] <= date_format($order->get_date_modified(), "Y-m-d");
			} else {
				return $date_range['date-end'] >= date_format($order->get_date_modified(), "Y-m-d");
			}
		} else {
			return $date_range['date-start'] <= date_format($order->get_date_modified(), "Y-m-d") && $date_range['date-end'] >= date_format($order->get_date_modified(), "Y-m-d");
		}
		
		return false;
	}
	
	/**
	 * Extract order data from WC_Order object and transform it into a 2-dimensional array
	 */
	function ezport_extract_order_data($order,$fields,$listFields) {
		$result = []; // array 2 dimensi
		$list_of_fields = $listFields;
		
		foreach ($order->get_items() as $item) {
			$base_array = [];
			foreach ($fields as $value) {
				if($value==0){
					$base_array[] = $order->get_order_number();
				}
				elseif($value==1){
					$base_array[] = ezport_pretty_print($order->get_status());
				}
				elseif($value==2){
					$base_array[] = ezport_format_date($order->get_date_created());
				}
				elseif($value==3){
					$base_array[] = ezport_format_date($order->get_date_modified());
				}
				elseif($value==4){
					$base_array[] = ezport_format_date($order->get_date_completed());
				}
				elseif($value==5){
					$base_array[] = ezport_format_date($order->get_date_paid());
				}
				elseif($value==6){
					$base_array[] = $order->get_customer_note();
				}
				elseif($value==7){		
					$base_array[] = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
				}
				elseif($value==8){		
					$base_array[] = $order->get_billing_company();
				}
				elseif($value==9){		
					$base_array[] = $order->get_billing_address_1();
				}
				elseif($value==10){		
					$base_array[] = $order->get_billing_address_2();
				}
				elseif($value==11){		
					$base_array[] = $order->get_billing_city();
				}
				elseif($value==12){		
					$base_array[] = $order->get_billing_state();
				}
				elseif($value==13){		
					$base_array[] = $order->get_billing_postcode();
				}
				elseif(($value==14)){		
					$base_array[] = $order->get_billing_country();
				}
				elseif($value==15){		
					$base_array[] = $order->get_billing_email();
				}
				elseif($value==16){		
					$base_array[] = $order->get_billing_phone();
				}
				elseif($value==17){		
					$base_array[] = $order->get_shipping_first_name() . " " . $order->get_shipping_last_name();
				}
				elseif($value==18){		
					$base_array[] = $order->get_shipping_company();
				}
				elseif($value==19){		
					$base_array[] = $order->get_shipping_address_1();
				}
				elseif($value==20){		
					$base_array[] = $order->get_shipping_address_2();
				}
				elseif($value==21){		
					$base_array[] = $order->get_shipping_city();
				}
				elseif($value==22){		
					$base_array[] = $order->get_shipping_state();
				}
				elseif($value==23){		
					$base_array[] = $order->get_shipping_postcode();
				}
				elseif($value==24){		
					$base_array[] = $order->get_shipping_country();
				}
				elseif($value==25){		
					$base_array[] = $order->get_payment_method_title();
				}
				elseif($value==26){		
					$base_array[] = $order->calculate_totals();
				}
				elseif($value==27){		
					$base_array[] = $item->get_product_id();
				}
				elseif($value==28){		
					$base_array[] =$item->get_name();
				}
				elseif($value==29){		
					$base_array[] = $item->get_quantity();
				}
				elseif($value==30){		
					$base_array[] = $item->get_total();
				}
				$metadata = $item->get_formatted_meta_data();
				foreach ($metadata as $meta) {
					if(strcasecmp($meta->key,$list_of_fields[$value])==0){
						$base_array[] = $meta->value;
					}
				}
			}
			$result[] = $base_array;
		}
		
		return $result;
	}


	/**
	 * Extract order data based on criteria
	 */
	function ezport_extract_orders($args, $listFields, $orders, $date_range) {
		$result = [];
		$fields = [];
		foreach ($args['fields'] as $value) {
			$fields[] = $listFields[$value];
		} 
		$result[] = $fields;
		$status = [];
		foreach ($args['status'] as $value) {
			$status[] = substr($value,3);
		} 
		foreach ($orders as $order) {
			if (empty($order)) {
				continue;
			}
			if (in_array($order->get_status(), $status) && ezport_order_on_range($date_range, $order)) {	 
				foreach (ezport_extract_order_data($order,$args['fields'],$listFields) as $entry) {
					$result[] = $entry;
				}
			}
		}

		return $result;
	}
?>
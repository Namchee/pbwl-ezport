<?php
	/**
	 *	Plugin Name: EZPort
	 *	Description: Sebuah plugin WooCommerce sederhana untuk melakukan export pada order-order yang ada
	 *	Author: Group E PBWL UNPAR
	 *  Version: 1.0
	 *  License: GPL v2 or later
 	 *  License URI: https://www.gnu.org/licenses/gpl-2.0.html
	 */
	require_once 'src/ezport-service.php';
	require_once 'src/ezport-io.php';

	/**
	 * Prevent direct access without WP
	 */
	if (!defined('ABSPATH')) {
	    exit();
	}
	
	/**
	 * Activation hook
	 */
	function ezport_activation_hook() {
		if (!ezport_check_dependency()) {
			deactivate_plugins(plugin_basename( __FILE__ ));
			show_dependency_error_message();
		}
	}
	
	 /**
	  * Check if woocommerce is installed and activated
	  */
	function ezport_check_dependency() {
		return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
	}
	
	/**
	 * Show a `die` when dependency requirements aren't met
	 */
	function show_dependency_error_message() {
		wp_die('<p>This plugin requires WooCommerce to installed and activated</p>');
	}
	
	/**
	 * Deactivation hook
	 * Remove EZPort submenu from WooCommerce menu
	 */
	function ezport_deactivation_hook() {
		remove_submenu_page('woocommerce', 'ezport');
	}
	 
	/**
	 * Add EZPort submenu to WooCommerce menu
	 */ 
	function ezport_add_submenu_page() {
		add_submenu_page('woocommerce', 'EZPort', 'EZPort - Export Orders', 'export', 'ezport', 'ezport_page');
	}
	 
	/**
	 * EZPort page functionality
	 */
	function ezport_page() {
		if (!ezport_check_dependency()) {
			show_dependency_error_message();
		}
		 
		if (!isset($_POST['export'])) {
			?>
			 	<div>
					<h1>
						Export Order
					</h1>
					<p>
						Melalui plugin ini, anda dapat mengexport seluruh order anda.
					</p>
					<form method='post'>
						<?php wp_nonce_field('ezport-export'); ?>
						<p>
							Order Status
						</p>
						<select name="status">
							<option value="" selected>All</option>
							<?php
								$statuses = wc_get_order_statuses();
								
								foreach ($statuses as $key => $value) {
									echo "<option value=${key}>" . $value . "</option>";
								}	
							?>
						</select>
						<p>
							File Extension
						</p>
						<p>
							<label for="csv">
								<input id="csv" type="radio" name="extension" value="csv" checked />
								<span>CSV</span>
							</label>
						</p>
						<p>
							<label for="xls">
								<input id="xls" type="radio" name="extension" value="xls" />
								<span>XLS</span>
							</label>
						</p>
						<p>
							<label for="xlsx">
								<input id="xlsx" type="radio" name="extension" value="xlsx" />
								<span>XLSX</span>
							</label>
						</p>
						<p style="margin-top: 2em;">								
							<input type='submit' class='button' name='export' value='Export Order' />
						</p>
					</form>
				</div>
			 <?php			
		} elseif (check_admin_referer('ezport-export')) {
            ob_clean(); // clear the output buffer first

			if (!current_user_can('view_woocommerce_reports')) { // If not shop manager, die immediately
				wp_die('<p>You must have access to view woocommerce reports</p>');
			}
			 
			$blogname = str_replace(" ", "", get_option('blogname'));
			$date = date('YmdHis');
			$filename = $blogname . '-' . $date;
			 
			$logger = wc_get_logger();
			$logger->info("EZPort activated on $date");

			$result = ezport_extract_orders($_POST);
			
			if ($_POST["extension"] == "csv") {
				ezport_export_as_csv($result, $filename);
			} else if ($_POST["extension"] == "xls") {
				ezport_export_as_xls($result, $filename);
			} else {
				ezport_export_as_xlsx($result, $filename);
			}

        	exit();
		}
	}
	 
	/**
	 * Hooks and action, do not touch this
	 */
	register_activation_hook(__FILE__, 'ezport_activation_hook');
    register_deactivation_hook(__FILE__, 'ezport_deactivation_hook');
	 
    add_action('admin_menu', 'ezport_add_submenu_page', 90);
?>
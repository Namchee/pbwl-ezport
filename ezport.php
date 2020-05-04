<?php
	/**
	 *	Plugin Name: EZPort
	 *	Description: Sebuah plugin WooCommerce sederhana untuk melakukan export pada order-order yang ada
	 *	Author: Group E PBWL UNPAR
	 *  Version: 1.0
	 *  Requires at least: 5.2
	 *  Requires PHP:      7.2
	 *  Author URI:        https://github.com/Namchee/pbwl-ezport
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
		add_submenu_page('woocommerce', 'EZPort', 'EZPort - Export Orders', 'view_woocommerce_reports', 'ezport', 'ezport_page');
	}
	 
	/**
	 * EZPort page functionality
	 */
	function ezport_page() {
		$wc_args = [
			'limit' => -1, // unlimited
			'type' => 'shop_order', // order saja
			'order' => 'ASC', // urutkan menaik
			'orderby' => 'ID' // urut berdasarkan ID
		];								
		$orders=wc_get_orders($wc_args);
		$listField = ezport_get_field_list($orders);
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
						Melalui plugin ini, anda dapat mengexport seluruh informasi mengenai orders yang ada.
					</p>
					<form method='post'>
						<?php wp_nonce_field('ezport-export'); ?>
						<div style="display: flex;">	
							<div>
								<p style="font-weight: bold;">
									Order Status
								</p>
								<?php
									$statuses = wc_get_order_statuses();
									foreach ($statuses as $key => $value) {
										echo "<p>
											<label for=${key}>
												<input id=${key} type='checkbox' name='status[]' value=${key} checked />
												<span>${value}</span>
											</label>
										</p>";
									}	
								?>
							</div>
							<div style="margin-left:2rem;">
								<p style="font-weight: bold;">
									Select Field
								</p>
								<div style="column-count: 3; margin-bottom:0;">
									<?php
									foreach ($listField as $key => $value) {
											if($key==0){
												echo "<p style='margin-top:0;'>
													<label for=${key}>
														<input id=${key} type='checkbox' name='fields[]' value=${key} checked />
														<span>${value}</span>
													</label>
												</p>";
											}
											else{
												echo "<p>
														<label for=${key}>
															<input id=${key} type='checkbox' name='fields[]' value=${key} checked />
															<span>${value}</span>
														</label>
												</p>";
											}
										}	
									?>
								</div>
							</div>
							<div style="margin-left:2rem;">
								<p style="font-weight: bold;">
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
							</div>
						</div>
						<div>
							<p style="font-weight: bold;">
								<label for="filename">Filename</label>
							</p>
							<input type="text" id="filename" name="filename" />
						</div>
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

			$date = date('YmdHis');
			$filename = $_POST["filename"];

			if (strlen($filename) == 0) {
				$blogname = str_replace(" ", "", get_option('blogname'));
				
				$filename = $blogname . '-' . $date;
			}
			 
			$logger = wc_get_logger();
			$logger->info("EZPort activated on $date");

			$result = ezport_extract_orders($_POST,$listField,$orders);
			
			ezport_export_data($result, $filename, $_POST['extension']);

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
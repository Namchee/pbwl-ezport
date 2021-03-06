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
				<style>
					.block {
    					padding: 1rem;
						padding-left: 0;
					}
					
					.m-4 {
						margin: 1rem;
					}

					.ml-8 {
    					margin-left: 2rem;
					}
					
					.ml-0 {
						margin-left: 0;
					}

					.mb-0 {
    					margin-bottom: 0;
					}

					.mt-0 {
    					margin-top: 0;
					}

					.cc-4 {
    					column-count: 4;
					}

					.font-weight-bold {
    					font-weight: bold;
					}

					.flex {
    					display: flex;
					}
					
					.align-start {
						align-items: start;
					}
				</style>
			 	<div>
					<h1>
						Export Order
					</h1>
					<p>
						Melalui plugin ini, anda dapat mengexport seluruh informasi mengenai orders yang ada.
					</p>
					<form method='post'>
						<?php wp_nonce_field('ezport-export'); ?>
						<div class="flex align-start">	
							<div class="block">
								<p class="font-weight-bold mt-0">
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
							<div class="block ml-8">
								<p class="font-weight-bold mt-0">
									Select Field
								</p>
								<div class="cc-4 mb-0">
									<?php
									foreach ($listField as $key => $value) {
											if ($key == 0) {
												echo "<p class='mt-0'>
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
							<div class="block ml-8">
								<p class="font-weight-bold mt-0">
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
						<div class="flex m-4 ml-0">
							<div class="block">
								<p class="font-weight-bold mt-0">
									<label for="filename">Filename</label>
								</p>
								<input type="text" id="filename" name="filename" /> <span class='file-ext'></span>
							</div>
							<div class="block">
								<p class="font-weight-bold mt-0">
									Last Modified On
								</p>
								<input type="text" id="date-start" name="date-start" /> &mdash;
								<input type="text" id="date-end" name="date-end" />
							</div>
						</div>
						<p class="block">								
							<input type='submit' class='button' name='export' value='Export Order' />
						</p>
					</form>
				</div>
				
				<script type="text/javascript">
    				jQuery(document).ready(function($) {
        				$('#date-start').datepicker({
							dateFormat: "yy-mm-dd"
						});
						$('#date-end').datepicker({
							dateFormat: "yy-mm-dd"
						});
						
						const extGroup = $('input[name="extension"]');
						const extText = $('.file-ext');
						
						extGroup.each(function() {
							if (this.checked) {
								extText.html(`.${this.value}`);
							}
							
							const that = this
							
							this.addEventListener('change', function() {
								extText.html(`.${that.value}`);
							});
						});
    				})
				</script>
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
			$logger->info('EZPort started on ${date}');
			
			$date_range = array();
			
			if (isset($_POST['date-start']) && count($_POST['date-start']) > 0) {
				$date_range['date-start'] = $_POST['date-start'];
			}
			
			if (isset($_POST['date-end']) && $_POST['date-end'] > 0) {
				$date_range['date-end'] = $_POST['date-end'];
			}

			$result = ezport_extract_orders($_POST, $listField, $orders, $date_range);
			
			ezport_export_data($result, $filename, $_POST['extension']);

        	exit();
		}
	}
	
	/**
	 * Load the jQuery UI Datepicker
	 */
	function ezport_load_datepicker() {
		wp_enqueue_script('jquery');
   	 	wp_enqueue_script('jquery-ui-datepicker');
    	// Enqueue default style
    	wp_enqueue_style('jquery-style','https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css'); 
	}
	 
	/**
	 * Hooks and action, do not touch this
	 */
	add_action('init', 'ezport_load_datepicker');
	add_action('admin_menu', 'ezport_add_submenu_page', 90); // action on visit admin page
	
	register_activation_hook(__FILE__, 'ezport_activation_hook'); // activation hook
    register_deactivation_hook(__FILE__, 'ezport_deactivation_hook'); // deactivation hook
?>
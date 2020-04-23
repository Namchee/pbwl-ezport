<?php
	/**
	 *	Plugin Name: EZPort
	 *	Description: Sebuah plugin WooCommerce sederhana untuk melakukan export pada order-order yang ada
	 *	Author: Group E PBWL UNPAR
	 *  Version: 1.0
	 *  License: GPL v2 or later
 	 *  License URI: https://www.gnu.org/licenses/gpl-2.0.html
	 */
	 require_once 'ezport-utils.php';
	 require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
	 
	 use PhpOffice\PhpSpreadsheet\Spreadsheet;
	 use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
				
	 ob_start(); // Start the output buffer

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
							File Extension
						</p>
						<p>
							<label for="csv">
								<input id="csv" type="radio" name="extension" value="csv" checked />
								<span>CSV</span>
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
            $format = ($_POST['extension'] == 'csv' ? 'csv' : 'xlsx'); 
            $content_type = ($_POST['extension'] == 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			 
			$logger = wc_get_logger();
			$logger->info("EZPort activated on $date");
			 
			$wc_args = array(
				'limit' => -1, // unlimited
				'type' => 'shop_order', // order saja
				'order' => 'ASC', // urutkan menaik
				'orderby' => 'ID', // urut berdasarkan ID
			);
			 
			$orders = wc_get_orders($wc_args);
			$result = array();
			 
			$result[] = ezport_get_field_list();
			 
			foreach ($orders as $order) {
				if (empty($order)) {
					continue;
				}
				 
				foreach (ezport_extract_order_data($order) as $entry) {
					$result[] = $entry;
				}
            }

            header("Content-Type: ${content_type}; charset=utf-8"); // Define content
    		header("Content-Disposition: attachment; filename=${filename}.${format}"); // Define attachment
			header("Cache-Control: no-cache, no-store, must-revalidate"); // Disable caching HTTP 1.1
			header("Pragma: no-cache"); // Disable caching HTTP 1.0
            header("Expires: 0"); // Proxies

            if ($_POST['extension'] == 'xlsx') {
				$styleArray = [
					'font' => [
        				'bold' => true,
    				],
				];
				
                $spreadsheet = new Spreadsheet();
				$worksheet = $spreadsheet->getActiveSheet();
				
                $worksheet->fromArray($result, NULL); // fill the value
				
				$highestColumn = $worksheet->getHighestColumn(); // get the farthest column
					
				$worksheet->getStyle("A1:${highestColumn}1")->applyFromArray($styleArray); // bold the headers

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output'); // save it
            } else {
                $output_file = fopen('php://output', 'w');

                foreach ($result as $entry) {
                    fputcsv($output_file, $entry);
                }

                ob_end_flush();
                fclose($output_file);
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
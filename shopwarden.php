<?php
/**
 * Plugin Name: Shopwarden
 * Plugin URI: https://shopwarden.com/
 * Description: Automated WooCommerce monitoring and testing.
 * Version: 1.0.11
 * Author: Shopwarden
 * Author URI: https://shopwarden.com
 * Text Domain: shopwarden
 * Requires at least: 5.6
 * Requires PHP: 7.0
 *
 * @package Shopwarden
 */

defined( 'ABSPATH' ) || exit;

final class Shopwarden {
	
	public function load(){
		
		// Activation hook
		register_activation_hook(__FILE__, array($this, 'redirectAfterActivation'));
		
		// Other actions
		add_action('plugins_loaded', array( $this, 'init' ) );
		add_action('init', array($this, 'initPlugin'));
		add_action('admin_init', array($this, 'activationRedirect'));

        // Delete orders on backend page load if crons are disabled
		if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON){
			add_action('admin_init', array($this, 'deleteOrdersFromBackend'));
		}
		
	}


	public function init() {
		
		// Add admin menu button
		add_action('admin_menu', array($this, 'adminMenuButton'), 999999);			
		
		// Only enable when WooCommerce is active
		if (class_exists('woocommerce')){
			
			add_filter('woocommerce_webhook_should_deliver', array($this, 'filterWebhooks'), 10, 3);
			add_filter('woocommerce_email_recipient_new_order', array($this, 'filterAdminEmails'), 10, 2 );
			add_action('shopwarden_delete_orders_action', array($this, 'deleteOrders'));
			
		}

	}
	
	function redirectAfterActivation() {
	    add_option('wg_redirect_after_activation_option', true);
	}
	
	
	function activationRedirect() {
	    if (get_option('wg_redirect_after_activation_option', false)) {
	        delete_option('wg_redirect_after_activation_option');
	        exit(wp_redirect(admin_url( 'admin.php?page=shopwarden' )));
	    }
	}
	
	// Admin button 
	function adminMenuButton(){
	    add_submenu_page( 'woocommerce', 'Shopwarden', 'Shopwarden', 'manage_options', 'shopwarden', array($this, 'initAdminPage') );
	}
	
	function initAdminPage(){
		
		// Get info and secrets
		$wg_secret = get_option('wg_secret');
		$wg_project = get_option('wg_project');
		$wg_linked = get_option('wg_linked');
		
		// Domain info
		$url_data = parse_url( get_site_url() );
		$domain = $url_data['host'];
		$appUrl = 'https://app.shopwarden.com/';
		
		// Fetch status
		$url = 'https://api.shopwarden.com/v1/p/' . $wg_project . '/sites/secrets/' . $wg_secret . '/status';		
		$result = wp_remote_get( $url );
				
		if(!empty($result['body'])){
			$result = json_decode($result['body'], true);
		} else {
			$result = [];
		}
		
		
		// Actions
		if(!empty($_GET['unlink'])){
						
			update_option('wg_linked', false, true);
			update_option('wg_project', null, true);
			
			// Reset secret
			$secret = strtolower(wp_generate_password(70, false));
			update_option('wg_secret', $secret, true);
			
	        exit(wp_redirect(admin_url( 'admin.php?page=shopwarden' )));
			
			
		} elseif(!empty($_GET['action'])){
						
			
            if($_GET['action'] == 'save_setting'){

                update_option($_GET['key'], $_GET['value'], true);
                exit;

            }
			
			
		} else {

			echo '<div style="max-width: 600px; margin: 0 auto; margin-top: 40px;">';
		
			if($wg_linked){
			
			    echo "<h1>Shopwarden WooCommerce Status</h1>
			    <p>Shopwarden automatically monitors your WooCommerce store. See the current status below.";
			    
			    
			    
			    if($result['health'] == 'healthy'){
				    echo '<div class="notice notice-success inline">
					<p>
						The website is fully operational
					</p>
				</div>';
				
				} else {
			
					echo '<div class="notice notice-error inline">
					<p>The website is not fully operational!
					</p>
				</div>';
				
				}
			
				echo '<h2>Dashboard</h2>
                <p>Visit the Shopwarden dashboard to configure your tests and to see all status details.</p>
                <a class="button-primary" href="https://app.shopwarden.com/sites/' . esc_html($result['hash']) . '" target="_blank" title="View Dashboard">View Dashboard</a>';
                
                echo '<h2>Settings</h2>

                <p><input type="checkbox" class="wppd-ui-toggle" name="shopwarden_auto_delete_customer" id="shopwarden_auto_delete_customer" value="1" ' . checked( 1, get_option( 'shopwarden_auto_delete_customer' ), false ) . ' />
                <label for="shopwarden_auto_delete_customer">Automatically delete customer accounts connected to test orders.</label></p>';



                echo '<h2>Unlink Site</h2>
                <p>Unlink the site from Shopwarden if you need to link it to a different account or if you need to do it for other reasons.</p>
                <a class="button-secondary" onclick="return confirm(\'Are you sure you want to unlink this site from Shopwarden?\')" href="' . admin_url( 'admin.php?page=shopwarden&unlink=true' ) . '" title="Unlink Site">Unlink Site</a>';
			





				echo '<p style="font-size: 6px; color: #f0f0f1;">Debug: ' . esc_html($wg_linked) . ' -  ' . esc_html($wg_project) . ' - ' . esc_html($wg_secret) . '</p>';

                echo "<script>

                const settingAccountDeletionCheckbox = document.getElementById('shopwarden_auto_delete_customer')
                settingAccountDeletionCheckbox.addEventListener('change', (event) => {

                    

                    let val = 0;
                    if (event.currentTarget.checked) {
                        val = 1;
                    } 

                    fetch('" . admin_url( 'admin.php?page=shopwarden&action=save_setting' ) . "&key=shopwarden_auto_delete_customer&value=' + val)
                    .then(data => { 
                        // Handle data 
                    }).catch(error => { 
                        // Handle error 
                    });


                });
                </script>";

                echo " <style>
                /**
                 * Checkbox Toggle UI
                 */
                input[type='checkbox'].wppd-ui-toggle {
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    appearance: none;

                    -webkit-tap-highlight-color: transparent;

                    width: auto;
                    height: auto;
                    vertical-align: middle;
                    position: relative;
                    border: 0;
                    outline: 0;
                    cursor: pointer;
                    margin: 0 4px;
                    background: none;
                    box-shadow: none;
                }
                input[type='checkbox'].wppd-ui-toggle:focus {
                    box-shadow: none;
                }
                input[type='checkbox'].wppd-ui-toggle:after {
                    content: '';
                    font-size: 8px;
                    font-weight: 400;
                    line-height: 18px;
                    text-indent: -14px;
                    color: #ffffff;
                    width: 36px;
                    height: 18px;
                    display: inline-block;
                    background-color: #a7aaad;
                    border-radius: 72px;
                    box-shadow: 0 0 12px rgb(0 0 0 / 15%) inset;
                }
                input[type='checkbox'].wppd-ui-toggle:before {
                    content: '';
                    width: 14px;
                    height: 14px;
                    display: block;
                    position: absolute;
                    top: 2px;
                    left: 2px;
                    margin: 0;
                    border-radius: 50%;
                    background-color: #ffffff;
                }
                input[type='checkbox'].wppd-ui-toggle:checked:before {
                    left: 20px;
                    margin: 0;
                    background-color: #ffffff;
                }
                input[type='checkbox'].wppd-ui-toggle,
                input[type='checkbox'].wppd-ui-toggle:before,
                input[type='checkbox'].wppd-ui-toggle:after,
                input[type='checkbox'].wppd-ui-toggle:checked:before,
                input[type='checkbox'].wppd-ui-toggle:checked:after {
                    transition: ease .15s;
                }
                input[type='checkbox'].wppd-ui-toggle:checked:after {
                    content: 'ON';
                    background-color: #2271b1;
                }
                </style>";



			} else {
		
		
		
				echo '<h1 style="line-height:30px;">The Shopwarden plugin has been installed successfully!</h1>
				<div class="notice notice-info inline" style="margin-left: 0px;">
					<p>
					Click the button below to configure Shopwarden and to start monitoring your WooCommerce site!
					</p>
					<a class="button-primary" style="margin-bottom: 20px;" href="' . esc_html($appUrl) .'plugin/' . esc_html($domain) . '" target="_blank" title="Configure Shopwarden">Configure Shopwarden</a>
				</div>';
			
			
			}
		
			echo '</div>';
		
	
		}
	
	}
	
	
	// Disable admin notifications on shopwarden checks
	function filterAdminEmails( $recipient, $order ){
		
		
        $payment_method = (\is_object($order) && \method_exists( $order, 'get_payment_method'))  ? $order->get_payment_method() : false;

		if($payment_method == 'shopwarden'){
			return false;
		} 
	
	    return $recipient;
	    
	}
	
	
	// Disable webhooks on shopwarden checks
	function filterWebhooks($should_deliver, $webhookObject, $arg){ 
		
		$topic = $webhookObject->get_topic();

        if(!empty($topic) && !empty($arg) && substr($topic, 0, 6) == 'order.'){
			
			$order = wc_get_order( $arg );	
            
            if(!empty($order)){
                $payment_method = (\is_object($order) && \method_exists( $order, 'get_payment_method'))  ? $order->get_payment_method() : false;

                if($payment_method && $payment_method == 'shopwarden'){
                    return false;
                } 
            }
			
		} elseif(!empty($topic) && !empty($arg) && substr($topic, 0, 13) == 'subscription.'){
			
			$order = wc_get_order( $arg );	
			
            if(!empty($order)){
                $payment_method = (\is_object($order) && \method_exists( $order, 'get_payment_method'))  ? $order->get_payment_method() : false;

                if($payment_method && $payment_method == 'shopwarden'){
                    return false;
                } 
            }
			
		}
	
		return $should_deliver;
		
	} 
	
	function initPlugin(){
		
		global $wpdb, $wp_version;


        $shopwarden_auto_delete_customer = get_option('shopwarden_auto_delete_customer');
        if($shopwarden_auto_delete_customer === false){
            add_option( 'shopwarden_auto_delete_customer', 1, '', 'yes' );
            $shopwarden_auto_delete_customer = get_option('shopwarden_auto_delete_customer');
        }

        // var_dump($shopwarden_auto_delete_customer);
        // exit;
        		
		// API Routes
		if(!empty($_GET['wg_api'])){
			
			// Get secret option
			$secret = get_option('wg_secret');
			$wgLinked = get_option('wg_linked');
			
			if($wgLinked == 1){
				$wgLinked = true;
			} else {
				$wgLinked = false;
			}
			
			$authed = false;
			
			if(!empty($_GET['wg_secret']) && $_GET['wg_secret'] == $secret){
				$authed = true;
			}
			
			if(empty($secret)){
				
				// Generate and store secret
				$secret = strtolower(wp_generate_password(70, false));
				update_option('wg_secret', $secret, true);
				
			}
			
			if(!empty($_GET['route'])){
				
				if($_GET['route'] == 'link' && $_SERVER['REQUEST_METHOD'] === 'POST' && $authed) {
					
					update_option('wg_linked', true, true);
					update_option('wg_project', sanitize_text_field($_GET['wg_project']), true);
					
					
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode([
						'success' => true,
						'msg' => 'linked_to_shopwarden'
					]);
					exit;
					
				} elseif($_GET['route'] == 'unlink' && $_SERVER['REQUEST_METHOD'] === 'POST' && $authed) {
					
					update_option('wg_linked', false, true);
					update_option('wg_project', null, true);
					
					// Reset secret
					$secret = strtolower(wp_generate_password(70, false));
					update_option('wg_secret', $secret, true);
					
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode([
						'success' => true,
						'msg' => 'unlinked_from_shopwarden'
					]);
					exit;

                } elseif($authed && $_GET['route'] == 'shipping'){

                    $countryClass = new WC_Countries();
                    $countryList = $countryClass->get_shipping_countries();


                    // print_r($countryList);
                    // exit;

                    $default_zone = new WC_Shipping_Zone(0);
                    $default_zone_formatted_location = $default_zone->get_formatted_location();
                    $default_zone_shipping_methods = $default_zone->get_shipping_methods();

                    $output = [
                        'default_methods' => [],
                        'zones' => []
                    ];

                    if(!empty($default_zone_shipping_methods)){
                        foreach($default_zone_shipping_methods AS $method){
                            if($method->enabled == 'yes'){
                                $output['default_methods'][] = $method->id;
                            }
                        }
                    }

                    $shippingZones = new WC_Shipping_Zones();
                    $zones = $shippingZones->get_zones();

                    if(!empty($zones)){
                        foreach($zones AS $zone){

                            $obj = [
                                'countries' => [],
                                'postalCodes' => [],
                                'states' => [],
                                'methods' => []
                            ];

                            if(!empty($zone['zone_locations'])){
                                foreach($zone['zone_locations'] AS $location){
                                    if($location->type == 'country'){
                                        $obj['countries'][] = $location->code;
                                    } elseif($location->type == 'postcode'){
                                        $obj['postalCodes'][] = $location->code;
                                    } elseif($location->type == 'state'){
                                        $p = explode(":", $location->code);
                                        $obj['states'][] = [
                                            'country' => $p[0],
                                            'state' => $p[1]
                                        ];
                                    }
                                }
                            }
                            
                            if(!empty($zone['shipping_methods'])){
                                foreach($zone['shipping_methods'] AS $method){
                                    if($method->enabled == 'yes'){
                                        $obj['methods'][] = $method->id;
                                    }
                                }
                            }

                            if(!empty($obj['methods'])){
                                $output['zones'][] = $obj;
                            }

                        }
                    }



                    // print_r($output);
                    // exit;

                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($output, JSON_UNESCAPED_SLASHES);
					exit;

                    // $array = [];

                    // foreach ($deliveryZones as $zone){


                    //     if(isset($countryList[$zone['zone_locations'][0]->code])){
                    //         foreach ($zone['shipping_methods'] as $shippingMethod){
                    //             if(!isset($array[$zone['zone_name']]) || $array[$zone['zone_name']] < $shippingMethod->instance_settings['cost'] )
                    //             $array[$zone['zone_name']] = $shippingMethod->instance_settings['cost'];
                    //         }
                    //     }
                    // }

                    // print_r($array);
                    // exit;
                                        
				} elseif($authed && $_GET['route'] == 'products'){
					
					header('Content-Type: application/json; charset=utf-8');
	
					$args = [
				        'post_type'      => 'product',
				        'post_status' => 'publish',
				        'ignore_sticky_posts' => 1,
				        'posts_per_page' => 99,
				        'meta_key' => 'total_sales',
				        'orderby' => 'meta_value_num',
				        'order' => 'DESC'
				    ];
				    				    
				    if(!empty($_GET['wg_keyword'])){
					    
					    $args['s'] = urldecode(sanitize_text_field($_GET['wg_keyword']));
					    
				    }
				    
				    if(!empty($_GET['wg_product_type'])){
					    
					    $args['tax_query'] = [
				            [
				                'taxonomy' => 'product_type',
				                'field'    => 'slug',
				                'terms'    => strtolower(sanitize_text_field($_GET['wg_product_type'])), 
				            ]
				        ];
					    
				    }
				
				    $loop = new WP_Query( $args );
				    
					$products = [];
				    
				    if(!empty($loop->posts)){
					    
					    foreach($loop->posts AS $post){
						    
						    $products[] = [
							    'id' => $post->ID,
							    'name' => $post->post_title,
							    'slug' => $post->post_name,
							    'url' => get_permalink($post->ID),
							    'thumb_url' => get_the_post_thumbnail_url($post->ID) 
						    ];
						    
					    }
					    
				    }
					
					echo json_encode($products, JSON_UNESCAPED_SLASHES);
					exit;
					
				} elseif($_GET['route'] == 'status'){
					
					header('Content-Type: application/json; charset=utf-8');
					
					$result = [
						'name' => get_option('blogname'),
						'linked' => $wgLinked
					];
					
					if(!$wgLinked){
						$result['secret'] = $secret;
					} elseif($authed) {
						
						$result['wordpress_version'] = $wp_version;
						if (class_exists('woocommerce')){
							$result['woocommerce_version'] = get_option('woocommerce_version');
						} else {
							$result['woocommerce_version'] = null;
						}
						
						// Get plugins
						if ( ! function_exists( 'get_plugins' ) ) {
						    require_once ABSPATH . 'wp-admin/includes/plugin.php';
						}
						
						$plugins = get_plugins();
						$activePlugins = wp_get_active_and_valid_plugins();
						
						$result['plugins'] = [];
						
						foreach($plugins AS $path => $plugin){
							
							$object = [
								'name' => $plugin['Name'],
								'author' => $plugin['Author'],
								'version' => $plugin['Version']
							];
							
							if(in_array(ABSPATH . 'wp-content/plugins/' . $path, $activePlugins)){
								$object['active'] = true;
							} else {
								$object['active'] = false;
							}
							
							$object['path'] = $path;
							
							$idE = explode("/", $path);
							$object['identifier'] = null;
							if(!empty($idE[0])){
								$object['identifier'] = $idE[0];
							}
							
							$result['plugins'][] = $object;
							
						}
						
					}
					
					echo json_encode($result);
					exit;
				
				} elseif($_GET['route'] == 'essentials' && $authed){
					
					header('Content-Type: application/json; charset=utf-8');
					
					$result = [
						'name' => get_option('blogname'),
						'linked' => $wgLinked,
						'wordpress_version' => $wp_version
					];
					
					if (class_exists('woocommerce')){
						
						$result['woocommerce_version'] = get_option('woocommerce_version');
						$result['cart_url'] = wc_get_cart_url();
						
					}
					
					echo json_encode($result);
					exit;
						
						
				} elseif($_GET['route'] == 'orders' && $authed){
					
					header('Content-Type: application/json; charset=utf-8');
					
					$per_page = 500;
					

			       	$params = [];
			       	
			       	$sql = "SELECT p.ID AS orderId, DATE_FORMAT(p.post_date_gmt, '%%Y-%%m-%%dT%%TZ') AS orderDate, 
						DATE_FORMAT(p.post_modified_gmt, '%%Y-%%m-%%dT%%TZ') AS lastModifiedOn, p.post_status AS status, 
						pm2.meta_value AS orderTotal, pm3.meta_value AS currency 
				        FROM {$wpdb->prefix}posts as p
				        LEFT JOIN {$wpdb->prefix}postmeta AS pm ON (p.id = pm.post_id AND pm.meta_key = '_payment_method') 
				        LEFT JOIN {$wpdb->prefix}postmeta AS pm2 ON (p.id = pm2.post_id AND pm2.meta_key = '_order_total') 
				        LEFT JOIN {$wpdb->prefix}postmeta AS pm3 ON (p.id = pm3.post_id AND pm3.meta_key = '_order_currency') 
				        WHERE p.post_type = 'shop_order'
				        AND p.post_status IN ('wc-processing', 'wc-completed', 'wc-failed', 'wc-cancelled') 
				        AND pm.meta_value <> 'shopwarden' ";
				    
				    if(!empty($_GET['wg_order_last_modified_since'])){

					    $sql .= " AND p.post_modified_gmt >= %s "; 
					    $params[] = gmdate('Y-m-d H:i:s', strtotime($_GET['wg_order_last_modified_since']));
					    
				    }
				    
				    if(!empty($_GET['wg_order_last_modified_until'])){

					    $sql .= " AND p.post_modified_gmt <= %s "; 
					    $params[] = gmdate('Y-m-d H:i:s', strtotime($_GET['wg_order_last_modified_until']));
					    
				    }
				    
				    if(!empty($_GET['wg_order_id_after']) && !empty($_GET['wg_order_last_modified_since'])){

					    $sql .= " AND (p.post_modified_gmt != %s OR p.ID > %s) "; 
					    $params[] = gmdate('Y-m-d H:i:s', strtotime($_GET['wg_order_last_modified_since']));
					    $params[] = $_GET['wg_order_id_after'];
					    
				    }
				    
				    if(!empty($_GET['wg_order_id_before']) && !empty($_GET['wg_order_last_modified_until'])){

					    $sql .= " AND (p.post_modified_gmt != %s OR p.ID < %s) "; 
					    $params[] = gmdate('Y-m-d H:i:s', strtotime($_GET['wg_order_last_modified_until']));
					    $params[] = $_GET['wg_order_id_before'];
					    
				    }				    
				    
				    // To make sure we don't get incomplete batches of orders
				    if(!empty($_GET['wg_order_last_modified_since'])){
					    $sql .= " AND p.post_modified_gmt < (NOW() - interval 2 second) "; 
				    }
				    
				        
				    if(!empty($_GET['wg_order_last_modified_until'])){
					    $sql .= " ORDER BY p.post_modified_gmt DESC, p.ID DESC 
					        	LIMIT " . $per_page;
			        } else {
				        $sql .= " ORDER BY p.post_modified_gmt ASC, p.ID ASC 
					        	LIMIT " . $per_page;
			        }
				        
				    $psql = $wpdb->prepare($sql, $params);
		
					$orders = $wpdb->get_results($psql);
				        				        
				    $output = [
					    'perPage' => $per_page,
					    'num' => count($orders), 
					    'orders' => $orders
				    ];
				        
					echo json_encode($output);
					exit;
				
				} elseif ($_GET['route'] == 'deactivate_plugin' && !empty($_GET['plugin_path']) && $_SERVER['REQUEST_METHOD'] === 'POST' && $authed) {
					
					if ( ! function_exists( 'deactivate_plugins' ) ) {
					    require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
						
					deactivate_plugins(sanitize_text_field($_GET['plugin_path']));    
		
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode([
						'success' => true,
						'msg' => 'plugin_deactivated'
					]);
					exit;
	
					
				} elseif ($_GET['route'] == 'activate_plugin' && !empty($_GET['plugin_path']) && $_SERVER['REQUEST_METHOD'] === 'POST' && $authed) {
					
					if ( ! function_exists( 'activate_plugins' ) ) {
					    require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
						
					activate_plugins(sanitize_text_field($_GET['plugin_path']));    
		
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode([
						'success' => true,
						'msg' => 'plugin_activated'
					]);
					exit;
	
					
				} else {
						
									
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode([
						'error' => true,
						'msg' => 'no_access'
					]);
					exit;
					
				}
				
			}
			
		}	
	
		if(!empty($_SERVER['HTTP_SHOPWARDEN_KEY']) && class_exists('woocommerce')){
			
            $secret = get_option('wg_secret');

            if($_SERVER['HTTP_SHOPWARDEN_KEY'] == $secret){

                // Init payment gateway
                $this->initPaymentGateway();
                
                // Load payment gateway
                include_once 'includes/fake-payment-gateway.php';
            
                // Add fake payment gateway for shopwarden tests
                add_filter('woocommerce_payment_gateways', array($this, 'addPaymentGateway') );
                
                // Init payment gateway
                $this->initPaymentGateway();

            }
			
		}
	
	
	}
		
	function scheduleDeleteOrders($order_id){
	    
	    $rng_seed = rand(0,999999999);
		wp_schedule_single_event(time() + 5, 'shopwarden_delete_orders_action', [$rng_seed]);
		
	}

    function deleteOrdersFromBackend() {

	    // don't run on ajax calls
	    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	       return;
	    }
	    
	    $rng_seed = rand(0,999999999);
	    $this->deleteOrders($rng_seed);
	    
	    
	}
	
	function deleteOrders($rng_seed){
		
		global $wpdb;

        $shopwarden_auto_delete_customer = get_option('shopwarden_auto_delete_customer');
		
		// Get all shopwarden orders
		$orders = $wpdb->get_results( "SELECT p.id
			FROM {$wpdb->prefix}posts as p
			LEFT JOIN {$wpdb->prefix}postmeta AS pm ON (p.id = pm.post_id AND pm.meta_key = '_payment_method')
			WHERE meta_value = 'shopwarden' ");
	        
	    // Delete orders
	    if(!empty($orders)){
		    foreach($orders AS $order){

                try {
                    $orderObject = new WC_Order( $order->id );
			        $customerId = $orderObject->get_customer_id();
			        
			        // Delete order
                    if($orderObject){
                        $orderObject->delete(true);
                    }
                    $orderObject = null;
					
					// Delete customer if available			        
			        if($customerId && $shopwarden_auto_delete_customer){
				        $customer = new WC_Customer( $customerId );
			        		
						if ( ! function_exists( 'wp_delete_user' ) ) {
							require_once ABSPATH . 'wp-admin/includes/user.php';
						}
						
						$res = $customer->delete(true);			
						$customer = null;						
					}

					
                } catch (\Exception $e) { }

			}    	
		}
		
	}
	
	function initPaymentGateway() {

		add_action( 'woocommerce_update_options_payment_gateways_shopwarden', array( 'WC_Gateway_Fake_Pay', 'process_admin_options' ));
		
	}
	
	function addPaymentGateway( $methods ) {
		
		$methods[] = 'WC_Gateway_Fake_Pay'; 	
		return $methods;
		
	}

		
}

$shopwarden = new Shopwarden( __FILE__ );
$shopwarden->load();

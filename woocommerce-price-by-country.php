<?php 
/*
Plugin Name: WooCommerce Price by Country
Plugin URI:  http://www.sweethomes.es
Description: Allows you to set the prices of a product according to the user's country
Version: 0.36
Author: Sweet Homes
Author URI: http://www.sweethomes.es
Email: info@sweethomes.es
*/


class woocommerce_price_by_country { 


	function __construct() {
		
		$this->idName = 'price_by_country';
		
		add_action( 'init', array( &$this, 'init' ), 9999999 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( &$this, 'get_item_from_session' ), -1, 1 );

		add_filter( 'woocommerce_get_price', array( &$this, 'maybe_return_price' ), 999, 2 );

		add_action( 'woocommerce_variable_product_bulk_edit_actions', array( &$this, 'bulk_edit' ) );
		
		add_action( 'woocommerce_product_after_variable_attributes', array( &$this, 'add_variable_attributes'), 1, 2 );
		
		add_action( 'woocommerce_product_options_pricing', array( &$this, 'add_simple_price' ), 1 );
		
		add_action(	'wp_enqueue_scripts', array( &$this, 'wpbc_enqueue_scripts' ), 1);
		add_action(	'wp_footer', array( &$this, 'wpbc_footer_script' ), 1000);
		
		if (class_exists('woocommerce_wpml')) {
		    add_action( 'admin_footer', array( &$this, 'wpbc_woo_multilingual_fix' ), 1000 );
		}
		
	}

	
	function init() { 
	
		
	
		@session_start();
		
		add_action( 'woocommerce_process_product_meta_simple', array( &$this, 'process_product_meta' ), 1, 1 );

		add_action( 'woocommerce_process_product_meta_variable', array( &$this, 'process_product_meta_variable' ), 999, 1 );

		// Regular price displays, before variations are selected by a buyer
		add_filter( 'woocommerce_grouped_price_html', array( &$this, 'maybe_return_wholesale_price' ), 1, 2 );
		add_filter( 'woocommerce_variable_price_html', array( &$this, 'maybe_return_wholesale_price' ), 1, 2 );

		// Javscript related
		add_filter( 'woocommerce_variation_sale_price_html', array( &$this, 'maybe_return_variation_price' ), 1, 2 );
		add_filter( 'woocommerce_variation_price_html', array( &$this, 'maybe_return_variation_price' ), 1, 2 );
		add_filter( 'woocommerce_variable_empty_price_html', array( &$this, 'maybe_return_variation_price_empty' ), 999, 2 );

		add_filter( 'woocommerce_product_is_visible', array( &$this, 'variation_is_visible' ), 99999, 2 );

		add_filter( 'woocommerce_available_variation', array( &$this, 'maybe_adjust_variations' ), 1, 3 );

		add_filter( 'woocommerce_is_purchasable', array( &$this, 'is_purchasable' ), 1, 2 );

		add_filter( 'woocommerce_sale_price_html', array( &$this, 'maybe_return_wholesale_price' ), 1, 2 );
		add_filter( 'woocommerce_price_html', array( &$this, 'maybe_return_wholesale_price' ), 1, 2 );
		add_filter( 'woocommerce_empty_price_html', array( &$this, 'maybe_return_wholesale_price' ), 1, 2 );

		add_filter( 'woocommerce_get_cart_item_from_session', array( &$this, 'get_item_from_session' ), 999, 1 );
		
		
				
		$this->settings = get_option( 'woocommerce_global_'. $this->idName . '_settings' );
		$defaults = array ( 'wpbc_price_checkoutselector' => 'billing');
		
		if(empty($this->settings)){
			$this->settings = wp_parse_args( $this->settings, $defaults );
		}
		
		
	}


	/* USER-AGENTS Tnks to iamandrus ## http://stackoverflow.com/a/6524325 ##
	========================================================================== */
	function check_user_agent ( $type = NULL ) {
	        $user_agent = strtolower ( $_SERVER['HTTP_USER_AGENT'] );
	        if ( $type == 'bot' ) {
	                // matches popular bots
	                if ( preg_match ( "/googlebot|adsbot|yahooseeker|yahoobot|msnbot|watchmouse|pingdom\.com|feedfetcher-google/", $user_agent ) ) {
	                        return true;
	                        // watchmouse|pingdom\.com are "uptime services"
	                }
	        } else if ( $type == 'browser' ) {
	                // matches core browser types
	                if ( preg_match ( "/mozilla\/|opera\//", $user_agent ) ) {
	                        return true;
	                }
	        } else if ( $type == 'mobile' ) {
	                // matches popular mobile devices that have small screens and/or touch inputs
	                // mobile devices have regional trends; some of these will have varying popularity in Europe, Asia, and America
	                // detailed demographics are unknown, and South America, the Pacific Islands, and Africa trends might not be represented, here
	                if ( preg_match ( "/phone|iphone|itouch|ipod|symbian|android|htc_|htc-|palmos|blackberry|opera mini|iemobile|windows ce|nokia|fennec|hiptop|kindle|mot |mot-|webos\/|samsung|sonyericsson|^sie-|nintendo/", $user_agent ) ) {
	                        // these are the most common
	                        return true;
	                } else if ( preg_match ( "/mobile|pda;|avantgo|eudoraweb|minimo|netfront|brew|teleca|lg;|lge |wap;| wap /", $user_agent ) ) {
	                        // these are less common, and might not be worth checking
	                        return true;
	                }
	        }
	        return false;
	}


	
	function wpbc_enqueue_scripts() {

	    $assets_path = str_replace( array( 'http:', 'https:' ), '', plugins_url() ) . '/woocommerce/assets/';
		wp_enqueue_script('google-jsapi', 'https://www.google.com/jsapi');
		wp_enqueue_script('jquery-cookie', '//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.0/jquery.cookie.min.js', array( 'jquery' ), '1.0.0', false );

		
		$ismobile = $this->check_user_agent('mobile');
		
		if($ismobile) {
			if($this->settings['wpbc_price_checkoutselector'] == 'billing'):
				wp_enqueue_script('pbc-billing', plugin_dir_url( __FILE__ ) . 'comple/pbc-billing.js', array( 'jquery', 'wc-checkout', 'google-jsapi', 'jquery-cookie'), '1.0.0', false);
			elseif($this->settings['wpbc_price_checkoutselector'] == 'shipping'):
				wp_enqueue_script('pbc-shipping', plugin_dir_url( __FILE__ ) . 'comple/pbc-shipping.js', array( 'jquery', 'wc-checkout', 'google-jsapi', 'jquery-cookie' ), '1.0.0', false);
			endif;
		} else {
			if ( get_option( 'woocommerce_enable_chosen' ) == 'yes' ) {
		
				wp_enqueue_script( 'base-chosen', $assets_path . 'js/chosen/chosen.jquery.min.js', array( 'jquery' ), '1.0.0', false );
				wp_enqueue_style( 'woocommerce_chosen_styles', $assets_path . 'css/chosen.css' );
			
				if($this->settings['wpbc_price_checkoutselector'] == 'billing'):
					wp_enqueue_script('pbc-chosen', plugin_dir_url( __FILE__ ) . 'comple/pbc-chosen-billing.js', array( 'jquery', 'wc-checkout', 'google-jsapi', 'jquery-cookie',  'base-chosen' ), '1.0.0', false);
				elseif($this->settings['wpbc_price_checkoutselector'] == 'shipping'):
					wp_enqueue_script('pbc-chosen', plugin_dir_url( __FILE__ ) . 'comple/pbc-chosen-shipping.js', array( 'jquery', 'wc-checkout', 'google-jsapi', 'jquery-cookie',  'base-chosen'  ), '1.0.0', false);
				endif;
	
			} else {
				if($this->settings['wpbc_price_checkoutselector'] == 'billing'):
					wp_enqueue_script('pbc-billing', plugin_dir_url( __FILE__ ) . 'comple/pbc-billing.js', array( 'jquery', 'wc-checkout', 'google-jsapi', 'jquery-cookie'), '1.0.0', false);
				elseif($this->settings['wpbc_price_checkoutselector'] == 'shipping'):
					wp_enqueue_script('pbc-shipping', plugin_dir_url( __FILE__ ) . 'comple/pbc-shipping.js', array( 'jquery', 'wc-checkout', 'google-jsapi', 'jquery-cookie' ), '1.0.0', false);
				endif;
			}

		}


		
	}

	function wpbc_woo_multilingual_fix(){
		
		// this function fix the translation of prices

		$inline_js = "<!-- WooCommerce price by country Woocomerce multilingual Fix -->\n<script type=\"text/javascript\">\n jQuery(document).ready(function($) { \n";

                $inline_js .= "$('input[class^=\"group_level_\"]').each(function(){ \n";
                    $inline_js .= "$(this).removeAttr('readonly');\n";
                    $inline_js .= "$(this).parent().find('img').remove();\n";
                $inline_js .= "});\n";

		$inline_js .="\n});\n</script> \n";
		
		echo $inline_js;
	}

	

	function wpbc_footer_script(){
	
		$c = new WC_Countries();
	
		$basecountry = $c->get_base_country();
   		$inline_js = "<!-- WooCommerce price by country JavaScript-->\n<script type=\"text/javascript\">\n jQuery(document).ready(function($) { \n";
   		$inline_js .= "var country = $.cookie('country'); \n ";
   		$inline_js .= "\n if (country) { }else{ \n ";
   		$inline_js .= "\n if (google.loader.ClientLocation ) { \n
						var country_code = google.loader.ClientLocation.address.country_code; \n
							$.cookie('country', country_code, { expires: 7 , path: '/'  }); \n
							$('select#pbc_country_selector').val('+country_code+'); \n
							$('select#pbc_country_selector').trigger('chosen:updated') \n
						}else{ \n 
							$.cookie('country', '".$basecountry."', { expires: 7 , path: '/'  }); \n
							$('select#pbc_country_selector').val('".$basecountry."'); \n
							$('select#pbc_country_selector').trigger('chosen:updated') \n
						} \n
					} \n";
		$inline_js .="\n});\n</script> \n";
   		
		echo $inline_js;
	}



	function get_item_from_session( $item_data = '' ) { 
		global $woocommerce;
		
		if (isset($_COOKIE['country'])) { 
			$country = $_COOKIE['country']; 
		} else { 
			$country = ""; 
		}
		$countries = $this->get_countries();
		if ( empty( $countries ) )
			return;
			
		foreach( $this->get_countries() as $group => $element ) {
		
			if ( !in_array($country, $element['countries'])  ) 
				continue;

			$_product = get_product( $item_data['product_id'] ); 

			if ( isset( $item_data['variation_id'] ) && 'variable' == $_product->product_type ):
				$level_price = get_post_meta( $item_data['variation_id' ], '_' . $group . '_price', true );
			elseif ( 'simple' == $_product->product_type || 'external' == $_product->product_type ):
				$level_price = get_post_meta( $item_data['product_id' ], '_' . $group . '_price', true );
			else: // all other product types - possibly incompatible with custom product types added by other plugins\
				$level_price = get_post_meta( $item_data['product_id' ], '_' . $group . '_price', true );
			endif;

			if ( $level_price ) { 
				$item_data['data']->price = $level_price;
				$item_data['data']->regular_price = $level_price;
			}

		}

		return $item_data;
	}
	
	
	
	
	function maybe_return_wholesale_price( $price, $_product ) { 
		
		global $product;
		global $woocommerce;
		
		if (isset($_COOKIE['country'])) { 
			$country = $_COOKIE['country']; 
		} else { 
			$country = ""; 
		}

		$countries = $this->get_countries();
		if ( empty( $countries ) )
			return;
			
		foreach( $this->get_countries() as $group => $element ) {
		
			if ( !in_array($country, $element['countries'])  ) 
				continue;

			$vtype = 'variable';

			if ( $_product->is_type('grouped') ) { 

				$min_price = '';
				$max_price = '';

				foreach ( $_product->get_children() as $child_id ) { 

					$child_price = get_post_meta( $child_id, '_' . $group . '_price', true );

					if ( !$child_price ) 
						continue;

					if ( $child_price < $min_price || $min_price == '' ) $min_price = $child_price;

					if ( $child_price > $max_price || $max_price == '' ) $max_price = $child_price;

				}


				$price = '<span class="from">' . __('From:', 'woocomerce-price-by-country') . ' </span>' . wc_price( $min_price );

			} elseif ( $_product->is_type( $vtype ) ) {

				$wprice_min = get_post_meta( $_product->id, 'min_variation_' . $group . '_price', true );
				$wprice_max = get_post_meta( $_product->id, 'max_variation_' . $group . '_price', true );
				/*
				$wprice_min = $_product->get_variation_price( 'min', true ); // tnx Germán Oronoz Arbide <germanoronoz@gmail.com>
				$wprice_max = $_product->get_variation_price( 'max', true ); // tnx Germán Oronoz Arbide <germanoronoz@gmail.com>
				*/

				if ( $wprice_min !== $wprice_max ){
					$price = '<span class="from">' . __( 'From:', 'woocomerce-price-by-country') . $wprice_min . ' </span>';
				}

				if ( !empty( $wprice_min ) && !empty( $wprice_max ) && $wprice_min == $wprice_max ){
					return $price;
				
				} elseif ( !empty( $wprice_min ) ){
					$price = '<span class="from '.$group.'" >' . __( 'From:', 'woocomerce-price-by-country') . ' ' . wc_price( $wprice_min ) . ' </span>';
					
				} else { 
				
					$wprice_min = get_post_meta( $_product->id, 'min_variation_' . $group . '_price', true );
					$wprice_max = get_post_meta( $_product->id, 'max_variation_' . $group . '_price', true );
					/*$min_price = $product->get_variation_price( 'min', true );
					$max_price = $product->get_variation_price( 'max', true );*/
					
					if ($min_price != $max_price){
						$price = sprintf( __( '%1$s', 'woocommerce' ), wc_price( $min_price ) );
						$price2 = sprintf( __( '%1$s', 'woocommerce' ), wc_price( $max_price ) );
						return $price.' - '.$price2;
					} else {
						$price = sprintf( __( '%1$s', 'woocommerce' ), wc_price( $min_price ) );
						return $price;
					}
				
					/*$wprice_min = $_product->get_variation_price( 'min', true ); // tnx Germán Oronoz Arbide <germanoronoz@gmail.com>
					$wprice_max = $_product->get_variation_price( 'max', true ); // tnx Germán Oronoz Arbide <germanoronoz@gmail.com>*/
				
					/*$wprice_min = get_post_meta( $_product->id, '_min_variation_regular_price', true );
					$wprice_max = get_post_meta( $_product->id, '_max_variation_regular_price', true );*/
				
					if ( $wprice_min !== $wprice_max ):
						$price = '<span class="from">' . __( 'From:', 'woocomerce-price-by-country') . $wprice_min . ' </span>';
					endif;

					if (  !empty( $wprice_min ) && !empty( $wprice_max ) && $wprice_min == $wprice_max ): 
						return $price;
					
					elseif ( !empty( $wprice_min ) ):
						$price = '<span class="from">' . __( 'From:', 'woocomerce-price-by-country') . ' ' . wc_price( $wprice_min ) . ' </span>';
						
					endif;

				}

			} else { 

				$wprice_min = get_post_meta( $_product->id, '_' . $group . '_price', true );
					
				if ( isset( $wprice_min ) && $wprice_min > 0 ){
					$price = wc_price( $wprice_min );

				} elseif ( '' === $wprice_min ) {
				
					$price = get_post_meta( $_product->id, '_price', true );
					if ( !empty( $price ) ){
						$price = wc_price( $price ); 
					}
				} elseif ( 0 == $wprice_min ) 
					$price = __( 'Free!', 'woocomerce-price-by-country' );
				
				
			}

		}


		//$price = '0000';

		return $price; 
	}
	
	
	
	
	function is_purchasable( $purchasable, $_product ) { 
		
		if (isset($_COOKIE['country'])) { 
			$country = $_COOKIE['country']; 
		} else { 
			$country = ""; 
		}
		$countries = $this->get_countries();
		
		if ( empty( $countries ) )
			return $purchasable;

		foreach( $this->get_countries() as $group => $element ) {

			if ( !in_array($country, $element['countries'])  ) 
				continue;

			$is_variation = $_product->is_type( 'variation' );

			if ( !$is_variation ) 
				$is_variation = $_product->is_type( 'variable' );

			if ( $is_variation  ) { 
			
				// Variable products
				if ( !isset( $_product->variation_id ) )
					return $purchasable;

				$price = get_post_meta( $_product->variation_id, 'min_variation_' . $group . '_price', true );

				if ( !isset( $price ) )
					return $purchasable;

			} else { 
			
				// Simple products
				$price = get_post_meta( $_product->id, '_' . $group . '_price', false );

				if ( !empty( $price ) )
					return true;
				else 
					return $purchasable;
					
					
			}
		}
		
		return $purchasable;
	}
	
	
	
		
	function maybe_return_price( $price = '', $_product ) { 
		
		if (isset($_COOKIE['country'])) { 
			$country = $_COOKIE['country']; 
		} else { 
			$country = ""; 
		}
		
		$iswcmlActive = $this->wpbc_is_wcml_active();
		
		$countries = $this->get_countries();
		if ( empty( $countries ) ):
			if($iswcmlActive):
				return apply_filters('wcml_raw_price_amount', $price);
			else:
				return $price;
			endif;
		endif;
			
		foreach( $this->get_countries() as $group => $element ) {
		
			if ( !in_array($country, $element['countries'])  ) 
				continue;

			
			if ( isset( $_product->variation_id ) ) {

				//if ( isset( $_product->variation_id ) ) 
					$wholesale = get_post_meta( $_product->variation_id, '_' . $group . '_price', true );
				//else 
				//	$wholesale = '';

				if ( intval( $wholesale ) > 0 ) {
				
					$customPrice = $wholesale;
				
					//$_product->product_custom_fields[ '_' . $group . '_price' ] = array( $wholesale );
					//var_dump($_product->product_custom_fields[ '_' . $group . '_price']);
					
					/*echo '<pre>';
					var_dump($group);
					var_dump($wholesale);
					var_dump($customPrice);
					echo '</pre>';*/
				}

				//if ( isset( $_product->product_custom_fields[ '_' . $group . '_price' ] ) && is_array( $_product->product_custom_fields[ '_' . $group . '_price'] ) && $_product->product_custom_fields[ '_' . $group . '_price'][0] > 0 ) {

				if(isset($customPrice) && $customPrice > 0){
					if($iswcmlActive):
						$price = apply_filters('wcml_raw_price_amount', $customPrice);
					else:
						$price = $customPrice;
					endif;

				} elseif ( $_product->price === '' ) {
					$price = '';
					
				}elseif ($_product->price == 0 ) {
					$price = __( 'Free!', 'woocomerce-price-by-country' );
					
				}
				
				if($iswcmlActive):
					return apply_filters('wcml_raw_price_amount', $price);
				else:
					return $price;
				endif;

			}

			$tier_price = get_post_meta( $_product->id, '_' . $group . '_price', true );
			
			if ( empty( $tier_price ) ): 
				if($iswcmlActive):
					return apply_filters('wcml_raw_price_amount', $price);
				else:
					return $price;
				endif;
			else:
				if($iswcmlActive):
					return apply_filters('wcml_raw_price_amount', $tier_price);
				else:
					return $tier_price;
				endif;
			endif;
				
		}
		if($iswcmlActive):
			return apply_filters('wcml_raw_price_amount', $price);
		else:
			return $price;
		endif;
	}
	
	
	
	
	function maybe_adjust_variations( $variation = '', $obj = '' , $variation_obj  = '') { 
		
		if (isset($_COOKIE['country'])) { 
			$country = $_COOKIE['country']; 
		} else { 
			$country = ""; 
		}
			
		foreach( $this->get_countries() as $group => $element ) {
		
			if ( !in_array($country, $element['countries'])  ) 
				continue;
				

			$price = $this->maybe_return_variation_price( '', $variation_obj );
			
			$variation['price_html'] = '<span class="price">' . $price . '</span>';

		}
		
		return $variation;
	}
	
	
	// For WooCommerce 2.x flow, to ensure product is visible as long as a group price is set
	function variation_is_visible( $visible, $vid ) {
		global $product;

		if ( !isset( $product->children ) || count( $product->children ) <= 0 )
			return $visible;

		$variation = new sw_pbc_dummy_variation();

		$variation->variation_id = $vid;

		$res = $this->maybe_return_variation_price( 'xxxxx', $variation );

		if ( !isset( $res ) || empty( $res ) || '' == $res )
			$res = false;
		else
			$res = true;

		return $res;
	}
	
	
	
	
	function maybe_return_variation_price_empty( $price, $_product ) {
		global $product;
		
		if (isset($_COOKIE['country'])) { 
			$country = $_COOKIE['country']; 
		} else { 
			$country = ""; 
		}

		foreach( $this->get_countries() as $group => $element ) {
			
			if ( !in_array($country, $element['countries'])  ) 
				continue;

			$min_variation_wholesale_price = get_post_meta( $_product->id, 'min_variation_' . $group . '_price' , true );
			
			$max_variation_wholesale_price = get_post_meta( $_product->id, 'max_variation_' . $group . '_price', true );

			if ( $min_variation_wholesale_price !== $max_variation_wholesale_price ):
				$price = '<span class="from">' . __( 'From:', 'woocomerce-price-by-country') . ' ' .  wc_price( $min_variation_wholesale_price ) . ' </span>';
			else:
				$price = '<span class="from">' . wc_price( $min_variation_wholesale_price ) . ' </span>';
			endif;
		}
		
		return $price;
	}
	
	
	
	
	// Handles getting prices for variable products
	// Used by woocommerce_variable_add_to_cart() function to generate Javascript vars that are later 
	// automatically injected on the public facing side into a single product page.
	// This price is then displayed when someone selected a variation in a dropdown
	function maybe_return_variation_price( $price, $_product ) {
		global $product; // parent product object - global


		if (isset($_COOKIE['country'])) { 
			$country = $_COOKIE['country']; 
		} else { 
			$country = ""; 
		}

		// Sometimes this hook runs when the price is empty but wholesale price is not, 
		// So check for that and handle returning a price for archive page view
		// $attrs = $_product->get_attributes();

		
		$is_variation = $_product->is_type( 'variation' );

		if ( !$is_variation )
			$is_variation = $_product->is_type( 'variable' );


		if ( !isset( $_product->variation_id ) && !$is_variation ) 
			    return $price;

				
		foreach( $this->get_countries() as $group => $element ) {
		
			// validacion de la cookie
			if ( $is_variation && in_array($country, $element['countries']) ) { 
				$price = wc_price( get_post_meta( $_product->variation_id, '_' . $group . '_price', true ) );
				return $price;
			}
		}
		
		foreach( $this->get_countries() as $group => $element ) { 
		
			// validacion de la cookie
			if ( in_array($country, $element['countries']) )  { 

					$wholesale = get_post_meta( $_product->variation_id, '_' . $group . '_price', true );

					if ( intval( $wholesale ) > 0 ) 
						$product->product_custom_fields[ '_' . $group . '_price'] = array( $wholesale );

					if ( is_array( $product->product_custom_fields[ '_' . $group . '_price' ] ) && $product->product_custom_fields[ '_' . $group . '_price'][0] > 0 ) {

						$price = wc_price( $product->product_custom_fields[ '_' . $group . '_price'][0] );

					} elseif ( $product->price === '' ) 

						$price = '';

					elseif ($product->price == 0 ) 

						$price = __( 'Free!', 'woocomerce-price-by-country' );
			} 

		}
		
		return $price;
	}
	
	
	
	function process_product_meta( $post_id, $post = '' ) {
		
		foreach( $this->get_countries() as $group => $element ) {

			if ( '' !==  stripslashes( $_POST[ $group . '_price'] ) )
				update_post_meta( $post_id, '_' . $group . '_price', stripslashes( $_POST[ $group . '_price' ] ) );
			else
				delete_post_meta( $post_id, '_' . $group . '_price' );

		}
	}
	
	
	// process variable product meta
	function process_product_meta_variable( $post_id ) {

		/*$this->get_roles();
		
		if ( empty( $this->roles ) )
			return;
			*/

		$variable_post_ids = $_POST['variable_post_id'];
		
		if ( empty( $variable_post_ids ) )
			return;
		
		foreach( $this->get_countries() as $group => $element ) {  

			foreach( $variable_post_ids as $key => $id ) { 
			
				if ( empty( $id ) || absint( $id ) <= 0 ) 
					continue;
				
				//if ( '' == $_POST[ $role .  '_price' ][ $key ] )
				//	continue;
					
				update_post_meta( $id, '_' . $group	 . '_price', $_POST[ $group .  '_price' ][ $key ] );

			}

		}

		$post_parent = $post_id;
		
		$children = get_posts( array(
				    'post_parent' 	=> $post_parent,
				    'posts_per_page'=> -1,
				    'post_type' 	=> 'product_variation',
				    'fields' 		=> 'ids'
			    ) );

		$lowest_price = '';

		$highest_price = '';

		if ( $children ) {

			foreach( $this->get_countries() as $group => $element ) {  
			
				foreach ( $children as $child ) {
				
					$child_price = get_post_meta( $child, '_' . $group . '_price', true );
					
					
					
					if ( is_null( $child_price ) ) continue;
		
					// Low price
					if ( !is_numeric( $lowest_price ) || $child_price < $lowest_price ) {
						$lowest_price = $child_price;
						/*var_dump('detro');
						var_dump($group);*/
					} else {
						$lowest_price = $child_price;
					}
					
					// High price
					if ( $child_price > $highest_price )
						$highest_price = $child_price;
						
						
					/*
					// debug
					echo '<pre>';
					var_dump($group);
					var_dump($highest_price);
					var_dump($lowest_price);
					var_dump($child_price);
					echo '</pre>';	*/
						
				}
				
				update_post_meta( $post_parent, '_' . $group . '_price', $lowest_price );
				
				update_post_meta( $post_parent, 'min_variation_' . $group . '_price' , $lowest_price );
				
				update_post_meta( $post_parent, 'max_variation_' . $group . '_price', $highest_price );

			}
			// debug
			//die();

		}
		
	}
		
	
	
	
	function bulk_edit() { 
			
		foreach( $this->get_countries() as $group => $element ) {
		?>
			<option value="<?php echo $group ?>_price"><?php _e( $element['name'] . ' Price', 'woocomerce-price-by-country' ); ?></option>
		<?php
		}
	}
	
	
	
	function add_variable_attributes( $loop, $variation_data ) { 
		
		foreach( $this->get_countries() as $group => $element ) {  
			$wprice = get_post_meta( $variation_data['variation_post_id'], '_' . $group . '_price', true );

			if ( !$wprice )
				$wprice = '';
			?>
			<tr>
				<td>
					<div>
					<label><?php echo $element['name']; echo ' ('.get_woocommerce_currency_symbol().')'; ?> <a class="tips" data-tip="<?php _e( 'Enter the price for ', 'woocomerce-price-by-country' ); echo $element['name'] ?>" href="#">[?]</a></label>
					<input class="<?php echo $group ?>_price" type="number" size="99" name="<?php echo $group ?>_price[<?php echo $loop; ?>]" value="<?php echo $wprice ?>" step="any" min="0" placeholder="<?php _e( 'Set price ( optional )', 'woocomerce-price-by-country' ) ?>"/>
					</div>
				</td>
			</tr>
			<?php
		}
	}
	
	
	
	
	function add_simple_price() { 
		global $thepostid;
		
		function doer_of_stuff() {
		  return new WP_Error( 'broke',  __( '<strong>Woocommerce Price By Country</strong> - You need to setup some country groups before enter, you can add some <a target="_blank" href="'.admin_url( 'admin.php?page=wc-settings&tab=integration&section=price_by_country' ).'">here</a>', 'woocomerce-price-by-country' ));
		    
		}
		
		$return = doer_of_stuff();
		
		
		if($this->get_countries()){
			
			foreach( $this->get_countries() as $group => $element ) { 
			
				$wprice = get_post_meta( $thepostid, '_' . $group . '_price', true );
			
				woocommerce_wp_text_input( array( 'id' => $group . '_price', 'class' => 'wc_input_price short', 'label' => __('Price for', 'woocomerce-price-by-country').' '.$element['name'] . ' (' . get_woocommerce_currency_symbol() . ')', 'description' => '', 'type' => 'number', 'custom_attributes' => array(
							'step' 	=> 'any',
							'min'	=> '0'
						), 'value' => $wprice ) );
						
			}
		} elseif(is_wp_error( $return )){
			$error_string = $return->get_error_message();
			echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
		}
	}
	
	
	
	
	function get_countries() {

		$settings = get_option( 'woocommerce_price_by_country_settings' );

		
		if ( empty( $settings ) ):
			return;
		else:
			return unserialize($settings);
		endif;
	}
	
	
	function wpbc_is_wcml_active (){
		if ( in_array( 'woocommerce-multilingual/wpml-woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		} else {
	        return false;     
		}
	}
	

}

// end Class


function wpbc_no_woo_warning(){
    ?>
    <div class="message error"><p><?php printf(__('Woocomerce Price by Country is enabled but not effective. It requires <a href="%s">WooCommerce</a> in order to work.', 'woocomerce-price-by-country'), 
        'http://www.woothemes.com/woocommerce/'); ?></p></div>
    <?php
}


if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	$wc_price_by_country = new woocommerce_price_by_country();
} else {
	add_action('admin_notices', 'wpbc_no_woo_warning');
        return false;     
}

class sw_pbc_dummy_variation {

	function is_type() {
		return true;
	}

}

add_action( 'plugins_loaded', 'sw_pbc_init', 1 );

function sw_pbc_init() { 

	require_once( dirname( __FILE__ ) . '/class-woocommerce-price-by-country-settings.php' );

	add_action( 'woocommerce_integrations', 'sw_pbc_pricing_init'  );
}


function sw_pbc_pricing_init( $integrations ) {

	$integrations[] = 'Woocomerce_Price_by_Country_Settings';
	
	return $integrations;
}


//////////////////////////////////// New

/* USER-AGENTS Tnks to iamandrus ## http://stackoverflow.com/a/6524325 ##
	========================================================================== */
	function check_user_agent ( $type = NULL ) {
	        $user_agent = strtolower ( $_SERVER['HTTP_USER_AGENT'] );
	        if ( $type == 'bot' ) {
	                // matches popular bots
	                if ( preg_match ( "/googlebot|adsbot|yahooseeker|yahoobot|msnbot|watchmouse|pingdom\.com|feedfetcher-google/", $user_agent ) ) {
	                        return true;
	                        // watchmouse|pingdom\.com are "uptime services"
	                }
	        } else if ( $type == 'browser' ) {
	                // matches core browser types
	                if ( preg_match ( "/mozilla\/|opera\//", $user_agent ) ) {
	                        return true;
	                }
	        } else if ( $type == 'mobile' ) {
	                // matches popular mobile devices that have small screens and/or touch inputs
	                // mobile devices have regional trends; some of these will have varying popularity in Europe, Asia, and America
	                // detailed demographics are unknown, and South America, the Pacific Islands, and Africa trends might not be represented, here
	                if ( preg_match ( "/phone|iphone|itouch|ipod|symbian|android|htc_|htc-|palmos|blackberry|opera mini|iemobile|windows ce|nokia|fennec|hiptop|kindle|mot |mot-|webos\/|samsung|sonyericsson|^sie-|nintendo/", $user_agent ) ) {
	                        // these are the most common
	                        return true;
	                } else if ( preg_match ( "/mobile|pda;|avantgo|eudoraweb|minimo|netfront|brew|teleca|lg;|lge |wap;| wap /", $user_agent ) ) {
	                        // these are less common, and might not be worth checking
	                        return true;
	                }
	        }
	        return false;
	}


function wpbc_footer_chosen_selector_script(){

		$inline_js1 = "<!-- WooCommerce price by country Selector JavaScript-->\n<script type=\"text/javascript\">\n jQuery(document).ready(function($) { \n";
   		$inline_js1 .= " $('select#pbc_country_selector').chosen( { search_contains: true } ); \n";
		$inline_js1 .= " $('select#pbc_country_selector').val($.cookie('country')); \n";
		$inline_js1 .= " $('select#pbc_country_selector').trigger('chosen:updated') \n";
		
		$inline_js1 .= " $('select#pbc_country_selector').on('change', function(evt, params) { \n";
			$inline_js1 .= " var valueSel = $('#pbc_country_selector').val(); \n";
			$inline_js1 .= " $.cookie('country', valueSel, { expires: 7 , path: '/' }); \n";
			$inline_js1 .= " window.location.reload(true);";
		$inline_js1 .= "}); \n";
		
	$inline_js1 .="\n});\n</script> \n";
		
	echo $inline_js1;
}


function wpbc_footer_selector_script(){

		$inline_js1 = "<!-- WooCommerce price by country Selector JavaScript-->\n<script type=\"text/javascript\">\n jQuery(document).ready(function($) { \n";
		
			$inline_js1 .= "$('select#pbc_country_selector').change(function(){ \n";
			    $inline_js1 .= "var valueSel2 = $(this).val(); \n";
				$inline_js1 .= "$.cookie('country', valueSel2, { expires: 7 , path: '/' }); \n";
				$inline_js1 .= " window.location.reload(true);";
			$inline_js1 .= "}); \n";
	$inline_js1 .="\n});\n</script> \n";
		
	echo $inline_js1;
}





add_action( 'get_pbc_country_dropdown', 'pbc_country_dropdown' );
function pbc_country_dropdown() {
	
	global $woocommerce;
	
	
	$alowedType = get_option( 'woocommerce_allowed_countries' );
	
	
	$c = new WC_Countries();
	
	if($alowedType == 'specific'):
		
		$woocommerce_specific_allowed_countries = get_option('woocommerce_specific_allowed_countries');
		
		if($woocommerce_specific_allowed_countries):
			$country_list = unserialize($woocommerce_specific_allowed_countries);
		endif;
	endif;
	
	//var_dump($c);
		
	if ( !isset( $s ) )
		$s = array();
		
	$ismobile = check_user_agent('mobile');
	if($ismobile) {
		add_action(	'wp_footer','wpbc_footer_selector_script' , 1000);
	} else {
		if ( get_option( 'woocommerce_enable_chosen' ) == 'yes' ) {
			add_action(	'wp_footer','wpbc_footer_chosen_selector_script' , 1000);
		} else {
			add_action(	'wp_footer','wpbc_footer_selector_script' , 1000);
		}
	}	
	$settings = get_option( 'woocommerce_global_price_by_country_settings' );
	$extraClasses = $settings['wpbc_price_countrySelectorClass'];
		
	$output .='<select id="pbc_country_selector" name="pbc_country_selector" class="chosen_select '.$extraClasses.'">';
	
	if($alowedType == 'specific'):
		foreach( $woocommerce_specific_allowed_countries as $k => $v ) {
			$output .='<option value="' . $v . '" ' . selected( $_COOKIE['country'], $v , false) . '>' . $c->countries[$v] . '</option> ';
		}
	else:
		foreach( $c->countries as $k => $v ) {
			$output .='<option value="' . $k . '" ' . selected( $_COOKIE['country'], $k , false) . '>' . $v . '</option> ';
		}
	
	endif;
	
	
	$output .='</select>';
	
	echo $output;
}

add_action( 'after_setup_theme', 'pbc_get_permited_countries' );


function pbc_get_permited_countries() {
	
	if (isset($_COOKIE['country'])) { 
		$country = $_COOKIE['country']; 
	} else { 
		$country = ""; 
	}
	
	$settings = get_option( 'woocommerce_price_by_country_settings' );
	
	if($settings):
	
		$settingsArr = unserialize($settings);
		
		if(is_array($settingsArr)):
	
			foreach( $settingsArr  as $group => $element ):
				
				if($element['countries']):
					foreach($element['countries'] as $key => $countryL):
						if($countryL == $country):
							$inList = 'yes';
						endif;
					endforeach;
				endif;
				
			endforeach;
		endif;
	
	endif;
	if (!isset($inList)) { $inList = "outside"; }
	
	$output = ($inList == 'yes') ? 'inside' : 'outside';
	
	return $output;
	
}


function get_country_alt() { // thanks to murphvienna -- https://wordpress.org/support/profile/murphvienna
	
    if (isset($_COOKIE) && isset($_COOKIE['country']) && !empty($_COOKIE['country'])) {
        # user has set a cookie - use its value
        return $_COOKIE['country'];
    } else {
        # do a country lookup by GeoIP, cURL, or just set a default value.
        $country = 'DE';

        # then set this country as a cookie directly with PHP.
        # the cookie will be valid from next request on.
        setcookie('country', $country, time()+86400, '/');
        return $country;
    }
}



$countries = pbc_get_permited_countries();

if($countries == 'outside'){
	add_action('init','pbc_remove_loop_button');
}

function pbc_remove_loop_button(){
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	
	function sw_custom_variation_price( $price, $product ) {
	 
	 	$settingsGlobal = get_option( 'woocommerce_global_price_by_country_settings' );
		$target_product_types = array( 
			'variable',
			'simple'
		);
	 
		if ( in_array ( $product->product_type, $target_product_types ) ) {
			// if variable product return and empty string
			return $settingsGlobal['wpbc_outsideMesage'];
		} else {
			// return normal price
			return $price;
		}
	}
	add_filter('woocommerce_get_price_html', 'sw_custom_variation_price', 10, 2);
	
}


add_action( 'wp_print_scripts', 'pbc_dequeueScripts', 99 );
 
function pbc_dequeueScripts() {

    //first check that woo exists to prevent fatal errors
    if ( function_exists( 'is_woocommerce' ) ) {
        //dequeue scripts and styles
        if ( is_checkout() ) {
            wp_dequeue_script( 'wc-chosen' );
        }
    }
 
}


?>
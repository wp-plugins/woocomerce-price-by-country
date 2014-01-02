<?php 
/*
Plugin Name: WooCommerce Price by Country
Plugin URI:  http://www.sweethomes.es
Description: Allows you to set the prices of a product according to the user's country
Version: 0.1
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

		
	}

	
	function wpbc_enqueue_scripts() {
		
		wp_enqueue_script('google-jsapi', 'https://www.google.com/jsapi');
		wp_enqueue_script('jquery-cookie', '//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.0/jquery.cookie.min.js');

	}


	function wpbc_footer_script(){
	
		$c = new WC_Countries();
	
		$basecountry = $c->get_base_country();
		
   		$inline_js = "<!-- WooCommerce price by country JavaScript-->\n<script type=\"text/javascript\">\n jQuery(document).ready(function($) {";
   		$inline_js .= "\n if (google.loader.ClientLocation) {\n
					var country_code = google.loader.ClientLocation.address.country_code;\n
						$.cookie('country', country_code, { expires: 7 });\n
					}else{\n 
						$.cookie('country', '".$basecountry."', { expires: 7 });\n
					}\n";
		$inline_js .="\n});\n</script>\n";
   		
		echo $inline_js;
	}



	function get_item_from_session( $item_data = '' ) { 
		global $woocommerce;
		
		$country = $_COOKIE['country'];
		$countries = $this->get_countries();
		if ( empty( $countries ) )
			return;
			
		foreach( $this->get_countries() as $group => $element ) {
		
			if ( !in_array($country, $element['countries'])  ) 
				continue;

			$_product = get_product( $item_data['product_id'] ); 

			if ( isset( $item_data['variation_id'] ) && 'variable' == $_product->product_type ) 
				$level_price = get_post_meta( $item_data['variation_id' ], '_' . $group . '_price', true );

			else if ( 'simple' == $_product->product_type || 'external' == $_product->product_type )
				$level_price = get_post_meta( $item_data['product_id' ], '_' . $group . '_price', true );


			else // all other product types - possibly incompatible with custom product types added by other plugins\
				$level_price = get_post_meta( $item_data['product_id' ], '_' . $group . '_price', true );

			if ( $level_price ) { 

				$item_data['data']->price = $level_price;
				
				$item_data['data']->regular_price = $level_price;


			}

		}

		return $item_data;
	}
	
	
	
	
	function maybe_return_wholesale_price( $price, $_product ) { 
		
		$country = $_COOKIE['country'];

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


				$price = '<span class="from">' . __('From:', 'woocomerce-price-by-country') . ' </span>' . woocommerce_price( $min_price );

			} elseif ( $_product->is_type( $vtype ) ) {

				$wprice_min = get_post_meta( $_product->id, 'min_variation_' . $group . '_price', true );
				
				$wprice_max = get_post_meta( $_product->id, 'max_variation_' . $group . '_price', true );

				if ( $wprice_min !== $wprice_max )
					$price = '<span class="from">' . __( 'From:', 'woocomerce-price-by-country') . $wprice_min . ' </span>';

				if ( !empty( $wprice_min ) && !empty( $wprice_max ) && $wprice_min == $wprice_max ) 
					return $price;
				
				else if ( !empty( $wprice_min ) )
					$price = '<span class="from">' . __( 'From:', 'woocomerce-price-by-country') . ' ' . woocommerce_price( $wprice_min ) . ' </span>';
					
				else { 
				
					$wprice_min = get_post_meta( $_product->id, '_min_variation_regular_price', true );
					
					$wprice_max = get_post_meta( $_product->id, '_max_variation_regular_price', true );
				
					if ( $wprice_min !== $wprice_max )
						$price = '<span class="from">' . __( 'From:', 'woocomerce-price-by-country') . $wprice_min . ' </span>';

					if (  !empty( $wprice_min ) && !empty( $wprice_max ) && $wprice_min == $wprice_max ) 
						return $price;
					
					else if ( !empty( $wprice_min ) )
						$price = '<span class="from">' . __( 'From:', 'woocomerce-price-by-country') . ' ' . woocommerce_price( $wprice_min ) . ' </span>';

				}

			} else { 

				$wprice_min = get_post_meta( $_product->id, '_' . $group . '_price', true );
					
				if ( isset( $wprice_min ) && $wprice_min > 0 )
					$price = woocommerce_price( $wprice_min );

				elseif ( '' === $wprice_min ) {
				
					$price = get_post_meta( $_product->id, '_price', true );
					if ( !empty( $price ) )
						$price = woocommerce_price( $price ); 
						
				} elseif ( 0 == $wprice_min ) 
					$price = __( 'Free!', 'woocomerce-price-by-country' );
				
				if ( !empty( $wprice_min ) && 'yes' == $this->settings['show_regular_price'] || 'yes' == $this->settings['show_savings'] ) { 
				
					$rprice = get_post_meta( $_product->id, '_regular_price', true );

					if ( empty( $wprice_min ) )
						continue; 
						
					if ( floatval( $rprice ) > floatval( $wprice_min ) && 'yes' == $this->settings['show_regular_price'] ) 
						$price .= '<br><span class="normal_price">' . $this->settings['show_regular_price_label'] . ' ' . woocommerce_price( $rprice ) . '</span>';
					
					$savings = ( floatval( $rprice ) - floatval( $wprice_min ) );
					
					if ( ( $savings < $rprice ) && 'yes' == $this->settings['show_savings'] ) 
						$price .= '<br><span class="normal_price savings">' . $this->settings['show_savings_label'] . ' ' . woocommerce_price( $savings ) . '</span>';
						
				}
			}

		}

		//$price = '0000';

		return $price; 
	}
	
	
	
	
	function is_purchasable( $purchasable, $_product ) { 
		
		$country = $_COOKIE['country'];
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
		
		$country = $_COOKIE['country'];
		
		$countries = $this->get_countries();
		
		if ( empty( $countries ) )
			return $price;
			
		foreach( $this->get_countries() as $group => $element ) {
		
			if ( !in_array($country, $element['countries'])  ) 
				continue;

			if ( isset( $_product->variation_id )  ) {

				if ( isset( $_product->variation_id ) ) 
					$wholesale = get_post_meta( $_product->variation_id, '_' . $group . '_price', true );
				else 
					$wholesale = '';

				if ( intval( $wholesale ) > 0 ) 
					$_product->product_custom_fields[ '_' . $group . '_price' ] = array( $wholesale );


				if ( isset( $_product->product_custom_fields[ '_' . $group . '_price' ] ) && is_array( $_product->product_custom_fields[ '_' . $group . '_price'] ) && $_product->product_custom_fields[ '_' . $group . '_price'][0] > 0 ) {

					$price = $_product->product_custom_fields[ '_' . $group . '_price'][0];

				} elseif ( $_product->price === '' ) 

					$price = '';

				elseif ($_product->price == 0 ) 

					$price = __( 'Free!', 'woocomerce-price-by-country' );

				return $price; 

			}

			$rprice = get_post_meta( $_product->id, '_' . $group . '_price', true );

			if ( !empty( $rprice ) )
				return $rprice;
		}
		//$price = '0000';
		return $price;
	}
	
	
	
	
	function maybe_adjust_variations( $variation = '', $obj = '' , $variation_obj  = '') { 
		
		$country = $_COOKIE['country'];
			
		foreach( $this->get_countries() as $group => $element ) {
		
			if ( !in_array($country, $element['countries'])  ) 
				continue;
				

			$price = $this->maybe_return_variation_price( '', $variation_obj );
			
			$variation['price_html'] = '<span class="price">' . $price . '</span>';

			if ( ( 'yes' == $this->settings['show_regular_price'] || 'yes' == $this->settings['show_savings'] ) ) { 
	
				$reg_price = get_post_meta( $variation['variation_id'], '_regular_price', true );

				$group_price = get_post_meta( $variation['variation_id'], '_' . $group . '_price', true );

				if ( ( floatval( $role_price ) < floatval( $reg_price ) ) && 'yes' == $this->settings['show_regular_price'] ) 
					$variation['price_html']  .= '<br><span class="price normal_price">' . $this->settings['show_regular_price_label'] . ' <span class="amount">' . woocommerce_price( $reg_price ) . '</span></span>';
				
				$savings = ( floatval( $reg_price ) - floatval( $group_price ) );

				if ( $savings < $reg_price && 'yes' == $this->settings['show_savings'] ) 
					$variation['price_html']  .= '<br><span class="price normal_price savings">' . $this->settings['show_savings_label'] . ' <span class="amount">' . woocommerce_price( $savings ) . '</span></span>';
					
			}


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
		
		$country = $_COOKIE['country'];

		foreach( $this->get_countries() as $group => $element ) {
			
			if ( !in_array($country, $element['countries'])  ) 
				continue;

			$min_variation_wholesale_price = get_post_meta( $_product->id, 'min_variation_' . $group . '_price' , true );
			
			$max_variation_wholesale_price = get_post_meta( $_product->id, 'max_variation_' . $group . '_price', true );

			if ( $min_variation_wholesale_price !== $max_variation_wholesale_price )
				$price = '<span class="from">' . __( 'From:', 'woocomerce-price-by-country') . ' ' .  woocommerce_price( $min_variation_wholesale_price ) . ' </span>';
				
			else 
				$price = '<span class="from">' . woocommerce_price( $min_variation_wholesale_price ) . ' </span>';
		}
		
		return $price;
	}
	
	
	
	
	// Handles getting prices for variable products
	// Used by woocommerce_variable_add_to_cart() function to generate Javascript vars that are later 
	// automatically injected on the public facing side into a single product page.
	// This price is then displayed when someone selected a variation in a dropdown
	function maybe_return_variation_price( $price, $_product ) {
		global $product; // parent product object - global


		$country = $_COOKIE['country'];

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
				$price = woocommerce_price( get_post_meta( $_product->variation_id, '_' . $group . '_price', true ) );
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

						$price = woocommerce_price( $product->product_custom_fields[ '_' . $group . '_price'][0] );

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
	
	
	
	
	function process_product_meta_variable( $post_id ) {
		
		$variable_post_ids = $_POST['variable_post_id'];
		
		if ( empty( $variable_post_ids ) )
			return;
		
		foreach( $this->get_countries() as $group => $element ) {  

			foreach( $variable_post_ids as $key => $id ) { 
			
				if ( empty( $id ) || absint( $id ) <= 0 ) 
					continue;
					
				update_post_meta( $id, '_' . $group . '_price', floatval( $_POST[ $group .  '_price' ][ $key ] ) );
			
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

					if ( !$child_price ) continue;
		
					// Low price
					if ( !is_numeric( $lowest_price ) || $child_price < $lowest_price ) $lowest_price = $child_price;

					
					// High price
					if ( $child_price > $highest_price )
						$highest_price = $child_price;
				}
				
				update_post_meta( $post_parent, '_' . $group . '_price', $lowest_price );
				
				update_post_meta( $post_parent, 'min_variation_' . $group . '_price' , $lowest_price );
				
				update_post_meta( $post_parent, 'max_variation_' . $group . '_price', $highest_price );

			}


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
		
		//print_r($this->get_countries());
			
		foreach( $this->get_countries() as $group => $element ) { 
		
			$wprice = get_post_meta( $thepostid, '_' . $group . '_price', true );
		
			woocommerce_wp_text_input( array( 'id' => $group . '_price', 'class' => 'wc_input_price short', 'label' => __('Price for', 'woocomerce-price-by-country').' '.$element['name'] . ' (' . get_woocommerce_currency_symbol() . ')', 'description' => '', 'type' => 'number', 'custom_attributes' => array(
						'step' 	=> 'any',
						'min'	=> '0'
					), 'value' => $wprice ) );
					
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

}

function wpbc_no_woo_warning(){
    ?>
    <div class="message error"><p><?php printf(__('Woocomerce Price by Country is enabled but not effective. It requires <a href="%s">WooCommerce</a> in order to work.', 'woocomerce-price-by-country'), 
        'http://www.woothemes.com/woocommerce/'); ?></p></div>
    <?php
}


if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	$woocommerce_price_by_country = new woocommerce_price_by_country();
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
?>
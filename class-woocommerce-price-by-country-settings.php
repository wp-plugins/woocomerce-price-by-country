<?php 
/*
Version: 0.36
Author: Sweet Homes
Author URI: http://www.sweethomes.es
Email: info@sweethomes.es
*/
/*********************************
Copyright (c) 2013 - sweethomes.es
**********************************/
	
if ( !defined( 'ABSPATH' ) ) die;

if ( !class_exists( 'WC_Integration' ) ) 
	return;
	
class Woocomerce_Price_by_Country_Settings extends WC_Integration {
	
	
	function __construct() {

		$this->id = 'price_by_country';
		$this->method_title = __( 'Price by Country', 'woocomerce-price-by-country' );
		//$this->method_description = __( 'Add price groups by country', 'woocomerce-price-by-country');

		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_integration_' . $this->id , array( &$this, 'process_admin_options') );
		add_action( 'wp_ajax_wpbc_get_uniqid', array( &$this, 'get_id' ) );
	}
	
	
	function init_form_fields() {
		

	}
	
	
	function get_id() { 
	
		die( json_encode( array( 'id' => uniqid() ) ) );
	
	}
	
	
	function admin_options() { 
		?>

		<h3><?php echo isset( $this->method_title ) ? $this->method_title : __( 'Settings', 'woocomerce-price-by-country' ) ; ?></h3>

		<?php echo isset( $this->method_description ) ? wpautop( $this->method_description ) : ''; ?>

		<?php
		///////////////////////////////////////////
								
		$settingsForEdit = get_option( 'woocommerce_'. $this->id . '_settings' );
		$oldSettingsForEdit = unserialize($settingsForEdit);
		
		if(is_array($oldSettingsForEdit)):
		
			$ArrayForJson = array();
			foreach($oldSettingsForEdit as $key => $element):
				$ArrayForJson[$key] = $element['countries'];
			endforeach;
		
		
		endif;
		
		$JsonSetings = json_encode($ArrayForJson);
		
		//print_r($JsonSetings);	
		?>
		<table id="wpbc_settings_table" class="form-table" data-json-settings='<?php echo $JsonSetings; ?>'>
			<?php $this->min_settings() ?>
			<?php $this->generate_settings_html(); ?>
		</table>

		<div><input type="hidden" name="section" value="<?php echo $this->id; ?>" /></div>

		<?php
	}
	
	
	
	function min_settings() { 
		global $woocommerce;
		
		$settingsGlobal = get_option( 'woocommerce_global_'. $this->id . '_settings' );
		
		$defaults = array ( 'wpbc_price_checkoutselector' => 'billing');
		
		if(empty($settingsGlobal)){
			$settingsGlobal = wp_parse_args( $settings, $defaults );
		}
		
		$c = new WC_Countries();
		
		if ( !isset( $s ) )
			$s = array();
		
		
		?>
		<script>
		jQuery( document ).ready( function( $ ) { 
			$( "#wpbc_add_new_group" ).click( function() { 
			
				var uid = null;
				
				$.post( ajaxurl, { action:'wpbc_get_uniqid' }, function( data ) { 
				
					try { 
						var j = $.parseJSON( data );
						
						uid = j.id;
						
					} catch( err ) { 
				
						alert( '<?php _e( 'An error occurred. Try again.', 'woocomerce-price-by-country' )?>');
						return false;
				
					}
				
					var html = '\
						<tr>\
						<td width="10%">\
							<label class="">\
								<input type="text" name="group_level_name[group_level_' + uid + ']" placeholder="<?php _e( 'Enter a group name','woocomerce-price-by-country' )?>" value="">\
							</label>\
						</td>\
						<td>\
							<div style="" class="group_states_box">\
								<select name="group_level_country[group_level_' + uid + '][]" multiple="multiple" class="chosen_select">\
								<?php
								foreach( $c->countries as $k => $v ) {
									if ( in_array( $k, (array)$s ) )
										$selected = ' selected';
									else
										$selected = '';
									echo '<option value="' . $k . '"' . $selected . '>' . $v . '</option>\ ';
								}
								?>
								</select>\
							</div>\
							<button type="button" class="button all_button" style="display:inline-block;"><?php _e('All'); ?></button>\
							<button type="button" class="button none_button" style="display:inline-block;"><?php _e('None'); ?></button>\
							<button type="button" class="button es_button" style="display:inline-block;"><?php _e('ES'); ?></button>\
							<button type="button" class="button usa_button" style="display:inline-block;"><?php _e('USA'); ?></button>\
							<button type="button" class="button eu_button" style="display:inline-block;"><?php _e('EU'); ?></button>\
						</td>\
						<td width="10%">\
						</td>\
					</tr>\
					';
					
					$( '.group_table' ).append( html );
					$("select.chosen_select").chosen();
					return false;
				
				});
				

			})
			
			$( ".edit_group" ).click( function() { 
			
				var uid = $(this).attr('id'),
					prev = $(this).prev('.group-name'),
					prevName = prev.val(),
					parentTrLinea = $(this).parents('tr').eq(0);
				
				
				
				$.post( ajaxurl, { action:'wpbc_get_uniqid' }, function( data ) { 
				
					var settings = $('#wpbc_settings_table').attr('data-json-settings'),
						jsonSettings = JSON.parse(settings),
						currentEdit = jsonSettings[uid];
					
						console.log(currentEdit);
					
					var html = '\
						<td width="10%">\
							<label class="group-name">\
								<input type="text"  name="group_level_name[' + uid + ']" placeholder="<?php _e( 'Enter a group name','woocomerce-price-by-country' )?>" value="'+prevName+'">\
							</label>\
						</td>\
						<td>\
							<div style="width:300px;" class="group_states_box">\
								<select name="group_level_country['+ uid + '][]" multiple="multiple" class="chosen_select">\ ';
					
						var html1 = '';		
						
						
						<?php foreach( $c->countries as $k => $v ) { ?>
							
							var founded = $.inArray( "<?php echo $k ?>", currentEdit );
							var selected = '';
							
							if(founded != -1){
								selected = 'selected';
							} else {
								selected = '';
							}
							html1 +=  '<option value="<?php echo $k ;?>" '+selected+'><?php echo  $v ?></option>\ ';
						
						<?php } ?>
					
						var html2 = '</select>\
							</div>\
							<button type="button" class="button all_button" style="display:inline-block;"><?php _e('All'); ?></button>\
							<button type="button" class="button none_button" style="display:inline-block;"><?php _e('None'); ?></button>\
							<button type="button" class="button es_button" style="display:inline-block;"><?php _e('ES'); ?></button>\
							<button type="button" class="button usa_button" style="display:inline-block;"><?php _e('USA'); ?></button>\
							<button type="button" class="button eu_button" style="display:inline-block;"><?php _e('EU'); ?></button>\
						</td>\
						<td width="10%">\
						</td>\
					';
					
					var res = html.concat(html1,html2);
					
					
					parentTrLinea.html( res );
					$("select.chosen_select").chosen({width: "95%"});
					return false;
				
				});
				

			})
			
			
			
		})
		</script>
		
		<tr valign="top">
			<th class="titledesc" scope="row" colspan="2">
				<h5 style="margin:0"><?php _e( 'Selects which is the primary modifier price', 'woocomerce-price-by-country' )?>:</h5>
			</th>
		</tr>
		
		<tr valign="top">
			<th class="titledesc" scope="row">
				<label for="groups">
				<?php _e( 'Billing address', 'woocomerce-price-by-country' )?>
				</label>
			</th>
			<td class="forminp wpbc_groups">	
			
				<input type="radio" name="wpbc_price_checkoutselector" value="billing" <?php checked( $settingsGlobal['wpbc_price_checkoutselector'], 'billing', true ) ?>>
				<img class="help_tip tiered" src="<?php echo $woocommerce->plugin_url() ?>/assets/images/help.png" data-tip="<?php _e( 'Billing defines Final Price.', 'woocomerce-price-by-country' )?>">
			</td>
			
		</tr>
		<tr valign="top">
			<th class="titledesc" scope="row">
				<label for="groups">
				<?php _e( 'Shipping address', 'woocomerce-price-by-country' )?>
				</label>
			</th>
			<td class="forminp wpbc_groups">	
			
				<input type="radio" name="wpbc_price_checkoutselector" value="shipping" <?php checked( $settingsGlobal['wpbc_price_checkoutselector'], 'shipping', true ) ?>> 
				<img class="help_tip tiered" src="<?php echo $woocommerce->plugin_url() ?>/assets/images/help.png" data-tip="<?php _e( 'Shipping defines Final Price.', 'woocomerce-price-by-country' )?>">
			</td>
		</tr>
		
		<tr valign="top">
			<th class="titledesc" scope="row">
				<label for="groups">
				<?php _e( 'Country selector class', 'woocomerce-price-by-country' )?>
				</label>
			</th>
			<td class="forminp wpbc_groups">	
			
				<input type="text" name="wpbc_price_countrySelectorClass" value="<?php echo $settingsGlobal['wpbc_price_countrySelectorClass'] ?>"> 
				<img class="help_tip tiered" src="<?php echo $woocommerce->plugin_url() ?>/assets/images/help.png" data-tip="<?php _e( 'Defines extra classe for country selector.', 'woocomerce-price-by-country' )?>">
			</td>
		</tr>
		
		<tr valign="top">
			<th class="titledesc" scope="row">
				<label for="groups">
				<?php _e( 'Message for country outside', 'woocomerce-price-by-country' )?>
				</label>
			</th>
			<td class="forminp wpbc_groups">	
			
				<input type="text" name="wpbc_outsideMesage" value="<?php echo $settingsGlobal['wpbc_outsideMesage'] ?>"> 
				<img class="help_tip tiered" src="<?php echo $woocommerce->plugin_url() ?>/assets/images/help.png" data-tip="<?php _e( 'Message for replacing price in case you customer is not your country groups list.', 'woocomerce-price-by-country' )?>">
			</td>
		</tr>
		
		<tr valign="top">
			<th class="titledesc" scope="row" colspan="2">
				<h5 style="margin:0">
				<?php _e( 'Add price groups by country', 'woocomerce-price-by-country' )?>:
				</h5>
			</th>
		</tr>
		<style>
			.help_tip.tiered { width: 16px; float: none !important; }
			.group_states_box .chzn-container { width: 100% !important; }
		</style>
		
		<tr valign="top">
			<td colspan="2" class="forminp wpbc_groups">	
				<table width="100%" class="group_table">
					<tr>
						<th>
							<strong><?php _e( 'Group Name', 'woocomerce-price-by-country' ) ?></strong>
						</th>
						<th>
							<strong><?php _e( 'Countries', 'woocomerce-price-by-country' ) ?></strong>
						</th>
						<th>
							<strong><?php _e( 'Delete', 'ignitewoo_tiered_pricing' ) ?></strong>
							<img class="help_tip tiered" src="<?php echo $woocommerce->plugin_url() ?>/assets/images/help.png" data-tip="<?php _e( 'Delete a group of price', 'woocomerce-price-by-country' )?>">
						</th>
						<th>
							<strong><?php _e( 'Edit', 'ignitewoo_tiered_pricing' ) ?></strong>
							<img class="help_tip tiered" src="<?php echo $woocommerce->plugin_url() ?>/assets/images/help.png" data-tip="<?php _e( 'Edit group', 'woocomerce-price-by-country' )?>">
						</th>
					</tr>
					<?php
						
					$settingsview = get_option( 'woocommerce_'. $this->id . '_settings' );
					$settingsArray = unserialize($settingsview);

					if (!empty($settingsArray)):
						foreach($settingsArray as $key => $data):
					?>
						<tr>
							<td width="10%">
								<label class="group-name">
									<span><?php echo stripslashes( $data['name'] ) ?></span> 
								</label>
							</td>
							<td>
								<?php echo implode(', ', $data['countries']); ?>
							</td>
							<td width="10%">
								<input type="checkbox" value="<?php echo $key ?>" style="" id="<?php echo $key ?>" name="group_level_delete[<?php echo $key ?>]" class="input-text wide-input "> 
							</td>
							<td width="20%">
								<input type="hidden" class="group-name" name="group-name" value="<?php echo stripslashes( $data['name'] ) ?>">
								<button type="button" class="button edit_group" id="<?php echo $key ?>" value="group_level_edit[<?php echo $key ?>]"><?php _e( 'Edit ', 'woocomerce-price-by-country' )?></button>
							</td>
						</tr>
					<?php 
						endforeach;
						
					endif;
					?>
				</table>
			</td>
		</tr>
		
		<tr>
			<th></th>
			<td><button type="button" class="button" id="wpbc_add_new_group"><?php _e( 'Add New Group', 'woocomerce-price-by-country' )?></button></td>
		
		<script type="text/javascript">
				jQuery('.all_button').live('click', function(){
					var self = jQuery(this),
						parentTD = self.parent();
					parentTD.find('select option').attr("selected","selected");
					parentTD.find('select').trigger('chosen:updated');
					return false;
				});

				jQuery('.none_button').live('click', function(){
					var self = jQuery(this),
						parentTD = self.parent();
					parentTD.find('select option').removeAttr("selected");
					parentTD.find('select').trigger('chosen:updated');
					return false;
				});

				jQuery('.es_button').live('click', function(){
					var self = jQuery(this),
						parentTD = self.parent();
					parentTD.find('option[value="ES"]').attr("selected","selected");
					parentTD.find('select').trigger('chosen:updated');
					return false;
				});

				jQuery('.usa_button').live('click', function(){
					var self = jQuery(this),
						parentTD = self.parent();
					parentTD.find('option[value="US"]').attr("selected","selected");
					parentTD.find('select').trigger('chosen:updated');
					return false;
				});

				jQuery('.eu_button').live('click', function(){
					var self = jQuery(this),
						parentTD = self.parent();
					parentTD.find('option[value="AL"], option[value="AD"], option[value="AM"], option[value="AT"], option[value="BY"], option[value="BE"], option[value="BA"], option[value="BG"], option[value="CH"], option[value="CY"], option[value="CZ"], option[value="DE"], option[value="DK"], option[value="EE"], option[value="ES"], option[value="FO"], option[value="FI"], option[value="FR"], option[value="GB"], option[value="GE"], option[value="GI"], option[value="GR"], option[value="HU"], option[value="HR"], option[value="IE"], option[value="IS"], option[value="IT"], option[value="LT"], option[value="LU"], option[value="LV"], option[value="MC"], option[value="MK"], option[value="MT"], option[value="NO"], option[value="NL"], option[value="PO"], option[value="PT"], option[value="RO"], option[value="RU"], option[value="SE"], option[value="SI"], option[value="SK"], option[value="SM"], option[value="TR"], option[value="UA"], option[value="VA"]').attr("selected","selected");
					parentTD.find('select').trigger('chosen:updated');
					return false;
				});
			</script>
		<?php

		// preset buttons

		
		echo '</tr>';
		
		?>
		<tr>
			<td colspan="2" align="left"><a href="http://www.sweethomes.es" style="text-decoration:none;"><?php echo '<img src="'. plugins_url('sweethomes.png', __FILE__).'" alt="Sweet Homes">'; ?> <span style="color: rgb(0, 0, 0); font: bold 14px/20px Arial,sans-serif; vertical-align: top;">Sweet Homes</span></a> <span style="diplay:block; vertical-align: super;">is a study of online communication based in Barcelona. We have extensive experience developing projects in wordpress and other CMS's </span></td>
		</tr>
		<?php
	}
	
	
	
	
	
	
	function process_admin_options() {

		//parent::process_admin_options();

		
		$OldsettingsGlobal = get_option( 'woocommerce_global_'. $this->id . '_settings' );
		
		if ( empty( $OldsettingsGlobal ) ) {
		
			$OldsettingsGlobal = '';
			add_option( 'woocommerce_global_'. $this->id . '_settings', $OldsettingsGlobal, '', 'yes' );
		}
		
		$OldsettingsGlobal['wpbc_price_checkoutselector'] =  isset( $_POST['wpbc_price_checkoutselector'] ) ? $_POST['wpbc_price_checkoutselector'] : '';
		$OldsettingsGlobal['wpbc_price_countrySelectorClass'] =  isset( $_POST['wpbc_price_countrySelectorClass'] ) ? $_POST['wpbc_price_countrySelectorClass'] : '';
		$OldsettingsGlobal['wpbc_outsideMesage'] =  isset( $_POST['wpbc_outsideMesage'] ) ? $_POST['wpbc_outsideMesage'] : '';
		
		
		/*var_dump($OldsettingsGlobal);
		die();*/
		
		update_option( 'woocommerce_global_'.  $this->id . '_settings', $OldsettingsGlobal );
		
		
		////////////////////////////////////////////////////////////
		$settings = get_option( 'woocommerce_'. $this->id . '_settings' );
		$oldSettings = unserialize($settings);

		if ( empty( $oldSettings ) ) {
			$settings = '';
			add_option( 'woocommerce_'. $this->id . '_settings', $settings, '', 'yes' );
		}
		
		if ((!empty($_POST['group_level_name'])) && (!empty($_POST['group_level_country']))):
		
			$names = $_POST['group_level_name'];
			$countries = $_POST['group_level_country'];
		
			if ($names):
				$rawSettings = array();
				foreach($names as $key => $name):
					$rawSettings[$key]['name'] = $name;
					$rawSettings[$key]['countries'] = $countries[$key];
				endforeach;
			endif;
			
			if ($oldSettings):
				
				$result = array_merge($oldSettings,$rawSettings );
				$newsettings = serialize($result);
				update_option( 'woocommerce_'.  $this->id . '_settings', $newsettings );
			else:	
				$settings = serialize($rawSettings);
				update_option( 'woocommerce_'.  $this->id . '_settings', $settings );	
			endif;
		endif;
		

		if ( !empty( $_POST[ 'group_level_delete' ] ) ) { 
			foreach( $_POST[ 'group_level_delete' ] as $key => $delgroup ) { 
				unset($oldSettings[$delgroup]);
			}
			$newsettings = serialize($oldSettings);
			update_option( 'woocommerce_'.  $this->id . '_settings', $newsettings );
		}
		
	}
	
	
}
	
	
?>
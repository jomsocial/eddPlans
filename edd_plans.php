<?php

/**

 * Plugin Name: EDD Plans

 * Plugin URI: http://peepso.com

 * Description: Offer time-limited plans. For example, you could create a three-month plan, a six-month plan and an annual plan and set a different price level for each.

 * Version: 1.0

 * Author: peepso.com

 * Author URI: peepso.com

 * Text Domain: edd_plans 

 * License: 

 */

 

defined('ABSPATH') or die("No script kiddies please!");

function edd_plans_load_multiple_license() {

	wp_enqueue_script( 'edd_plans_multiple_license', plugins_url() . '/edd_plans/helpers/edd_plans.js', 'jquery', '', true );

}



function edd_plans_multiple_license_row( $key, $args = array(), $post_id ) {

	$edd_plans_multiple_license   	= get_post_meta( $post_id, 'edd_plans_multiple_license', true );

	$length_default 				= isset($edd_plans_multiple_license[$key]['length']) ? $edd_plans_multiple_license[$key]['length'] : '';

	$unit_default 					= isset($edd_plans_multiple_license[$key]['unit']) ? $edd_plans_multiple_license[$key]['unit'] : '';	



	$variable_pricing = edd_has_variable_prices( $post_id );

	if($variable_pricing){

		$prices = edd_get_variable_prices( $post_id );

	} else {

		$prices = edd_get_download_price( $post_id );		

	}

	$enabled   				= isset($prices[$key]['renew']) ? true : false;

	

	echo '<td>';

		echo '<input type="number" name="edd_plans_multiple_license[' . $key . '][length]" class="small-text" value="' . $length_default . '"/>&nbsp;';

		echo '<select name="edd_plans_multiple_license[' . $key . '][unit]">';

			echo '<option value="days"' . selected( 'days', $unit_default, false ) . '>' . __( 'Days', 'edd_plans' ) . '</option>';

			echo '<option value="weeks"' . selected( 'weeks', $unit_default, false ) . '>' . __( 'Weeks', 'edd_plans' ) . '</option>';

			echo '<option value="months"' . selected( 'months', $unit_default, false ) . '>' . __( 'Months', 'edd_plans' ) . '</option>';

			echo '<option value="years"' . selected( 'years', $unit_default, false ) . '>' . __( 'Years', 'edd_plans' ) . '</option>';

		echo '</select>&nbsp;';

	echo '</td>';

}

	

function edd_plans_multiple_license_header( $download_id ) {

	edd_plans_load_multiple_license();

	if( 'bundle' == edd_get_download_type( $download_id ) ) {

		return;

	}



?>

	<th></th>

	<th><?php _e( 'License Period', 'edd_plans' ); ?></th>

	<th><?php //_e( 'Renew Price', 'edd_plans' ); ?></th>    

<?php

}



function edd_plans_update_multiple_license_back($post_id){

	if ( isset( $_POST['edd_plans_multiple_license'] ) ) {

		update_post_meta( $post_id, 'edd_plans_multiple_license', ( array ) $_POST['edd_plans_multiple_license'] );

	} else {

		delete_post_meta( $post_id, 'edd_plans_multiple_license' );

	}

}



function edd_plans_update_multiple_license_front($license_id, $expiration){

	$payment_id			= get_post_meta( $license_id, '_edd_sl_payment_id', true );

	$license_details 	= get_post_meta( $payment_id, '_edd_payment_meta', true );

	$license_product 	= get_post_meta( $license_id, '_edd_sl_download_id', true );	



	if(!empty($license_details)){

		foreach($license_details['downloads'] as $product){

			if($product['id'] != $license_product){

				continue;

			}

			

			unset($edd_plans_multiple_license);

			$edd_plans_multiple_license 	= get_post_meta( $product['id'], 'edd_plans_multiple_license', true );

			if(!empty($edd_plans_multiple_license) && isset($product['options']['price_id']) && isset($edd_plans_multiple_license[$product['options']['price_id']])){

				$expiration_details 	= $edd_plans_multiple_license[$product['options']['price_id']];

				

				if($expiration_details['length'] > 0 && $expiration_details['unit'] != ''){

					$license_length			= '+' . $expiration_details['length'] . ' ' . $expiration_details['unit'];

					$expiration     		= strtotime( $license_length, strtotime( get_post_field( 'post_date', $license_id, 'raw' ) ) );

					update_post_meta( $license_id, '_edd_sl_expiration', $expiration );

				}

			}

		}

	}

}



add_action( 'edd_sl_post_set_expiration', 'edd_plans_update_multiple_license_front', 100, 2 );

add_action( 'save_post', 'edd_plans_update_multiple_license_back', 11 );

add_action( 'wp_enqueue_scripts', 'edd_plans_load_multiple_license' );

add_action( 'edd_download_price_table_head', 'edd_plans_multiple_license_header', 1000 );

add_action( 'edd_render_price_row', 'edd_plans_multiple_license_row', 900, 3 );



function edd_plans_edd_sl_hide_downloads_on_expired( $show, $item, $receipt_args ) {

	/*

	* show all downloads

	* override of edd_sl_hide_downloads_on_expired from plugin/edd-software-licensing/includes/receipt.php

	*/

	return 1;

}

add_filter( 'edd_receipt_show_download_files', 'edd_plans_edd_sl_hide_downloads_on_expired', 11, 3 );



function edd_plans_filter_download_price($price, $download_id){

	global $edd_options;

	$post = get_post();



	if(isset($post->post_name) && $post->post_name == 'checkout'){

		return $price;

	}



	if( (isset($_GET['action']) && esc_html($_GET['action']) == 'edit') ){ //|| !isset($_GET['action'])

		return $price;

	}



	$payment_id = isset($_GET['payment_id']) ? absint($_GET['payment_id']) : (isset($edd_options['payment_id']) ? $edd_options['payment_id'] : 0);	

	if(is_array($price) && !empty($price)){

		foreach($price as $row => $value){



			if( is_object($post) && ($post->post_content == '[purchase_history]' || $post->post_content == '[downloads_history]') && $payment_id > 0){	

				if(isset($edd_options['expired'][$payment_id]) && $edd_options['expired'][$payment_id] == $download_id){

					if(!isset($value['renew'])){

						unset($price[$row]);

					}

				}

			} else {

				if(isset($value['renew'])){

					unset($price[$row]);

				}				

			}			

		}

	}

	

	return $price;

}

//add_action( 'edd_purchase_variable_prices', 'edd_plans_filter_download_price', 900, 2 );



add_filter( 'edd_get_download_price', 'edd_plans_filter_download_price', 11, 2 );

add_filter( 'edd_get_variable_prices', 'edd_plans_filter_download_price', 11, 2 );





function edd_plans_filter_cart_price($price, $download_id, $options, $include_taxes = false ){

	global $edd_options;



	$price = false;



	if ( edd_has_variable_prices( $download_id ) && ! empty( $options ) ) {

		$prices = get_post_meta( $download_id, 'edd_variable_prices', true );

		if ( $prices ) {

			$price = isset( $prices[ $options['price_id'] ] ) ? $prices[ $options['price_id'] ]['amount'] : false;

		}

	}



	if( ! $price ) {

		// Get the standard Download price if not using variable prices

		$price = edd_get_download_price( $download_id );

	}



	if( ! edd_download_is_tax_exclusive( $download_id ) ) {



		if( edd_prices_include_tax() && ! $include_taxes ) {

			// If price is entered with tax, we have to deduct the taxed amount from the price to determine the actual price

			$price -= edd_calculate_tax( $price );

		} elseif( ! edd_prices_include_tax() && $include_taxes ) {

			$price += edd_calculate_tax( $price );

		}



	}

	

	return $price;	

}

add_filter( 'edd_cart_item_price', 'edd_plans_filter_cart_price', 11, 4 );





function edd_plans_change_purchase_label($defaults){

	global $edd_options;

	$post 		= get_post();

	$payment_id = isset($_GET['payment_id']) ? absint($_GET['payment_id']) : (isset($defaults['payment_id']) ? $defaults['payment_id'] : 0);


	if( (strstr($post->post_content,'[purchase_history]') || strstr($post->post_content,'[downloads_history]')) && $payment_id > 0){

		$licenses 	= edd_software_licensing()->get_licenses_of_purchase( $payment_id );

		foreach($licenses as $license){

			$license_download_id = edd_software_licensing()->get_download_id( $license->ID );

			if( absint($defaults['download_id']) == $license_download_id && 'expired' == edd_software_licensing()->get_license_status( $license->ID ) ) {

				$defaults['text'] = __( 'Renew', 'edd_plans' );

			}

		}

	}



	return $defaults;

}

add_filter( 'edd_purchase_link_args', 'edd_plans_change_purchase_label', 11, 1 );





/**

 * Displays the renewal discount row on the cart

 *

 * @since 3.0.2

 * @return void

 */

function edd_plans_sl_cart_items_renewal_row() {



	$renewal_discount = edd_get_option( 'edd_sl_renewal_discount', false );



	if( empty( $renewal_discount ) ) {

		return;

	}



	if( $renewal_discount < 1 ) {

		$renewal_discount *= 100;

	}



	$discount_amount = ( edd_get_cart_subtotal() * $renewal_discount ) / 100;

	$discount_amount = edd_currency_filter( edd_format_amount( $discount_amount ) );



?>

	<tr class="edd_cart_footer_row edd_sl_renewal_row">

		<td colspan="3"><?php printf( __( 'License renewal discount: %s - %s', 'edd_plans' ), $renewal_discount . '%', $discount_amount ); ?></td>

	</tr>

<?php

}

//add_action( 'edd_cart_items_after', 'edd_plans_sl_cart_items_renewal_row' );



/**

 * @since 3.0.2

 * @param $discount float The discount amount

 * @param $item array the cart item array

 * @return float

 */

function edd_plans_sl_cart_details_item_discount( $discount, $item ) {



	$renewal_keys = EDD()->session->get( 'edd_renewal_keys' );



	$key = isset( $renewal_keys[ $item['id'] ] ) ? $renewal_keys[ $item['id'] ] : false;



	if( ! $key ) {

		return $discount;

	}



	$license_id = edd_software_licensing()->get_license_by_key( $key );



	if( edd_has_variable_prices( $item['id'] ) ) {



		$price_id = ( isset( $item['options']['price_id'] ) ) ? $item['options']['price_id'] : (int) get_post_meta( $license_id, '_edd_sl_download_price_id', true );

		$price    = edd_get_price_option_amount( $item['id'], $price_id );



	} else {



		$price = edd_get_download_price( $item['id'] );



	}



	$renewal_discount = edd_get_option( 'edd_sl_renewal_discount', false );



	if( $renewal_discount ) {



		if( $renewal_discount > 1 ) {

			$renewal_discount /= 100;

		}



		$renewal_discount = ( $price * $renewal_discount );

		$renewal_discount = number_format( $renewal_discount, 2, '.', '' );

		$discount += $renewal_discount;

	}



	return $discount;

}

//add_filter( 'edd_get_cart_content_details_item_discount_amount', 'edd_plans_sl_cart_details_item_discount', 10, 2 );


<?php

/*
* Plugin Name: Komerci WooCommerce Payment Gateway
* Description: This plugin adds a payment option in WooCommerce for customers to pay with their Credit Cards Via RedeCard.
* Version: 0.0.1
* Author: Luciano Junior
* Author URI: https://lucianojunior.com.br/
* License: GPLv2
*/

if ( ! defined( 'ABSPATH' ) ) exit;
//
function komerci_init()
{
	//
	function add_komerci_gateway_class( $methods ) 
	{
		$methods[] = 'WC_Komerci_Gateway'; 
		return $methods;
	}
	
	add_filter( 'woocommerce_payment_gateways', 'add_komerci_gateway_class' );
	
	if( class_exists( 'WC_Payment_Gateway' ) )
	{
		class WC_Komerci_Gateway extends WC_Payment_Gateway 
		{
	        //		
			public function __construct()
			{
				//
				$this->id               			= 'komercigateway';
				$this->icon             			= plugins_url( 'images/komerci.png' , __FILE__ )  ;
				$this->has_fields       			= true;
				$this->method_title    				= 'Komerci Settings';	
				
				//
				$this->init_form_fields();
				$this->init_settings();

				//
				$this->title                		= $this->get_option( 'title' );
				$this->description       		 	= $this->get_option( 'description' );
				$this->filiacao         		    = $this->get_option( 'filiacao' );
				$this->token                		= $this->get_option( 'token' );
				$this->method               		= $this->get_option( 'method' );

				//
				$this->user               			= $this->get_option( 'user' );
				$this->password               		= $this->get_option( 'password' );
				
				//
				$this->test               			= $this->get_option( 'test' );
				$this->supports                 	= array( 'default_credit_card_form' );
				$this->komerci_cardtypes       		= $this->get_option( 'komerci_cardtypes'); 
	
				//
				$this->siteurl 						= $_SERVER['SERVER_NAME'];
				$this->serverip 					= getHostByName(php_uname('n'));

				//
                $this->komerci_wsdlurl 				= 'https://ecommerce.redecard.com.br/pos_virtual/wskomerci/cap.asmx?WSDL';
                $this->komerci_liveurl         		= 'https://ecommerce.redecard.com.br/pos_virtual/wskomerci/cap.asmx';
               	$this->komerci_testurl				= 'https://ecommerce.redecard.com.br/pos_virtual/wskomerci/cap_teste.asmx';
                $this->komerci_methodurl			= 'https://ecommerce.redecard.com.br/pos_virtual/wskomerci/cap.asmx?op=GetAuthorized';

				if (is_admin()) 
				{
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) ); 
				}		
				
			} // __construct()

	        //
			public function admin_options()
			{
			?>		
	
				<h3><?php _e( 'Komerci Payment Gateway for WooCommerce', 'woocommerce' ); ?></h3>
				<p><?php  _e( 'Komerci is a payment gateway service provider allowing merchants to accept credit card.', 'woocommerce' ); ?></p>
	
				<table class="form-table">
				
				<div id="screen-meta" style="display:block;">
					<div id="screen-options-wrap">
						<h2><?php echo 'The IP of the website ' . $this->siteurl . ' is ' . $this->serverip . '.'; ?></h2>
						<p><?php _e('You should request the release of this IP to the RedeCard.', 'komerci'); ?></p>
					</div>
				</div>

				<?php $this->generate_settings_html(); ?>
				</table>
			
			<?php
			}
			
			//
			public function test()
			{
				if('yes' == $this->test)
				{
					$wsdl = $this->komerci_liveurl;
					return $wsdl;
				}
				else
				{
					$wsdl = $this->komerci_testurl;
					return $wsdl;
				}
			}

			//
			public function init_form_fields()
			{				
				  $this->form_fields = array(
					'enabled' => array(
					  'title' => __('Enable/Disable', 'komerci'),
					  'type' => 'checkbox',
					  'label' => __('Enable Komerci Payment Module.', 'komerci'),
					  'default' => 'no'),

					'title' => array(
					  'title' => __('Title:', 'komerci'),
					  'type'=> 'text',
					  'description' => __('This controls the title which the user sees during checkout.', 'komerci'),
					  'default' => __('Komerci', 'komerci')),

					'user' => array(
					  'title' => __('Komerci User:', 'komerci'),
					  'type'=> 'text',
					  'description' => __('The user RedeCard.', 'komerci')),
					 
					'password' => array(
					  'title' => __('Komerci Password:', 'komerci'),
					  'type'=> 'password',
					  'description' => __('The user password.', 'komerci')),

					'method' => array(
					  'title' => __('Method:', 'komerci'),
					  'type'=> 'text',
					  'description' => __('The server payment method.', 'komerci'),
					  'default' => __('GetAuthorized', 'komerci')),

					'description' => array(
					  'title' => __('Description:', 'komerci'),
					  'type' => 'textarea',
					  'description' => __('This controls the description which the user sees during checkout.', 'komerci'),
					  'default' => __('It is a practical and safe solution for accepting payments with credit cards MasterCard, Visa and Diners Club International on the Internet.', 'komerci')),

					'filiacao' => array(
					  'title' => __('Filiation:', 'komerci'),
					  'type' => 'text',
					  'description' => __('This id is available by RedeCard.')),

					'test' => array(
					  'title' => __('WSDL Test:', 'komerci'),
					  'type' => 'checkbox',
					  'label' => 'Enable/Disable',
					  'default' => 'no',
					  'description' =>  __('Only use the WSDL test server.', 'komerci'),
					),

					'debug' => array(
					  'title' => __('Debug:', 'komerci'),
					  'type' => 'text',
					  'description' =>  __('Active the account to buy/page.', 'komerci'),
					),
					
					'komerci_cardtypes' => array(
						'title'    => __( 'Accepted Cards', 'woocommerce' ),
						'type'     => 'multiselect',
						'class'    => 'chosen_select',
						'css'      => 'width: 350px;',
						'desc_tip' => __( 'Select the card types to accept.', 'woocommerce' ),
						
						'options'  => array(
							'mastercard'       	=> 'MasterCard',
							'visa'             	=> 'Visa',
							'dinersclub'       	=> 'Dinners Club'
						),
						
						'default' => array( 'mastercard', 'visa', 'dinersclub' ),
					)

				  );
				
			} // @function init_form_fields

			
			//
			public function includes()
			{
				include_once 'woocommerce-komerci-api.php';
			}
			
			//
			public function get_icon()
			{				
				$icon = '';
				
				if( is_array( $this->komerci_cardtypes ) )
				{
					foreach ($this->komerci_cardtypes as $card_type ) {
						if ( $url = $this->get_payment_method_image_url( $card_type ) ) {
							$icon .= '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( strtolower( $card_type ) ) . '" />';
						}
					}
				}
				else
				{
					$icon .= '<img src="' . esc_url( plugins_url( 'images/komerci.png' , __FILE__ ) ).'" alt="Komerci Gateway" />';	  
				}

				return apply_filters( 'woocommerce_merchantone_icon', $icon, $this->id );
			} // @function get_icon
			
			//
			public function get_payment_method_image_url( $type )
			{
				
				$image_type = strtolower( $type );
				return  WC_HTTPS::force_https_url( plugins_url( 'images/' . $image_type . '.png' , __FILE__ ) ); 
				
			} // @function get_payment_method_image_url
			
			//
			function get_card_type( $number )
			{
				
				$number = preg_replace('/[^\d]/','',$number);
				
				if (preg_match('/^3[47][0-9]{13}$/',$number))
				{
					return 'amex';
				}
				elseif (preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',$number))
				{
					return 'dinersclub';
				}
				elseif (preg_match('/^6(?:011|5[0-9][0-9])[0-9]{12}$/',$number))
				{
					return 'discover';
				}
				elseif (preg_match('/^(?:2131|1800|35\d{3})\d{11}$/',$number))
				{
					return 'jcb';
				}
				elseif (preg_match('/^5[1-5][0-9]{14}$/',$number))
				{
					return 'mastercard';
				}
				elseif (preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/',$number))
				{
					return 'visa';
				}
				else
				{
					return 'Invalid Card Number.';
				}
			} // @function get_card_type
			
		
			//
			function get_client_ip() 
			{
				$ipaddress = '';
				if (getenv('HTTP_CLIENT_IP'))
					$ipaddress = getenv('HTTP_CLIENT_IP');
				else if(getenv('HTTP_X_FORWARDED_FOR'))
					$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
				else if(getenv('HTTP_X_FORWARDED'))
					$ipaddress = getenv('HTTP_X_FORWARDED');
				else if(getenv('HTTP_FORWARDED_FOR'))
					$ipaddress = getenv('HTTP_FORWARDED_FOR');
				else if(getenv('HTTP_FORWARDED'))
					$ipaddress = getenv('HTTP_FORWARDED');
				else if(getenv('REMOTE_ADDR'))
					$ipaddress = getenv('REMOTE_ADDR');
				else
					$ipaddress = '0.0.0.0';
				return $ipaddress;
			} // @function get_client_ip
			
		
			//
			public function komerci_params( $wc_order )
			{
				// Param expire dates.
				$exp_date         = explode( "/", sanitize_text_field( $_POST['komercigateway-card-expiry'] ) );
				$exp_month        = str_replace( ' ', '', $exp_date[0]);
				$exp_year         = str_replace( ' ', '', $exp_date[1]);
				
				if(strlen($exp_year) == 2)
				{
					$exp_year += 2000;
				}
				
				// Get all args and insert into array.				
				$komerci_args = array(
				
					// Redecard indentification
					'filiation'  		=> $this->filiacao,
					'method'  			=> $this->method,
					'token'  			=> $this->token,
					
					// Sales information
					'ccnumber'  		=> sanitize_text_field( str_replace(" ", "", $_POST['komercigateway-card-number'] ) ),
					'ccexp'     		=> $exp_month . $exp_year,
					'amount'    		=> number_format( $wc_order-> order_total, 2, ".", ""),
					'cvv'       		=> sanitize_text_field( $_POST['komercigateway-card-cvc'] ),
					
					// Order information
					'ipaddress' 		=> $this->get_client_ip(),
					'orderid'   		=> $wc_order->get_order_number() ,
					'orderdescription' 	=> get_bloginfo('blogname').' Order #'.$wc_order->get_order_number() ,
					'tax'       		=> number_format($wc_order->get_total_tax(),2,".","") ,
					'shipping'  		=> number_format($wc_order->get_total_shipping(),2,".","") ,
					'ponumber'  		=> $wc_order->get_order_number() ,
					
					// Billing information
					'firstname'         => $wc_order->billing_first_name , 
					'lastname'          => $wc_order->billing_last_name ,
					'company'           => $wc_order->billing_company ,
					'address1'          => $wc_order->billing_address_1 ,
					'address2'          => $wc_order->billing_address_2 ,
					'city'              => $wc_order->billing_city ,
					'state'             => $wc_order->billing_state ,
					'zip'               => $wc_order->billing_postcode ,
					'country'           => $wc_order->billing_country ,
					'phone'             => $wc_order->billing_phone ,
					'fax'               => $wc_order->billing_phone ,
					'email'             => $wc_order->billing_email,
					'website'           => get_bloginfo('url'),
					
					// Shipping Information
					'shipping_firstname'=> $wc_order->shipping_first_name ,
					'shipping_lastname' => $wc_order->shipping_last_name,
					'shipping_company'  => $wc_order->shipping_company,
					'shipping_address1' => $wc_order->shipping_address_1,
					'shipping_address2' => $wc_order->shipping_address_2,
					'shipping_city'     => $wc_order->shipping_city,
					'shipping_state'    => $wc_order->shipping_state,
					'shipping_zip'      => $wc_order->shipping_postcode ,
					'shipping_country'  => $wc_order->shipping_country ,
					'shipping_email'    => $wc_order->shipping_email ,
					'type'              => 'sale'
					
				);
					
				return $komerci_args; 		
				
			} // @function komerci_params
			
			
			//
			public function process_payment( $order_id )
			{

				global $woocommerce;
				$wc_order = new WC_Order($order_id);
				
				$cardtype = $this->get_card_type( sanitize_text_field(str_replace(" ", "", $_POST['komercigateway-card-number']) ) );

				$cardnumber 	= $_POST['komercigateway-card-number'];
				$cvc2 			= $_POST['komercigateway-card-cvc'];

				if( !in_array( $cardtype, $this->komerci_cardtypes ) )
         		{
         			wc_add_notice('Komerci do not support accepting in '.$cardtype,  $notice_type = 'error' );
         			return array (
						'result'   => 'success',
						'redirect' => WC()->cart->get_checkout_url(),
					);

					die;
         		}

          		$params = $this->komerci_params( $wc_order );
          		$this->includes();

          		// TO DO CONNECT SOAP
          		/*
          		* É AQUI QUE ESSE CARALHOS NÃO VAI, FILHO DA PUTAAAAAAAAAAAAAAAAAAAAA DE REDECARDDDDDDDDDDDD
          		*/


          		$wsdl = 'https://ecommerce.userede.com.br/pos_virtual/wskomerci/cap_teste.asmx?WSDL';

          		// END OF CONNECT SOAP   		                    
        		     
			} // @function process_payment
						
		} // @class WC_Komerci_Gateway
	} // @if class_exists('WC_Payment_Gateway')
	
} // @function komerci_init();

add_action( 'plugins_loaded', 'komerci_init' );

function komerci_addon_activate()
{
	if( !function_exists( 'curl_exec' ) )
	{
		 wp_die( '<pre>This plugin requires PHP CURL library installled in order to be activated</pre>' );
	}
} // @function komerci_addon_activate

register_activation_hook( __FILE__, 'komerci_addon_activate' );

function komerci_settings_link( $links )
{
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=wc_komerci_gateway">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}

$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'komerci_settings_link' );


?>

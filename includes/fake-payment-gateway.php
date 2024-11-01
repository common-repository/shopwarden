<?php

class WC_Gateway_Fake_Pay extends WC_Payment_Gateway {
		
	function __construct() {
		
		
		$this->id = 'shopwarden';
		$this->title = 'Shopwarden Payments';
		$this->description = 'Pay with Shopwarden test gateway';
        $this->enabled = 'yes';
        $this->supports[] = 'products';
		$this->supports[] = 'subscriptions';
        $this->supports[] = 'cancellation';
        $this->supports[] = 'suspension';
		$this->supports[] = 'refunds';
        $this->supports[] = 'payment_method_change';
        $this->supports[] = 'payment_method_change_customer';
        $this->supports[] = 'payment_method_change_admin';
		
	}

    public function init_settings() {
		parent::init_settings();
		$this->enabled = 'yes';
	}

	function process_payment( $order_id ) {
		
		global $woocommerce;
		
		// Get an instance of the order object
		$order = new WC_Order( $order_id );
			    
	    if($order){
		    
		    // Iterating though each order items
		    foreach ( $order->get_items() as $item_id => $item_values ) {

				$product_id = $item_values['variation_id'];
		        if( $product_id == 0 || empty($product_id) ) $product_id = $item_values['product_id'];

				if($product_id){
					
			        $product = wc_get_product( $product_id );

					if($product->managing_stock()){
						
						// Increase stock with one to make this test order neutral to stock
				        wc_update_product_stock( $product, 1, 'increase' );
						
					}
					
				}

		    }	
		    
	    }		

        $order->update_status('completed');

		$order->payment_complete();

		// Remove cart
		$woocommerce->cart->empty_cart();
		
		// Schedule order deletion
		$shopwarden = new Shopwarden( __FILE__ );
		$shopwarden->scheduleDeleteOrders($order_id);

		// Return thankyou redirect
		return array(
			'result' => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}
}
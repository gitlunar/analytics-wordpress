<?php
function log_it($message) {
    if ( WP_DEBUG === true ) {
        if ( is_array($message) || is_object($message) ) {
            error_log( print_r($message, true) );
        } else {
            error_log( $message );
        }
    }
}

class Segment_Commerce_Woo extends Segment_Commerce {

	/**
	 * Init method registers two types of hooks: Standard hooks, and those fired in-between page loads.
	 *
	 * For all our events, we hook into either `segment_get_current_page` or `segment_get_current_page_track`
	 * depending on the API we want to use.
	 *
	 * For events that occur between page loads, we hook into the appropriate action and set a Segment_Cookie
	 * instance to check on the next page load.
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 */
	public function init() {

		$this->register_hook( 'segment_get_current_page'      , 'viewed_category'  , 1, $this );
		$this->register_hook( 'segment_get_current_page_track', 'viewed_product'   , 1, $this );
		$this->register_hook( 'segment_get_current_page_track', 'viewed_checkout'  , 1, $this );
		$this->register_hook( 'segment_get_current_page_track', 'viewed_cart'      , 1, $this );
		$this->register_hook( 'segment_get_current_page_track', 'completed_order'  , 1, $this );
		$this->register_hook( 'segment_get_current_page_track', 'added_to_cart'    , 2, $this );
		$this->register_hook( 'segment_get_current_page_track', 'removed_from_cart', 2, $this );

		/* HTTP actions */
		add_action( 'woocommerce_add_to_cart'		, array( $this, 'add_to_cart' )		, 10, 3 );
		add_action( 'woocommerce_remove_cart_item'	, array( $this, 'remove_from_cart' ), 10, 1 );
	}

	public function viewed_checkout() {
		$args  = func_get_args();
		$track = $args[0];

		if( wc_get_page_id( 'checkout' ) == get_the_ID() ) {
			$items =  WC()->cart->get_cart();
			
			$products = array();
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product = $this->get_segment_product($cart_item);
				if( ! $product ) {
					continue;
				}

				$products[] = $product;
			}

			// TODO : add checkout started properties
			if( ! empty($products) ) {
				array_push($track, array(
					'event'      => __( 'Checkout Started', 'segment' ),
					'properties' => array(
						'products' => $products
					)
				));
			}
		}

		return $track;
	}

	public function viewed_cart() {
		$args  = func_get_args();
		$track = $args[0];

		if( wc_get_page_id( 'cart' ) == get_the_ID() ) {
			$items =  WC()->cart->get_cart();
			
			$products = array();
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product = $this->get_segment_product($cart_item);
				if( ! $product ) {
					continue;
				}

				$products[] = $product;
			}

			if( ! empty($products) ) {
				array_push($track, array(
					'event'      => __( 'Cart Viewed', 'segment' ),
					'properties' => array(
						'products' => $products
					)
				));
			}
		}

		return $track;
	}

			
	/**
	 * Adds category name to analytics.page()
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @uses  func_get_args() Because our abstract class doesn't know how many parameters are passed to each hook
	 *                        for each different platform, we use func_get_args().
	 *
	 * @return array Filtered array of name and properties for analytics.page().
	 */
	public function viewed_category() {
		$args = func_get_args();
		$page = $args[0];

		if ( is_tax( 'product_cat' ) ) {
				$page = array(
					'category'       => single_term_title( '', false ),
					'properties' => array()
				);
		}

		return $page;
	}

	/**
	 * Adds product properties to analytics.track() when product is viewed.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @uses  func_get_args() Because our abstract class doesn't know how many parameters are passed to each hook
	 *                        for each different platform, we use func_get_args().
	 *
	 * @return array Filtered array of name and properties for analytics.track().
	 */
	public function viewed_product() {
		$args  = func_get_args();
		$track = $args[0];

		if ( is_singular( 'product' ) ) {
			$product = get_product( get_queried_object_id() );

			array_push($track, array(
				'event'      => __( 'Product Viewed', 'segment' ),
				'properties' => $this->get_segment_product( $product )
			));
		}

		return $track;
	}

	/**
	 * Adds product information to a Segment_Cookie when item is added to cart.
	 *
	 * @param string $key      Key name for item in cart.  A hash.
	 * @param int    $id       Product ID
	 * @param int    $quantity Item quantity
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @uses  func_get_args() Because our abstract class doesn't know how many parameters are passed to each hook
	 *                        for each different platform, we use func_get_args().
	 */
	public function add_to_cart( $key, $id, $quantity ) {
		if ( ! is_object( WC()->cart ) ) {
			return;
		}

		$items     = WC()->cart->get_cart();
		$cart_item = $items[ $key ];

		if ( ! is_array( $cart_item ) ) {
			return;
		}

		
		$added_cart_data = $this->get_segment_product($cart_item);
		if ( ! $added_cart_data ) {
			return;
		}

		$cookie = Segment_Cookie::get_cookie( 'added_to_cart' );
		$cookie = ($cookie) ? json_decode( stripslashes( $cookie ) ) : array();

		array_push($cookie, $added_cart_data);

		Segment_Cookie::set_cookie( 'added_to_cart', json_encode( $cookie ));
	}

	/**
	 * Adds product properties to analytics.track() when product added to cart.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @uses  func_get_args() Because our abstract class doesn't know how many parameters are passed to each hook
	 *                        for each different platform, we use func_get_args().
	 *
	 * @return array Filtered array of name and properties for analytics.track().
	 */
	public function added_to_cart() {
		$args = func_get_args();
		$track = $args[0];

		if ( false !== ( $cookie = Segment_Cookie::get_cookie( 'added_to_cart' ) ) ) {
			if ( ! is_object( WC()->cart ) ) {
				return $track;
			}

			$products = json_decode( stripslashes( $cookie ) );

			foreach($products as &$_product) {
				if ( is_object( $_product ) ) {
					array_push($track, array(
						'event'      => __( 'Added Product', 'segment' ),
						'properties' => array(
							'id'       => $_product->id,
							'sku'      => $_product->sku,
							'name'     => $_product->name,
							'price'    => $_product->price,
							'quantity' => $_product->quantity,
							'category' => $_product->category
						),
						'http_event' => 'added_to_cart'
					));
				}
			}
		}

		return $track;
	}

	/**
	 * Adds product information to a Segment_Cookie when item is removed from cart.
	 *
	 * @param string $key      Key name for item in cart.  A hash.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @uses  func_get_args() Because our abstract class doesn't know how many parameters are passed to each hook
	 *                        for each different platform, we use func_get_args().
	 */
	public function remove_from_cart( $key ) {
		if ( ! is_object( WC()->cart ) ) {
			return;
		}

		$items     = WC()->cart->get_cart();
		$cart_item = $items[ $key ];
		if ( ! is_array( $cart_item ) ) {
			return;
		}
		
		$removed_item_data = $this->get_segment_product($cart_item);
		if ( ! $removed_item_data ) {
			return;
		}

		$cookie = Segment_Cookie::get_cookie( 'removed_from_cart' );
		$cookie = ($cookie) ? json_decode( stripslashes( $cookie ) ) : array();

		array_push($cookie, $removed_item_data);
		Segment_Cookie::set_cookie( 'removed_from_cart', json_encode( $cookie ));
	}

	private function get_segment_product($item) {
		if( $item instanceof WC_Order_Item_Product) {
			$product_id = $item['product_id'];
			$specific_product = wc_get_product(
					! empty($item['variation_id'])
						? $item['variation_id']
						: $item['product_id']
				);
		}
		else if ( $item instanceof WC_Product ) {
			$product_id = $item->get_id();
			$specific_product = wc_get_product($product_id);
		}
		else {
			$product_id = $item['product_id'];
			$specific_product = $item['data'];
			$quantity = $item['quantity'];
		}

		$segment_product = array(
			'id'       => $product_id,
			'name'     => wc_get_product($product_id)->get_name(),
			'sku'      => $specific_product->get_sku(),
			'price'    => $specific_product->get_price(),
			'category' => implode( ', ', wp_list_pluck( wc_get_product_terms( $product_id, 'product_cat' ), 'name' ) )
		);

		if ( $quantity ) {
			$segment_product['quantity'] = $quantity;
		}

		return apply_filters('segment_product', $segment_product, $item);
	}

	/**
	 * Adds product properties to analytics.track() when product is removed from cart.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @uses  func_get_args() Because our abstract class doesn't know how many parameters are passed to each hook
	 *                        for each different platform, we use func_get_args().
	 *
	 * @return array Filtered array of name and properties for analytics.track().
	 */
	public function removed_from_cart() {
		$args = func_get_args();
		$track = $args[0];

		if ( false !== ( $cookie = Segment_Cookie::get_cookie( 'removed_from_cart' ) ) ) {

			if ( ! is_object( WC()->cart ) ) {
				return $track;
			}

			$products = json_decode( stripslashes( $cookie ) );
			
			foreach($products as &$_product) {
				if ( is_object( $_product ) ) {
					array_push($track, array(
						'event'      => __( 'Removed Product', 'segment' ),
						'properties' => array(
							'id'       => $_product->id,
							'sku'      => $_product->sku,
							'name'     => $_product->name,
							'price'    => $_product->price,
							'quantity' => $_product->quantity,
							'category' => $_product->category
						),
						'http_event' => 'removed_from_cart'
					));
				}
			}
		}

		return $track;
	}

	/**
	 * Adds product properties to analytics.track() when the order is completed successfully.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @uses  func_get_args() Because our abstract class doesn't know how many parameters are passed to each hook
	 *                        for each different platform, we use func_get_args().
	 *
	 * @return array Filtered array of name and properties for analytics.track().
	 */
	public function completed_order() {
		$args  = func_get_args();
		$track = $args[0];

		if ( is_order_received_page() ) {
			$order_number = get_query_var( 'order-received' );

			$order = new WC_Order( $order_number );

			log_it("Order Number : " . $order_number);
			/* Because gateways vary wildly in their usage of the status concept, we check for failure rather than success. */
			if ( 'failed' !== $order->status ) {

				$items        = $order->get_items();
				$products     = array();

				foreach ( $items as $item ) {
					log_it("Cart Item : ");
					log_it($item);
					$product = $this->get_segment_product($item);
					log_it("Segment Cart Item : ");
					log_it($product);
					if( ! $product) {
						continue;
					}
					$products[] = $product;
				}

				array_push($track, array(
					'event'      => __( 'Order Completed', 'segment' ),
					'properties' => array(
						'order_id' => $order->get_order_number(),
						'total'    => $order->get_total(),
						'revenue'  => $order->get_total() - ( $order->get_total_shipping() + $order->get_total_tax() ),
						'shipping' => $order->get_total_shipping(),
						'tax'      => $order->get_total_tax(),
						'discount' => $order->get_total_discount(),
						'coupon'   => implode(",", $order->get_used_coupons()),
						'products' => $products				
					)
				));
			}
		}

		return $track;
	}

}

/**
 * Bootstrapper for the Segment_Commerce_Woo class.
 *
 * @since  1.0.0
 */
function segment_commerce_woo() {
	$commerce = new Segment_Commerce_Woo();

	return $commerce->init();
}

add_action( 'plugins_loaded', 'segment_commerce_woo', 100 );

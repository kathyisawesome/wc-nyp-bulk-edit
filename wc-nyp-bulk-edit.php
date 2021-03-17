<?php
/**
 * Plugin Name: WooCommerce Name Your Price - Bulk Edit
 * Plugin URI:  http://github.com/kathyisawesome/wc-nyp-bulk-edit
 * Description: Add support for Bulk Editing Name Your Price properties
 * Version: 1.0.0-beta
 * Author:      Kathy Darling
 * Author URI:  http://www.kathyisawesome.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wc-nyp-bulk-edit
 * Domain Path: /languages
 * Requires at least: 5.0.0
 * Tested up to: 5.7.0
 * WC requires at least: 5.0.0
 * WC tested up to: 5.1.0   
 *
 * Copyright: © 2021 Kathy Darling.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! class_exists( 'WC_NYP_Bulk_Edit' ) ) :

	/**
	 * The Main WC_NYP_Bulk_Edit class
	 * 
	 * @version 1.0.0
	 */
	class WC_NYP_Bulk_Edit {

		const VERSION = '1.0.0-beta';
		const REQUIRED_WC = '5.0.0';
		const REQUIRED_NYP = '3.0.0';

		/**
		 * @var WC_NYP_Bulk_Edit - the single instance of the class
		 * @since 1.0.0
		 */
		protected static $instance = null;

		/**
		 * Main WC_NYP_Bulk_Edit Instance
		 *
		 * Ensures only one instance of WC_NYP_Bulk_Edit is loaded or can be loaded.
		 *
		 * @static
		 * @see WC_NYP_Bulk_Edit()
		 * @return WC_NYP_Bulk_Edit - Main instance
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WC_NYP_Bulk_Edit ) ) {
				self::$instance = new WC_NYP_Bulk_Edit();
			}
			return self::$instance;
		}


		public function __construct() {

			// Load translation files.
			add_action( 'init', array( $this, 'load_plugin_textdomain' ), 20 );

			// Sanity checks.
			if( ! $this->has_min_environment() ) {
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
				return false;
			}

			// Launch.
			self::attach_hooks_and_filters();

		}

		/**
		 * Test environement meets min requirements.
		 *
		 * @since  1.1.0
		 */
		public function has_min_environment() {

			$has_min_environment = true;
			$notices = array();

			// WC version sanity check.
			if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, self::REQUIRED_WC, '<' ) ) {
				$notice = sprintf( __( '<strong>Name Your Price - Bulk Edit is inactive.</strong> The %sWooCommerce plugin%s must be active and at least version %s for Name Your Price - Bulk Edit to function. Please upgrade or activate WooCommerce.', 'wc-nyp-bulk-edit' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', self::REQUIRED_WC );

				$notices[] = $notice;
				$has_min_environment = false;
			}

			// Name Your Price version sanity check.
			if ( ! function_exists( 'WC_Name_Your_Price' ) || version_compare( WC_Name_Your_Price()->version, self::REQUIRED_NYP, '<' ) ) {
				$notice = sprintf( __( '<strong>Name Your Price - Bulk Edit is inactive.</strong> The %sWooCommerce Name Your Price plugin%s must be active and at least version %s for Name Your Price - Bulk Edit to function. Please upgrade or activate WooCommerce Name Your Price.', 'wc-nyp-bulk-edit' ), '<a href="http://woocommerce.com/products/name-your-price/">', '</a>', self::REQUIRED_NYP );

				$notices[] = $notice;
				$has_min_environment = false;
			}

			if( ! empty( $notices ) ) {
				update_option( 'wc_nyp_bulk_edit_notices', $notices );
			}
			return $has_min_environment;

		}


		/**
		 * Displays a warning message if version check fails.
		 *
		 * @return string
		 */
		public function admin_notices() {

			$notices = get_option( 'wc_nyp_bulk_edit_notices', array() );

			if( ! empty( $notices ) ) {
				foreach( $notices as $notice ) {
					 echo '<div class="error"><p>' . $notice . '</p></div>';
				}
				delete_option( 'wc_nyp_bulk_edit_notices' );
			}
		   
		}

		/*-----------------------------------------------------------------------------------*/
		/* Localization */
		/*-----------------------------------------------------------------------------------*/


		/**
		 * Make the plugin translation ready
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
		 *
		 * Locales found in:
		 *      - WP_LANG_DIR/plugins/wc-nyp-bulk-edit-LOCALE.mo
		 *      - WP_CONTENT_DIR/plugins/wc-nyp-bulk-edit/languages/wc-nyp-bulk-edit-LOCALE.mo
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'wc-nyp-bulk-edit' , false , dirname( plugin_basename( __FILE__ ) ) .  '/languages/' );
		}

		/*-----------------------------------------------------------------------------------*/
		/* Setup */
		/*-----------------------------------------------------------------------------------*/

		/**
		 * Displays a warning message if version check fails.
		 *
		 * @return string
		 */
		public function attach_hooks_and_filters() {

			add_action( 'woocommerce_product_bulk_edit_end', array( __CLASS__, 'add_bulk_edit_options' ) );
			add_action( 'woocommerce_product_bulk_edit_save', array( __CLASS__, 'save_bulk_edit_options' ) );
		   
		}


		/**
		 * Adds inputs to the bulk edit.
		 *
		 * @param  string $meta_name the custom meta name/field.
		 * @param  string $label Label text.
		 * @param string $input_type price/nyp_enable_select/dropdown
		 * @return print HTML
		 */
		public static function add_input( $meta_name, $label, $input_type ) {
			$input_type = strtolower( trim( $input_type ) );
			?>  
			<div class="inline-edit-group nyp-bulk-edit-fields">
				<label class="alignleft">
					<span class="title"><?php echo esc_attr( $label ); ?></span>
					<span class="input-text-wrap">
						<select class="change<?php echo esc_attr( $meta_name ); ?> change_to" name="change<?php echo esc_attr( $meta_name ); ?>">
							<?php
							$options = array(
								'' => __( '— No change —', 'wc-nyp-bulk-edit' ),
							);
							// Input type.
							if ( 'price' === $input_type ) {
								$add_options = array(
									'1' => __( 'Change to:', 'wc-nyp-bulk-edit' ),
									'2' => __( 'Increase existing price by (fixed amount or %):', 'wc-nyp-bulk-edit' ),
									'3' => __( 'Decrease existing price by (fixed amount or %):', 'wc-nyp-bulk-edit' ),
								);
							} elseif ( 'nyp_enable_select' === $input_type ) {
								$add_options = array(
									'yes' => __( 'Enable Name Your Price for selected product(s)', 'wc-nyp-bulk-edit' ),
									'no'  => __( 'Disable Name Your Price for selected product(s)', 'wc-nyp-bulk-edit' ),
								);
							} else {
								$add_options = array(
									'yes' => __( 'Yes', 'wc-nyp-bulk-edit' ),
									'no'  => __( 'No', 'wc-nyp-bulk-edit' ),
								);
							}
							$options = array_replace( $options, $add_options );

							foreach ( $options as $key => $value ) {
								echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
							}
							?>
						</select>
					</span>
				</label>
				<?php if ( 'price' === $input_type ) : ?>
				<label class="change-input">
					<input type="text" name="<?php echo esc_attr( $meta_name ); ?>" class="text <?php echo esc_attr( $meta_name ); ?>" placeholder="<?php /* Translators: %s is Woocommerce store currency symbol */ printf( esc_attr__( 'Enter price (%s)', 'wc-nyp-bulk-edit' ), esc_attr( get_woocommerce_currency_symbol() ) ); ?>" value="" />
				</label>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Add options to Bulk Edit
		 *
		 * @return void
		 */
		public static function add_bulk_edit_options() {
			/**
			 * Options to be added.
			 *
			 * Format: 'meta_name' => [ 'label' => 'my label', 'input_type' => 'price/nyp_enable_select/dropdown' ]
			 * Did this because i don't like code repete :).
			 */
			$options = array(
				'_nyp'              => array(
					'label'      => __( 'Name Your Price Status', 'wc-nyp-bulk-edit' ),
					'input_type' => 'nyp_enable_select',
				),
				'_suggested_price'  => array(
					'label'      => __( 'Suggested Price', 'wc-nyp-bulk-edit' ),
					'input_type' => 'price',
				),
				'_min_price'        => array(
					'label'      => __( 'Minimum Price', 'wc-nyp-bulk-edit' ),
					'input_type' => 'price',
				),
				'_maximum_price'    => array(
					'label'      => __( 'Maximum Price', 'wc-nyp-bulk-edit' ),
					'input_type' => 'price',
				),
				'_hide_nyp_minimum' => array(
					'label'      => __( 'Hide Minimum Price', 'wc-nyp-bulk-edit' ),
					'input_type' => 'dropdown',
				),
			);

			if ( ! empty( $options ) ) {
				echo '<div class="wp-clearfix"></div><section class="wc-nyp-bulk-edit-section">';

				do_action( 'wc_nyp_product_bulk_edit_start' );

				echo '<hr/>';

				// phpcs:ignore
				echo '<h4>' . __( 'Name Your Price', 'wc_name_your_price', 'wc-nyp-bulk-edit' ) . '</h4>';
				echo '<h5>' . __( 'Price changes for products which have <strong>"Name Your Price"</strong> disabled, will be ignored.', 'wc-nyp-bulk-edit' ) . '</h5>'; 				

				foreach ( $options as $meta_key => $data ) {
					$label      = $data['label'];
					$input_type = $data['input_type'];

					// Keep pushing out inputs.
					if ( ! empty( $label ) && ! empty( $input_type ) ) {
						self::add_input( $meta_key, $label, $input_type );
					}
				}
				// Basic tags save the day sometimes :).
				echo '<div class="wp-clearfix"></div><br>';

				do_action( 'wc_nyp_product_bulk_edit_end' );

				echo '<hr/></section>';
			}
		}


		/**
		 * Should we save the NYP values?
		 *
		 * @param object $product
		 * @return bool
		 */
		public static function is_nyp_savable( $product ) {
			$request_data = self::request_data();
			$nyp_option   = isset( $request_data['change_nyp'] ) ? $request_data['change_nyp'] : '';

			// Either NYP product, or NYP is set.
			if ( WC_Name_Your_Price_Helpers::is_nyp( $product ) || ( ! empty( $nyp_option )
			&& in_array( $product->get_type(), WC_Name_Your_Price_Helpers::get_simple_supported_types(), true ) ) ) {
				// Was NYP set to yes or no?
				if ( 'no' === $nyp_option ) {
					return false;
				} else {
					return true;
				}
			}
			return false;
		}

		/**
		 * Save bulk edit options
		 *
		 * @param object $product
		 * @return void
		 */
		public static function save_bulk_edit_options( $product ) {

			// phpcs:disable WordPress.Security.NonceVerification
			$suggested = '';
			$minimum   = '';

			$request_data = self::request_data();

			if ( self::is_nyp_savable( $product ) ) {
				$product->update_meta_data( '_nyp', 'yes' );
				// Removing the sale price removes NYP items from Sale shortcodes.
				$product->set_sale_price( '' );
				$product->delete_meta_data( '_has_nyp' );
			} else {
				$product->update_meta_data( '_nyp', 'no' );

				// Save and ignore the rest, not needed.
				$product->save();
				return;
			}

			$suggested = self::sort_new_price( $product, 'suggested' );
			$minimum   = self::sort_new_price( $product, 'min' );

			// Set the regular price as the min price to enable WC to sort by price.
			$product->set_price( $minimum );
			$product->set_regular_price( $minimum );
			$product->set_sale_price( '' );

			if ( $product->is_type( 'subscription' ) ) {
				$product->update_meta_data( '_subscription_price', $minimum );
			}

			// Show error if minimum price is higher than the suggested price.
			if ( $suggested && $minimum && $minimum > $suggested ) {
				// Translators: %d variation ID.
				$error_notice = __( '🧐 The suggested price must be higher than the minimum for Name Your Price products. Please review your prices.', 'wc-nyp-bulk-edit' );
				WC_Admin_Meta_Boxes::add_error( $error_notice );
				return;
			}

			// Maximum price.
			$maximum = self::sort_new_price( $product, 'maximum' );

			// Show error if minimum price is higher than the maximum price.
			if ( $maximum && $minimum && $minimum > $maximum ) {
				// Translators: %d variation ID.
				$error_notice = __( '🧐 The maximum price must be higher than the minimum for Name Your Price products. Please review your prices.', 'wc-nyp-bulk-edit' );
				WC_Admin_Meta_Boxes::add_error( $error_notice );
				return;
			}

			// Hide or obscure minimum price.
			$hide = isset( $request_data['_hide_nyp_minimum'] ) ? $request_data['_hide_nyp_minimum'] : 'no';
			$product->update_meta_data( '_hide_nyp_minimum', $hide );

			// Suggested period - don't save if no suggested price.
			if ( $product->is_type( 'subscription' ) && isset( $request_data['_suggested_billing_period'] ) && array_key_exists( sanitize_key( $request_data['_suggested_billing_period'] ), WC_Name_Your_Price_Helpers::get_subscription_period_strings() ) ) {

				$suggested_period = sanitize_key( $request_data['_suggested_billing_period'] );

				$product->update_meta_data( '_suggested_billing_period', $suggested_period );
			} else {
				$product->delete_meta_data( '_suggested_billing_period' );
			}

			// Minimum period - don't save if no minimum price.
			if ( $product->is_type( 'subscription' ) && $minimum && isset( $request_data['_minimum_billing_period'] ) && array_key_exists( sanitize_key( $request_data['_minimum_billing_period'] ), WC_Name_Your_Price_Helpers::get_subscription_period_strings() ) ) {

				$minimum_period = sanitize_key( $request_data['_minimum_billing_period'] );

				$product->update_meta_data( '_minimum_billing_period', $minimum_period );
			} else {
				$product->delete_meta_data( '_minimum_billing_period' );
			}
			$product->save();

			do_action( 'wc_nyp_product_bulk_edit_save', $product );
		}

		/**
		 * Set the new price if requested.
		 *
		 * @param WC_Product $product The product to set the new price for.
		 * @param string     $price_type 'min'/'maximum','suggested'
		 * @return mixed the price, if a new price or price has been set or there's an old price, otherwise false.
		 */
		private static function sort_new_price( $product, $price_type ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$price_type   = trim( $price_type, '_' );
			$request_data = self::request_data();
			$old_price    = $product->get_meta( "_{$price_type}_price", true );

			if ( empty( $request_data[ "change_{$price_type}_price" ] ) || ! isset( $request_data[ "_{$price_type}_price" ] ) ) {
				// Get old price if possible, or return false.
				return ( ! empty( $old_price ) ? (float) $old_price : false );
			}

			$old_price     = (float) $old_price;
			$price_sorted  = false;
			$change_price  = absint( $request_data[ "change_{$price_type}_price" ] );
			$raw_price     = wc_clean( wp_unslash( $request_data[ "_{$price_type}_price" ] ) );
			$is_percentage = (bool) strstr( $raw_price, '%' );
			$price         = wc_format_decimal( $raw_price );

			switch ( $change_price ) {
				case 1:
					$new_price = $price;
					break;
				case 2:
					if ( $is_percentage ) {
						$percent   = $price / 100;
						$new_price = $old_price + ( $old_price * $percent );
					} else {
						$new_price = $old_price + $price;
					}
					break;
				case 3:
					if ( $is_percentage ) {
						$percent   = $price / 100;
						$new_price = max( 0, $old_price - ( $old_price * $percent ) );
					} else {
						$new_price = max( 0, $old_price - $price );
					}
					break;
				default:
					break;
			}

			if ( isset( $new_price ) ) {
				$price_sorted = true;

				if ( $new_price !== $old_price ) {
					$new_price = round( $new_price, wc_get_price_decimals() );
					$product->update_meta_data( "_{$price_type}_price", $new_price );
				}
			}

			return ( $price_sorted ? $new_price : $price_sorted );
		}

		/**
		 * Get the current request data ($_REQUEST superglobal).
		 * This method is added to ease unit testing.
		 * Inspired by WC_Admin_Post_Types :).
		 *
		 * @return array The $_REQUEST superglobal.
		 */
		protected static function request_data() {
			return $_REQUEST;
		}

	} // End class: do not remove or there will be no more guacamole for you.

	/**
	 * Returns the main instance of WC_NYP_Bulk_Edit to prevent the need to use globals.
	 *
	 * @return WC_NYP_Bulk_Edit
	 */
	function WC_NYP_Bulk_Edit() {
		return WC_NYP_Bulk_Edit::instance();
	}

	// Launch the whole plugin
	add_action( 'plugins_loaded', 'WC_NYP_Bulk_Edit', 20 );

endif; // End class_exists check.



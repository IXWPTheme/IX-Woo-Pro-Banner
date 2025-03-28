<?php
/**
 * AJAX Handler
 *
 * @package IX Woo Pro Banner
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class IX_WPB_Ajax_Handler {

    /**
     * The single instance of the class.
     *
     * @var IX_WPB_Ajax_Handler
     */
    private static $instance = null;

    /**
     * Main IX_WPB_Ajax_Handler instance.
     *
     * @return IX_WPB_Ajax_Handler
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Product search
        add_action( 'wp_ajax_ix_wpb_search_products', [ $this, 'handle_product_search' ] );
        add_action( 'wp_ajax_nopriv_ix_wpb_search_products', [ $this, 'handle_nopriv_access' ] );
        
        // Manager settings
        add_action( 'wp_ajax_ix_wpb_save_manager_settings', [ $this, 'handle_save_manager_settings' ] );
        add_action( 'wp_ajax_ix_wpb_search_manager_products', [ $this, 'handle_manager_product_search' ] );
    }

    /**
     * Handle product search for admin.
     */
    public function handle_product_search() {
        check_ajax_referer( 'ix_wpb_manager_form_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error(
                __( 'Permission denied', 'ix-woo-pro-banner' ),
                403
            );
        }

        $search = isset( $_REQUEST['q'] ) ? sanitize_text_field( $_REQUEST['q'] ) : '';
        $results = [];

        if ( ! empty( $search ) ) {
            $products = get_posts( [
                'post_type'      => 'product',
                'posts_per_page' => 20,
                's'             => $search,
                'post_status'   => 'publish',
            ] );

            foreach ( $products as $product ) {
                $product_obj = wc_get_product( $product->ID );
                $price       = $product_obj ? $product_obj->get_price() : '';
                
                $results[] = [
                    'id'      => $product->ID,
                    'text'    => $product->post_title,
                    'price'   => $price,
                    'display' => $this->format_product_display( $product, $price ),
                ];
            }
        }

        wp_send_json_success( $results );
    }

    /**
     * Handle product search for shop managers.
     */
    public function handle_manager_product_search() {
        check_ajax_referer( 'ix_wpb_manager_form_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error(
                __( 'Permission denied', 'ix-woo-pro-banner' ),
                403
            );
        }

        $search  = isset( $_REQUEST['q'] ) ? sanitize_text_field( $_REQUEST['q'] ) : '';
        $results = [];

        if ( ! empty( $search ) ) {
            $products = get_posts( [
                'post_type'      => 'product',
                'posts_per_page' => 20,
                's'             => $search,
                'post_status'   => 'publish',
                'author'        => get_current_user_id(), // Only manager's products
            ] );

            foreach ( $products as $product ) {
                $product_obj = wc_get_product( $product->ID );
                $price       = $product_obj ? $product_obj->get_price() : '';
                
                $results[] = [
                    'id'      => $product->ID,
                    'text'    => $product->post_title,
                    'price'   => $price,
                    'display' => $this->format_product_display( $product, $price ),
                ];
            }
        }

        wp_send_json_success( $results );
    }

    /**
     * Handle saving manager settings.
     */
    public function handle_save_manager_settings() {
        check_ajax_referer( 'ix_wpb_manager_form_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_products' ) ) {
            wp_send_json_error(
                __( 'Permission denied', 'ix-woo-pro-banner' ),
                403
            );
        }

        // Validate and sanitize input
        $image_source = isset( $_POST['image_source'] ) ? sanitize_text_field( $_POST['image_source'] ) : 'both';
        $image_size   = isset( $_POST['image_size'] ) ? sanitize_text_field( $_POST['image_size'] ) : 'woocommerce_thumbnail';
        $selected_ids = isset( $_POST['selected_products'] ) ? array_map( 'absint', (array) $_POST['selected_products'] ) : [];

        // Validate image source
        if ( ! in_array( $image_source, [ 'both', 'product', 'promo' ] ) ) {
            $image_source = 'both';
        }

        // Validate image size exists
        $valid_sizes = array_keys( wp_get_registered_image_subsizes() );
        if ( ! in_array( $image_size, $valid_sizes ) ) {
            $image_size = 'woocommerce_thumbnail';
        }

        // Validate product ownership
        $valid_products = [];
        if ( ! empty( $selected_ids ) ) {
            $user_products = get_posts( [
                'post_type'      => 'product',
                'post__in'       => $selected_ids,
                'posts_per_page' => -1,
                'author'         => get_current_user_id(),
                'fields'         => 'ids',
            ] );
            
            $valid_products = array_intersect( $selected_ids, $user_products );
        }

        // Prepare settings
        $settings = [
            'image_source'    => $image_source,
            'image_size'      => $image_size,
            'selected_products' => $valid_products,
        ];

        // Save settings
        update_option( 'ix_wpb_manager_grid_settings', $settings, false );

        wp_send_json_success( __( 'Settings saved successfully!', 'ix-woo-pro-banner' ) );
    }

    /**
     * Format product display for Select2.
     */
    private function format_product_display( $product, $price ) {
        $price_display = $price ? wc_price( $price ) : __( 'N/A', 'ix-woo-pro-banner' );
        return sprintf(
            '%s (ID: %d) - %s',
            $product->post_title,
            $product->ID,
            $price_display
        );
    }

    /**
     * Handle unauthorized access.
     */
    public function handle_nopriv_access() {
        wp_send_json_error(
            __( 'Authentication required', 'ix-woo-pro-banner' ),
            401
        );
    }
}

IX_WPB_Ajax_Handler::instance();
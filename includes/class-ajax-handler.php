<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class IX_WPB_Ajax_Handler {

    private static $instance;

    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('wp_ajax_ix_wpb_search_products', array($this, 'search_products'));
        add_action('wp_ajax_nopriv_ix_wpb_search_products', array($this, 'search_products'));
    }

    public function search_products() {
        check_ajax_referer('ix_wpb_search_products', 'nonce');

        if (!current_user_can('edit_products')) {
        wp_send_json_error(__('Unauthorized access', 'ix-woo-pro-banner'), 403);
    }
		
		$search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $results = [];

        if (!empty($search)) {
            $products = get_posts([
                'post_type' => 'product',
                'posts_per_page' => 20,
                's' => $search,
                'post_status' => 'publish'
            ]);

            foreach ($products as $product) {
                $product_obj = wc_get_product($product->ID);
                $price = $product_obj ? $product_obj->get_price() : '';
                
                $results[] = [
                    'id' => $product->ID,
                    'text' => $product->post_title,
                    'price' => $price,
                    'display' => sprintf(
                        '%s (ID: %d) - %s',
                        $product->post_title,
                        $product->ID,
                        $price ? wc_price($price) : __('N/A', 'ix-woo-pro-banner')
                    )
                ];
            }
        }

        wp_send_json($results);
    }
}
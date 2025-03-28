<?php
/**
 * Shop Manager Form
 * 
 * @package IX Woo Pro Banner
 * @version 1.0.0
 */

defined('ABSPATH') || exit;

class IX_WPB_Shop_Manager_Form {

    /**
     * Plugin version
     */
    const VERSION = IX_WPB_VERSION;

    /**
     * The single instance of the class
     */
    private static $instance = null;

    /**
     * Get class instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_shortcode('wpb-shop-manager-form', array($this, 'render_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
        if ($this->is_shortcode_present()) {
            // Select2 for product selection
            wp_enqueue_style(
                'ix-wpb-select2',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                array(),
                '4.1.0'
            );

            wp_enqueue_script(
                'ix-wpb-select2',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                array('jquery'),
                '4.1.0',
                true
            );

            // Form styles and scripts
            wp_enqueue_style(
                'ix-wpb-manager-form',
                IX_WPB_PLUGIN_URL . 'assets/css/wpb-manager-form.css',
                array(),
                self::VERSION
            );

            wp_enqueue_script(
                'ix-wpb-manager-form',
                IX_WPB_PLUGIN_URL . 'assets/js/wpb-manager-form.js',
                array('jquery', 'ix-wpb-select2'),
                self::VERSION,
                true
            );

            // Localize script data
            wp_localize_script('ix-wpb-manager-form', 'ix_wpb_manager_form', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ix_wpb_manager_form_nonce'),
                'i18n' => array(
                    'search_placeholder' => __('Search for products...', 'ix-woo-pro-banner'),
                    'no_results' => __('No products found', 'ix-woo-pro-banner'),
                    'loading' => __('Loading...', 'ix-woo-pro-banner'),
                    'saving' => __('Saving...', 'ix-woo-pro-banner'),
                    'save' => __('Save Settings', 'ix-woo-pro-banner'),
                    'error' => __('Error saving settings', 'ix-woo-pro-banner')
                )
            ));
        }
    }

    /**
     * Check if shortcode is present in current page
     */
    private function is_shortcode_present() {
        global $post;
        return (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'wpb-shop-manager-form'));
    }

    /**
     * Render the manager form
     */
    public function render_form() {
        if (!current_user_can('edit_products')) {
            return '<div class="ix-wpb-error">' . esc_html__('Permission denied', 'ix-woo-pro-banner') . '</div>';
        }

        $settings = get_option('ix_wpb_manager_grid_settings', array());
        $image_sizes = wp_get_registered_image_subsizes();

        ob_start();
        ?>
        <div class="ix-wpb-manager-form">
            <form id="ix-wpb-manager-form" method="post">
                <?php wp_nonce_field('ix_wpb_save_manager_settings', 'ix_wpb_manager_nonce'); ?>
                
                <div class="ix-wpb-form-section">
                    <h3><?php esc_html_e('Image Settings', 'ix-woo-pro-banner'); ?></h3>
                    
                    <div class="ix-wpb-form-row">
                        <label for="ix-wpb-image-source"><?php esc_html_e('Image Source', 'ix-woo-pro-banner'); ?></label>
                        <select name="image_source" id="ix-wpb-image-source" class="regular-text">
                            <option value="both" <?php selected($settings['image_source'] ?? 'both', 'both'); ?>>
                                <?php esc_html_e('Both (Product + Promo)', 'ix-woo-pro-banner'); ?>
                            </option>
                            <option value="product" <?php selected($settings['image_source'] ?? 'both', 'product'); ?>>
                                <?php esc_html_e('Product Image Only', 'ix-woo-pro-banner'); ?>
                            </option>
                            <option value="promo" <?php selected($settings['image_source'] ?? 'both', 'promo'); ?>>
                                <?php esc_html_e('Promo Image Only', 'ix-woo-pro-banner'); ?>
                            </option>
                        </select>
                    </div>
                    
                    <div class="ix-wpb-form-row">
                        <label for="ix-wpb-image-size"><?php esc_html_e('Image Size', 'ix-woo-pro-banner'); ?></label>
                        <select name="image_size" id="ix-wpb-image-size" class="regular-text">
                            <?php foreach ($image_sizes as $size => $dimensions) : ?>
                                <option value="<?php echo esc_attr($size); ?>" <?php selected($settings['image_size'] ?? 'woocommerce_thumbnail', $size); ?>>
                                    <?php echo esc_html("$size ({$dimensions['width']}×{$dimensions['height']})"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="ix-wpb-form-section">
                    <h3><?php esc_html_e('Featured Products', 'ix-woo-pro-banner'); ?></h3>
                    
                    <div class="ix-wpb-form-row">
                        <select id="ix-wpb-selected-products" 
                                name="selected_products[]" 
                                multiple="multiple" 
                                class="ix-wpb-product-select" 
                                style="width: 100%;"
                                data-placeholder="<?php esc_attr_e('Search for products...', 'ix-woo-pro-banner'); ?>">
                            <?php foreach ($this->get_selected_products($settings['selected_products'] ?? array()) as $product) : ?>
                                <option value="<?php echo esc_attr($product['id']); ?>" selected>
                                    <?php echo esc_html($product['text']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="ix-wpb-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save Settings', 'ix-woo-pro-banner'); ?>
                    </button>
                    <div class="ix-wpb-form-message"></div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get selected products data for the form
     */
    private function get_selected_products($selected_ids) {
        $products = array();
        
        if (!empty($selected_ids)) {
            $posts = get_posts(array(
                'post_type' => 'product',
                'post__in' => $selected_ids,
                'posts_per_page' => -1,
                'author' => get_current_user_id() // Only show current manager's products
            ));
            
            foreach ($posts as $post) {
                $products[] = array(
                    'id' => $post->ID,
                    'text' => $post->post_title
                );
            }
        }
        
        return $products;
    }
}

// Initialize the class
IX_WPB_Shop_Manager_Form::instance();
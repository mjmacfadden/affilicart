<?php
/**
 * Plugin Name: Affilicart Light
 * Plugin URI: https://affilicartpro.com
 * Description: A simple Amazon Affiliate product manager with settings and menu cart.
 * Version: 1.3
 * Author: Michael Macfadden
 * Author URI: https://mmacfadden.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: affilicart-light
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'AFFILICART_VERSION', '1.3' );
define( 'AFFILICART_PRO_ACTIVE', defined( 'AFFILICART_PRO_VERSION' ) );
define( 'AFFILICART_PLUGIN_FILE', __FILE__ );

// Activation Hook - Register post types and flush rewrite rules
register_activation_hook( __FILE__, function() {
    affilicart_register_post_type();
    affilicart_register_taxonomies();
    flush_rewrite_rules();
    
    // Set default accent color if not already set
    if ( ! get_option( 'affilicart_accent_color' ) ) {
        add_option( 'affilicart_accent_color', '#0073aa' );
    }
});

// Template Include Filter - Load custom templates from plugin folder for amazon_product post type and taxonomy
add_filter( 'template_include', 'affilicart_custom_template_include', 99 );

function affilicart_custom_template_include( $template ) {
    // Check if we're viewing a single amazon_product post
    if ( is_singular( 'amazon_product' ) ) {
        $plugin_template = plugin_dir_path( AFFILICART_PLUGIN_FILE ) . 'templates/single-amazon_product.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    
    // Check if we're viewing /product/category/all (all products alphabetical)
    if ( get_query_var( 'affilicart_show_all' ) ) {
        $plugin_template = plugin_dir_path( AFFILICART_PLUGIN_FILE ) . 'templates/archive-amazon_product.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    // Check if we're viewing the amazon_product archive
    if ( is_post_type_archive( 'amazon_product' ) ) {
        $plugin_template = plugin_dir_path( AFFILICART_PLUGIN_FILE ) . 'templates/archive-amazon_product.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    
    // Check if we're viewing the amazon_product category taxonomy archive
    if ( is_tax( 'amazon_product_category' ) ) {
        $plugin_template = plugin_dir_path( AFFILICART_PLUGIN_FILE ) . 'templates/taxonomy-amazon_product_category.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    
    // Return the original template if we're not on our custom post type
    return $template;
}

// Query Filter - Limit amazon_product_category taxonomy queries to only amazon_product posts
add_action( 'pre_get_posts', function( $query ) {
    if ( ! is_admin() && $query->is_main_query() ) {
        // Handle /product/category/all - show all products alphabetically
        if ( $query->get( 'affilicart_show_all' ) ) {
            $query->set( 'post_type', 'amazon_product' );
            $query->set( 'posts_per_page', -1 );
            $query->set( 'orderby', 'title' );
            $query->set( 'order', 'ASC' );
            return;
        }
        // Check if we're on the amazon_product_category taxonomy page
        if ( isset( $query->query_vars['taxonomy'] ) && $query->query_vars['taxonomy'] === 'amazon_product_category' ) {
            // Explicitly set post type to ONLY amazon_product - this prevents blog posts from showing
            $query->set( 'post_type', 'amazon_product' );
            $query->set( 'posts_per_page', 12 );
        }
        // Also limit the main post archive to only amazon_product type if on the main products page
        if ( is_post_type_archive( 'amazon_product' ) || ( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] === 'amazon_product' ) ) {
            $query->set( 'post_type', 'amazon_product' );
        }
    }
} );

// Fix any incorrect accent color values on admin load
add_action('admin_init', function() {
    $accent_color = get_option('affilicart_accent_color');
    // If accent color is set but invalid, fix it
    if ( !empty($accent_color) && !preg_match('/^#[a-fA-F0-9]{6}$/', $accent_color) ) {
        update_option('affilicart_accent_color', '#0073aa');
    }
}, 1); // Run early before other admin_init hooks

// 1. Register Custom Post Type
function affilicart_register_post_type() {
    // Product slug is now always 'product' (custom slugs moved to Pro)
    $args = array(
        'labels' => array(
            'name' => 'Products',
            'menu_name' => 'Affilicart',
            'add_new_item' => 'Add New Product',
            'edit_item' => 'Edit Product',
            'new_item' => 'New Product'
        ),
        'public' => true,
        'supports' => array( 'title', 'thumbnail' ),
        'taxonomies' => array(),
        'menu_icon' => 'dashicons-cart',
        'show_in_rest' => true,
        'rest_base' => 'amazon-products',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
        'rewrite' => array( 'slug' => 'product' ),
        'has_archive' => true,
        'template' => array(),
        'template_lock' => false,
    );
    register_post_type( 'amazon_product', $args );
}
add_action( 'init', 'affilicart_register_post_type' );

// 1a. Register Custom Taxonomies (Pro Feature - Moved to Pro Plugin)
// This function is now a stub and can be removed
function affilicart_register_taxonomies() {
    // Taxonomies are now registered by Affilicart Pro plugin
}
add_action( 'init', 'affilicart_register_taxonomies', 11 );

// Register custom query var for the "show all" alphabetical page
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'affilicart_show_all';
    return $vars;
} );

// Add custom rewrite rule for product category archives (Pro Feature - Moved to Pro Plugin)
add_action( 'init', function() {
    // Product category rewrite rules are now handled by Affilicart Pro plugin
}, 11 );

// Flush rewrite rules if needed
add_action( 'init', function() {
    // Check if rewrite rules need flushing (only do this once per activation)
    if ( get_option( 'affilicart_rewrite_rules_flushed' ) !== '2' ) {
        flush_rewrite_rules();
        update_option( 'affilicart_rewrite_rules_flushed', '2' );
    }
}, 12 );

// Deactivation Hook - Flush rewrite rules on deactivation
register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});

// 2. Filter to exclude product categories from regular site categories
add_filter( 'get_terms_args', function( $args, $taxonomies ) {
    // If querying regular categories, exclude product categories
    if ( isset( $taxonomies ) && is_array( $taxonomies ) && in_array( 'category', $taxonomies ) && ! in_array( 'amazon_product_category', $taxonomies ) ) {
        // This ensures product categories don't interfere with post categories
    }
    return $args;
}, 10, 2 );

// Add Product Description Meta Box
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'product_description',
        'Product Description',
        function( $post ) {
            $description = get_post_meta( $post->ID, 'product_description', true );
            wp_nonce_field( 'product_description_nonce', 'product_description_nonce' );
            echo '<textarea name="product_description" style="width: 100%; height: 120px; padding: 8px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; font-size: 14px; border: 1px solid #ddd; border-radius: 4px;">' . esc_textarea( $description ) . '</textarea>';
            echo '<p style="margin: 8px 0 0 0; color: #666; font-size: 13px;">Enter a brief product description or details.</p>';
        },
        'amazon_product',
        'normal',
        'high'
    );
});

// Save Product Description
add_action( 'save_post', function( $post_id ) {
    if ( get_post_type( $post_id ) !== 'amazon_product' ) return;
    if ( !isset( $_POST['product_description_nonce'] ) || !wp_verify_nonce( sanitize_key( $_POST['product_description_nonce'] ), 'product_description_nonce' ) ) return;
    if ( isset( $_POST['product_description'] ) ) {
        update_post_meta( $post_id, 'product_description', sanitize_textarea_field( wp_unslash( $_POST['product_description'] ) ) );
    }
});

// Customize the product editor
add_action( 'edit_form_top', function( $post ) {
    if ( $post->post_type === 'amazon_product' ) {
        echo '<div style="background: #f0f6fc; border-left: 4px solid #007cba; padding: 15px; margin: 20px 0; border-radius: 3px;">';
        echo '<strong>💡 ' . esc_html__( 'Need help?', 'affilicart' ) . '</strong> ' . esc_html__( 'Visit', 'affilicart' ) . ' <strong>' . esc_html__( 'Products → Settings → How To', 'affilicart' ) . '</strong> ' . esc_html__( 'for a complete guide.', 'affilicart' );
        echo '</div>';
        
        echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 3px;">';
        echo '<strong>⚠️ ' . esc_html__( 'Image Rights', 'affilicart' ) . '</strong> ' . esc_html__( 'You must own the rights to or have permission to use any images uploaded to this product. Do not use images without proper licensing or permission.', 'affilicart' );
        echo '</div>';
    }
});

// Customize title placeholder
add_filter( 'enter_title_here', function( $placeholder, $post ) {
    if ( $post->post_type === 'amazon_product' ) {
        return 'Enter product name...';
    }
    return $placeholder;
}, 10, 2 );

// Hide "Products" text in breadcrumb and customize Featured Image label
add_action( 'admin_head', function() {
    $screen = get_current_screen();
    if ( $screen && $screen->post_type === 'amazon_product' ) {
        echo '<style>
            .block-editor-block-breadcrumb__current { display: none !important; }
            .post-type-amazon_product .editor-post-title { margin-bottom: 20px; }
            .post-type-amazon_product .editor-post-title__input { font-size: 24px; }
        </style>';
        echo '<script>
            function changeLabel() {
                // Target all h2 elements containing "Featured image"
                document.querySelectorAll("h2").forEach(el => {
                    if (el.innerText === "Featured image") {
                        el.innerText = "Product Image";
                    }
                });
                // Also target divs with that text
                document.querySelectorAll("div").forEach(el => {
                    if (el.innerText === "Featured image" && el.className.includes("panel") || el.className.includes("title")) {
                        el.innerText = "Product Image";
                    }
                });
            }
            
            // Run multiple times as the editor loads
            changeLabel();
            setTimeout(changeLabel, 500);
            setTimeout(changeLabel, 1000);
            
            // Also use MutationObserver as fallback
            const observer = new MutationObserver(() => {
                document.querySelectorAll("*").forEach(el => {
                    if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
                        if (el.innerText === "Featured image") {
                            el.innerText = "Product Image";
                        }
                    }
                });
            });
            observer.observe(document.body, { childList: true, subtree: true, characterData: false });
        </script>';
    }
});

// 2. Admin Columns for Shortcode
add_filter('manage_amazon_product_posts_columns', function($columns) {
    $columns['affilicart_shortcode'] = 'Shortcode';
    return $columns;
});

// Adjust column widths
add_action('admin_head', function() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'amazon_product' && $screen->base === 'edit') {
        echo '<style>
            .wp-list-table .column-title { width: 15%; }
            .wp-list-table .column-affilicart_shortcode { width: 70%; }
        </style>';
    }
});

add_action('manage_amazon_product_posts_custom_column', function($column, $post_id) {
    if ($column === 'affilicart_shortcode') {
        $grid_shortcode = '[affilicart_grid id="' . $post_id . '"]';
        $button_shortcode = '[affilicart_button id="' . $post_id . '"]';
        $link_shortcode = '[affilicart_link id="' . $post_id . '"]';
        
        echo '<div style="display: flex; gap: 20px;">';
        
        // Grid Shortcode
        echo '<div>';
        echo '<div style="font-size: 12px; color: #666; margin-bottom: 4px;"><strong>Grid:</strong></div>';
        echo '<div style="display: flex; align-items: center; gap: 8px;">';
        echo '<code style="background:#eee; padding:5px; border-radius:3px;" id="affilicart-grid-' . esc_attr($post_id) . '">' . esc_html($grid_shortcode) . '</code>';
        echo '<button type="button" style="background: none; border: none; cursor: pointer; padding: 0; color: #666666; font-size: 16px;" onclick="copyToClipboard(\'' . esc_attr($grid_shortcode) . '\', this)" title="Copy grid shortcode">';
        echo '<span class="dashicons dashicons-admin-page"></span>';
        echo '</button>';
        echo '</div>';
        echo '</div>';
        
        // Button Shortcode
        echo '<div>';
        echo '<div style="font-size: 12px; color: #666; margin-bottom: 4px;"><strong>Button:</strong></div>';
        echo '<div style="display: flex; align-items: center; gap: 8px;">';
        echo '<code style="background:#eee; padding:5px; border-radius:3px;" id="affilicart-button-' . esc_attr($post_id) . '">' . esc_html($button_shortcode) . '</code>';
        echo '<button type="button" style="background: none; border: none; cursor: pointer; padding: 0; color: #666666; font-size: 16px;" onclick="copyToClipboard(\'' . esc_attr($button_shortcode) . '\', this)" title="Copy button shortcode">';
        echo '<span class="dashicons dashicons-admin-page"></span>';
        echo '</button>';
        echo '</div>';
        echo '</div>';
        
        // Link Shortcode
        echo '<div>';
        echo '<div style="font-size: 12px; color: #666; margin-bottom: 4px;"><strong>Link:</strong></div>';
        echo '<div style="display: flex; align-items: center; gap: 8px;">';
        echo '<code style="background:#eee; padding:5px; border-radius:3px;" id="affilicart-link-' . esc_attr($post_id) . '">' . esc_html($link_shortcode) . '</code>';
        echo '<button type="button" style="background: none; border: none; cursor: pointer; padding: 0; color: #666666; font-size: 16px;" onclick="copyToClipboard(\'' . esc_attr($link_shortcode) . '\', this)" title="Copy link shortcode">';
        echo '<span class="dashicons dashicons-admin-page"></span>';
        echo '</button>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        
        echo '<script>
            function copyToClipboard(text, button) {
                navigator.clipboard.writeText(text).then(function() {
                    const originalHTML = button.innerHTML;
                    button.innerHTML = "<span class=\"dashicons dashicons-yes\"></span>";
                    button.style.color = "#28a745";
                    setTimeout(function() {
                        button.innerHTML = originalHTML;
                        button.style.color = "#666666";
                    }, 2000);
                }).catch(function(err) {
                    console.error("Failed to copy:", err);
                });
            }
        </script>';
    }
}, 10, 2);

// 3. Settings Page (Associate ID)
add_action('admin_menu', function() {
    add_submenu_page('edit.php?post_type=amazon_product', 'Affilicart Settings', 'Settings', 'manage_options', 'affilicart-settings', 'affilicart_settings_html');
});

function affilicart_settings_html() {
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized', 'affilicart' ) );
    }
    // Get current tab from GET parameter (nonce verification handled by Settings API for form submissions via options.php)
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $current_tab = isset($_GET['tab']) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Affilicart Settings', 'affilicart' ); ?></h1>
        
        <div class="nav-tab-wrapper" style="border-bottom: 1px solid #ccc; margin-bottom: 20px;">
            <a href="?post_type=amazon_product&page=affilicart-settings&tab=settings" class="nav-tab <?php echo esc_attr( $current_tab === 'settings' ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Settings', 'affilicart' ); ?></a>
            <a href="?post_type=amazon_product&page=affilicart-settings&tab=how-to" class="nav-tab <?php echo esc_attr( $current_tab === 'how-to' ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'How To', 'affilicart' ); ?></a>
            <a href="?post_type=amazon_product&page=affilicart-settings&tab=disclaimer" class="nav-tab <?php echo esc_attr( $current_tab === 'disclaimer' ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Disclaimer', 'affilicart' ); ?></a>
            <?php if ( ! defined( 'AFFILICART_PRO_VERSION' ) ): ?>
            <a href="?post_type=amazon_product&page=affilicart-settings&tab=upgrade" class="nav-tab <?php echo esc_attr( $current_tab === 'upgrade' ? 'nav-tab-active' : '' ); ?>" style="background: linear-gradient(135deg, #2fbdb6 0%, #1a9a94 100%); color: white;">🚀 <?php esc_html_e( 'Upgrade to Pro', 'affilicart' ); ?></a>
            <?php endif; ?>
        </div>
        <?php if ($current_tab === 'settings'): ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('affilicart_settings_group');
                do_settings_sections('affilicart-settings');
                submit_button();
                ?>
            </form>
        <?php elseif ($current_tab === 'how-to'): ?>
            <div style="max-width: 900px;">
                <h2>How To Use Affilicart</h2>
                
                <h3>⚙️ Step 1: Configure Settings</h3>
                <p>Go to the <strong>Settings</strong> tab to configure:</p>
                <ul style="line-height: 1.8;">
                    <li><strong>Product URL Slug:</strong> Customize the URL path for your product pages. Default is "product" (URLs look like /product/product-name). You can change this to any word you prefer, like "item", "equipment", or "gear". After changing, visit Settings → Permalinks and save to apply the change.</li>
                    <li><strong>Amazon Associate ID:</strong> Your unique tracking ID from Amazon Associates (required for earning commissions)</li>
                    <li><strong>Accent Color:</strong> <strong>Pro feature:</strong> Customize button colors to match your site's branding</li>
                    <li><strong>Cart Position & Display:</strong> <strong>Pro feature:</strong> Configure cart visibility and placement on your site</li>
                    <li><strong>Image Lightbox:</strong> <strong>Pro feature:</strong> When enabled, clicking product images on single product pages opens a fullscreen viewer with high-resolution images</li>
                </ul>
                
                <h3>📝 Step 2: Add Products</h3>
                <ol style="line-height: 1.8;">
                    <li><strong>Navigate to Products:</strong> Click <code>Products</code> in the admin menu and select <code>Add New Product</code></li>
                    <li><strong>Product Name:</strong> Enter the product name in the title field</li>
                    <li><strong>Product Image:</strong> Click <code>Product Image</code> on the right to upload or select the product image</li>
                    <li><strong>Description:</strong> Add product details in the description box below the title</li>
                    <li><strong>ASIN:</strong> Enter the Amazon Standard Identification Number (found on the Amazon product page URL or product details). <strong>This is required for the affiliate link to work.</strong></li>
                    <li><strong>Price:</strong> Enter the product price manually. <strong>Important:</strong> Prices do not update automatically — you'll need to update them manually when Amazon prices change.</li>
                    <li><strong>Publish:</strong> Click <code>Publish</code> to save your product</li>
                </ol>
                
                <p style="margin-top: 20px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;"><strong>💡 Tip:</strong> You'll need the product ID (shown in the Products list) when creating shortcodes to display your products.</p>
                
                <h3>🛒 Step 3: Display Products Using Shortcodes</h3>
                <p>Add products to any page or post using these shortcodes:</p>
                
                <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                    <thead>
                        <tr style="background: #f0f0f0;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;"><strong>Use Case</strong></th>
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;"><strong>Shortcode</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Display all products</td>
                            <td style="padding: 12px; border: 1px solid #ddd; font-family: monospace; background: #f9f9f9;"><code>[affilicart_grid]</code></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Display a single product</td>
                            <td style="padding: 12px; border: 1px solid #ddd; font-family: monospace; background: #f9f9f9;"><code>[affilicart_grid id="123"]</code></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Display multiple products</td>
                            <td style="padding: 12px; border: 1px solid #ddd; font-family: monospace; background: #f9f9f9;"><code>[affilicart_grid id="123,456,789"]</code></td>
                        </tr>
                    </tbody>
                </table>
                
                <h4>Customize Product Display</h4>
                <p>Hide specific fields by adding parameters to your shortcode:</p>
                
                <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                    <thead>
                        <tr style="background: #f0f0f0;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;"><strong>To Hide</strong></th>
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;"><strong>Add This Parameter</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Product Image</td>
                            <td style="padding: 12px; border: 1px solid #ddd; font-family: monospace; background: #f9f9f9;"><code>show-image="no"</code></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Product Title</td>
                            <td style="padding: 12px; border: 1px solid #ddd; font-family: monospace; background: #f9f9f9;"><code>show-title="no"</code></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Product Description</td>
                            <td style="padding: 12px; border: 1px solid #ddd; font-family: monospace; background: #f9f9f9;"><code>show-description="no"</code></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Product Price</td>
                            <td style="padding: 12px; border: 1px solid #ddd; font-family: monospace; background: #f9f9f9;"><code>show-price="no"</code></td>
                        </tr>
                    </tbody>
                </table>
                
                <p><strong>Example:</strong> <code>[affilicart_grid id="123" show-description="no"]</code> displays product 123 without the description.</p>
                
                <h4>Display a Single Button</h4>
                <p>Add a standalone "Add to Cart" button for a specific product anywhere on your site:</p>
                
                <p style="font-family: monospace; background: #f9f9f9; padding: 12px; border-radius: 3px; border-left: 4px solid var(--ac-accent-color, #007cba);"><code>[affilicart_button id="123"]</code></p>
                
                <p><strong>Use Case:</strong> Perfect for embedding product buttons within blog posts, sidebars, or anywhere you want a minimal call-to-action without the full product card.</p>
                
                <h4>Display a Link with Hover Card</h4>
                <p>Add a clickable text link that shows a product preview (image, name, price) on hover:</p>
                
                <p style="font-family: monospace; background: #f9f9f9; padding: 12px; border-radius: 3px; border-left: 4px solid var(--ac-accent-color, #007cba);"><code>[affilicart_link id="123"]</code></p>
                
                <p>This displays the product name as a link. When you hover over it, a small card appears showing:</p>
                <ul style="margin: 10px 0 15px 20px;">
                    <li>Product image</li>
                    <li>Product name</li>
                    <li>Product price</li>
                    <li>Add to Cart button</li>
                </ul>
                
                <p><strong>Custom Link Text:</strong> You can change the link text by adding a <code>text</code> parameter:</p>
                <p style="font-family: monospace; background: #f9f9f9; padding: 12px; border-radius: 3px; border-left: 4px solid var(--ac-accent-color, #007cba);"><code>[affilicart_link id="123" text="View Details"]</code></p>
                
                <p><strong>Use Case:</strong> Embed product links naturally within blog post text, reviews, or recommendations. Readers can preview the product without leaving your page.</p>
                
                <h3>🎯 Pro Tips</h3>
                <ul style="line-height: 1.8;">
                    <li><strong>Find Product IDs:</strong> Go to Products list — the ID is shown under the product name</li>
                    <li><strong>Custom Styling:</strong> Use the Accent Color setting to match your site theme without code changes</li>
                    <li><strong>High-Res Images:</strong> The lightbox automatically loads full-resolution product images for better viewing</li>
                    <li><strong>Shopping Cart:</strong> All cart data is saved in the browser and persists across page visits until cleared</li>
                    <li><strong>Price Updates:</strong> Remember to manually update prices if they change on Amazon</li>
                </ul>
            </div>
        <?php elseif ($current_tab === 'disclaimer'): ?>
            <div style="max-width: 900px;">
                <h2>Legal Disclaimer & Terms</h2>
                
                <h3>1. No Affiliation With Amazon</h3>
                <p>Affilicart is an independent WordPress plugin and is not affiliated with, endorsed by, sponsored by, or approved by Amazon.com, Inc. or any of its affiliates, including the Amazon Associates Program.</p>
                <p>"Amazon" and related marks are trademarks of Amazon.com, Inc. All such trademarks remain the property of their respective owners.</p>
                
                <h3>2. Use of Amazon URLs and Parameters</h3>
                <p>Affilicart may generate or modify Amazon product URLs, including the addition of query string parameters intended to:</p>
                <ul>
                    <li>Preload shopping carts</li>
                    <li>Redirect users to specific product pages</li>
                    <li>Append affiliate tracking IDs</li>
                    <li>Trigger "Buy Now" functionality</li>
                </ul>
                <p>Amazon may modify, restrict, deprecate, or discontinue support for any URL structure, parameter, cart-loading mechanism, or affiliate tracking method at any time without notice.</p>
                <p>The Developer makes no guarantee that:</p>
                <ul>
                    <li>URL parameters will continue to function</li>
                    <li>Cart preloading will remain supported</li>
                    <li>Affiliate tracking will persist</li>
                    <li>Redirect behaviors will remain unchanged</li>
                    <li>Amazon will not block, flag, or alter such links</li>
                </ul>
                <p><strong>Functionality may stop working at any time due to changes made by Amazon or third parties.</strong></p>
                
                <h3>3. Amazon Associates Compliance Responsibility</h3>
                <p>If you participate in the Amazon Associates Program, you are solely responsible for ensuring your compliance with:</p>
                <ul>
                    <li>The Amazon Associates Program Operating Agreement</li>
                    <li>Amazon Trademark Guidelines</li>
                    <li>Amazon Brand Usage Policies</li>
                    <li>Any applicable local laws and advertising disclosure requirements</li>
                </ul>
                <p>Affilicart does not monitor, validate, or enforce compliance with Amazon's Terms of Service. Use of this plugin does not guarantee compliance with any Amazon policy. <strong>You assume full responsibility for ensuring your use of generated links complies with Amazon's current rules.</strong></p>
                
                <h3>4. Risk of Account Suspension or Termination</h3>
                <p>Amazon reserves the right to suspend or terminate affiliate accounts at its sole discretion.</p>
                <p>Use of this plugin may:</p>
                <ul>
                    <li>Trigger automated detection systems</li>
                    <li>Be interpreted by Amazon as non-compliant behavior</li>
                    <li>Result in loss of affiliate privileges</li>
                    <li>Result in forfeited commissions</li>
                </ul>
                <p>The Developer is not responsible for:</p>
                <ul>
                    <li>Affiliate account suspension</li>
                    <li>Loss of commissions</li>
                    <li>Loss of Amazon access</li>
                    <li>Any related business losses</li>
                </ul>
                <p><strong>You acknowledge and accept these risks by using this plugin.</strong></p>
                
                <h3>5. No Warranty</h3>
                <p>This plugin is provided "AS IS" and "AS AVAILABLE," without warranty of any kind, express or implied, including but not limited to:</p>
                <ul>
                    <li>Merchantability</li>
                    <li>Fitness for a particular purpose</li>
                    <li>Non-infringement</li>
                    <li>Compatibility with future WordPress releases</li>
                    <li>Compatibility with future Amazon systems</li>
                </ul>
                <p>The Developer does not warrant that:</p>
                <ul>
                    <li>The plugin will function uninterrupted</li>
                    <li>The plugin will be error-free</li>
                    <li>The plugin will meet your expectations</li>
                    <li>The plugin will generate revenue</li>
                </ul>
                
                <h3>6. Limitation of Liability</h3>
                <p>To the fullest extent permitted by law, the Developer shall not be liable for any:</p>
                <ul>
                    <li>Direct damages</li>
                    <li>Indirect damages</li>
                    <li>Incidental damages</li>
                    <li>Consequential damages</li>
                    <li>Loss of profits</li>
                    <li>Loss of data</li>
                    <li>Business interruption</li>
                    <li>Loss of affiliate revenue</li>
                    <li>Reputational harm</li>
                </ul>
                <p>arising out of or related to:</p>
                <ul>
                    <li>Use or inability to use the plugin</li>
                    <li>Amazon system changes</li>
                    <li>Broken links</li>
                    <li>Compliance issues</li>
                    <li>Third-party actions</li>
                </ul>
                <p><strong>Your use of this plugin is entirely at your own risk.</strong></p>
                
                <h3>7. Indemnification / Hold Harmless</h3>
                <p>You agree to indemnify, defend, and hold harmless the Developer from and against any and all claims, liabilities, damages, losses, and expenses, including reasonable legal fees, arising out of or in any way connected with:</p>
                <ul>
                    <li>Your use of the plugin</li>
                    <li>Your violation of Amazon's Terms of Service</li>
                    <li>Your violation of any applicable laws or regulations</li>
                    <li>Any claims made by Amazon or third parties</li>
                </ul>
                
                <h3>8. Third-Party Service Dependency</h3>
                <p>This plugin depends on external systems and services that are outside the control of the Developer, including but not limited to:</p>
                <ul>
                    <li>Amazon website infrastructure</li>
                    <li>Amazon cart and checkout systems</li>
                    <li>Amazon affiliate tracking systems</li>
                    <li>WordPress core software</li>
                    <li>WordPress theme compatibility</li>
                    <li>Web hosting environments</li>
                </ul>
                <p>The Developer is not responsible for failures caused by third-party systems.</p>
                
                <h3>9. Revenue Disclaimer</h3>
                <p>Affilicart does not guarantee:</p>
                <ul>
                    <li>Affiliate earnings</li>
                    <li>Increased conversion rates</li>
                    <li>Increased click-through rates</li>
                    <li>Increased average order value</li>
                    <li>Improved customer behavior</li>
                </ul>
                <p>Any revenue examples are hypothetical and not guarantees of future results.</p>
                
                <h3>10. Modifications</h3>
                <p>The Developer reserves the right to:</p>
                <ul>
                    <li>Modify plugin functionality</li>
                    <li>Remove features</li>
                    <li>Discontinue development</li>
                    <li>Transition to paid or freemium models</li>
                </ul>
                <p>Amazon may also modify its systems in ways that render plugin functionality partially or fully inoperable.</p>
                
                <h3>11. Acceptance of Terms</h3>
                <p>By installing or using Affilicart, you acknowledge that:</p>
                <ul>
                    <li>You have read this disclaimer</li>
                    <li>You understand the risks</li>
                    <li>You accept full responsibility for your use</li>
                    <li>You agree to all terms stated herein</li>
                </ul>
                <p><strong>If you do not agree, you must discontinue use immediately.</strong></p>
            </div>
        <?php elseif ($current_tab === 'upgrade' && ! defined( 'AFFILICART_PRO_VERSION' )): ?>
            <div style="max-width: 900px;">
                <h2>🚀 Upgrade to Affilicart Pro</h2>
                
                <div style="background: linear-gradient(135deg, #2fbdb6 0%, #1a9a94 100%); color: white; padding: 30px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: white; margin-top: 0;">Unlock Premium Features</h3>
                    <p style="font-size: 16px;">Upgrade to Affilicart Pro to unlock premium customization and organization features.</p>
                </div>
                
                <h3>Free vs Pro Comparison</h3>
                <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                    <thead>
                        <tr style="background: #f0f0f0;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #ddd;"><strong>Feature</strong></th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong>Free</strong></th>
                            <th style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong>Pro</strong></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Number of Products</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong>Unlimited</strong></td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">Unlimited</strong></td>
                        </tr>
                        <tr style="background: #fafafa;">
                            <td style="padding: 12px; border: 1px solid #ddd;">Shopping Cart</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">✓</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">✓</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Grid & Link Shortcodes</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">✓</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">✓</td>
                        </tr>
                        <tr style="background: #fafafa;">
                            <td style="padding: 12px; border: 1px solid #ddd;">Image Lightbox</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Product Categories</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                        <tr style="background: #fafafa;">
                            <td style="padding: 12px; border: 1px solid #ddd;">Single Product Pages</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Custom Accent Color</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                        <tr style="background: #fafafa;">
                            <td style="padding: 12px; border: 1px solid #ddd;">Cart Display Options</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Automatic Price Sync (with API Keys) <span style="background: #ff9800; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold;">BETA</span></td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                        <tr style="background: #fafafa;">
                            <td style="padding: 12px; border: 1px solid #ddd;">Priority Support</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <p style="color: #666; font-size: 13px;">Pro features help you organize and customize your affiliate store for better user experience.</p>
                
                <h3 style="margin-top: 40px;">Get Affilicart Pro</h3>
                <p>Ready to unlock advanced features like categories, custom colors, flexible cart positioning, and price sync? Upgrade to Affilicart Pro:</p>
                
                <a href="https://affilicartpro.com" target="_blank" rel="noopener noreferrer" class="button button-primary" style="padding: 15px 40px; font-size: 16px; background: #2fbdb6; border-color: #2fbdb6; color: white; text-decoration: none; display: inline-block; margin-top: 15px;">
                    Learn More About Pro
                </a>
                
                <p style="margin-top: 30px; padding: 15px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 3px; color: #155724;">
                    <strong>💡 Tip:</strong> Pro is installed as a separate plugin. After purchasing, download it from your account and install it alongside the free version. The Pro plugin will automatically unlock all premium features.
                </p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

add_action('admin_init', function() {
    register_setting('affilicart_settings_group', 'affilicart_associate_id', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'yourtag-20'
    ));
    
    add_settings_section('affilicart_main_section', 'Main Settings', null, 'affilicart-settings');
    
    add_settings_field('affilicart_associate_id', 'Amazon Associate ID', function() {
        $id = get_option('affilicart_associate_id', 'yourtag-20');
        echo '<input type="text" name="affilicart_associate_id" value="' . esc_attr($id) . '" class="regular-text">';
        echo '<p class="description">Enter your Amazon Associates tracking ID here.</p>';
    }, 'affilicart-settings', 'affilicart_main_section');
});

// Settings page opening and tabs
// All pro feature tabs have been removed

// 4. Meta Boxes (Product Details)
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'affilicart_details', 'Product Details', 'affilicart_meta_box_cb', 'amazon_product', 'normal', 'high' );
});
function affilicart_meta_box_cb( $post ) {
    $asin = get_post_meta( $post->ID, '_affilicart_asin', true );
    wp_nonce_field( 'affilicart_meta_nonce', 'affilicart_meta_nonce' );
    ?>
    <p><label><?php esc_html_e( 'ASIN:', 'affilicart' ); ?></label><input type="text" name="affilicart_asin" value="<?php echo esc_attr($asin); ?>" class="widefat"></p>
    <?php
}

add_action( 'save_post', function($post_id) {
    if ( !isset( $_POST['affilicart_meta_nonce'] ) || !wp_verify_nonce( sanitize_key( $_POST['affilicart_meta_nonce'] ), 'affilicart_meta_nonce' ) ) return;
    if ( isset( $_POST['affilicart_asin'] ) ) update_post_meta( $post_id, '_affilicart_asin', sanitize_text_field( wp_unslash( $_POST['affilicart_asin'] ) ) );
});

// 2. Helper function to get product price (if available from Pro API)
function affilicart_get_product_price( $product_id ) {
    // Check if Pro is active and API is enabled
    if ( ! defined( 'AFFILICART_PRO_VERSION' ) || ! get_option( 'affilicart_api_enabled' ) ) {
        return false; // No API pricing available
    }
    
    $price = get_post_meta( $product_id, '_affilicart_price', true );
    $price_date = get_post_meta( $product_id, '_affilicart_price_date', true );
    $price_source = get_post_meta( $product_id, '_affilicart_price_source', true );
    
    // Only return price if it came from API (not manually entered)
    if ( $price && $price_source === 'api' && $price_date ) {
        return array(
            'price' => $price,
            'date' => $price_date,
            'source' => 'api'
        );
    }
    
    return false;
}

// 3. Enqueue & Global Logic
add_action('wp_enqueue_scripts', function() {
    // Apply filter to allow disabling CDN (default: enabled for best compatibility)
    // Usage: add_filter( 'affilicart_use_cdn', '__return_false' ); to disable
    $use_cdn = apply_filters( 'affilicart_use_cdn', true );
    
    // Bootstrap CSS - provides grid system and components
    // Served via jsDelivr (https://www.jsdelivr.com/) - no tracking
    // NOTE: Removed from wp_enqueue to comply with WordPress.org requirements.
    // Bootstrap functionality replaced with local CSS.
    // if ( $use_cdn ) {
    //     wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');
    // }
    
    // Bootstrap Icons - lightweight icon font
    // Served via jsDelivr (https://www.jsdelivr.com/) - no tracking
    // NOTE: Removed from wp_enqueue to comply with WordPress.org requirements.
    // Bootstrap Icons replaced with dashicons (WordPress built-in).
    // if ( $use_cdn ) {
    //     wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css', array(), '1.11.0');
    // }
    
    // Enqueue local styles
    wp_enqueue_style('affilicart-style', plugins_url('style.css', __FILE__), array('dashicons'), AFFILICART_VERSION);
    
    // Bootstrap JS - required for cart modal functionality
    // NOTE: Removed from wp_enqueue to comply with WordPress.org requirements.
    // Bootstrap modal functionality replaced with vanilla JavaScript modal.
    // if ( $use_cdn ) {
    //     wp_enqueue_script('bootstrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), '5.3.0', true);
    // }
    
    wp_enqueue_script('affilicart-js', plugins_url('scripts.js', __FILE__), array('jquery'), AFFILICART_VERSION, true);

    $products = array();
    $query = new WP_Query(array('post_type' => 'amazon_product', 'posts_per_page' => -1, 'post_status' => 'publish'));
    foreach ($query->posts as $p) {
        $categories = wp_get_post_terms($p->ID, 'category', array('fields' => 'names'));
        $products[] = array(
            'id' => $p->ID, 'name' => get_the_title($p->ID),
            'slug' => $p->post_name,
            'description' => wp_trim_words(get_post_meta($p->ID, 'product_description', true), 15),
            'image' => get_the_post_thumbnail_url($p->ID, 'medium'),
            'image_full' => get_the_post_thumbnail_url($p->ID, 'full'),
            'asin' => get_post_meta($p->ID, '_affilicart_asin', true),
            'categories' => is_array($categories) ? $categories : array(),
        );
    }
    
    // Determine cart position - free version always uses bottom-right
    $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
    $cart_position = 'bottom-right'; // Fixed for free version
    
    wp_localize_script('affilicart-js', 'affilicart_data', array(
        'products' => $products,
        'associate_tag' => get_option('affilicart_associate_id', 'default-20'),
        'accent_color' => '#0073aa',
        'lightbox_enabled' => false,
        'is_divi' => $is_divi,
        'cart_position' => $cart_position,
        'product_slug' => 'product',
        'api_enabled' => false,
        'is_pro' => false
    ));
});

// 6. Menu Icon & Modal - simplified for free version
add_filter('wp_nav_menu_items', function($items) {
    // Only add menu cart if Pro is NOT active
    if ( ! defined( 'AFFILICART_PRO_VERSION' ) ) {
        // Free version uses fixed bottom-right cart position
        $items .= '<li class="menu-item ac-menu-cart"><a href="#" onclick="showCartModal(); return false;"><span class="dashicons dashicons-cart"></span> <span id="cart-count">0</span></a></li>';
    }
    return $items;
}, 10, 2);

// Divi menu support removed - this is a pro feature
// All Divi-specific cart positioning code has been moved to Affilicart Pro

add_action('wp_head', function() {
    // Basic styles for all versions
    echo '<style>
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
    </style>';
});

// Footer modal and cart display
add_action('wp_footer', function() { ?>
    <div class="ac-modal" id="cartModal" style="z-index: 999999;">
        <div class="ac-modal-dialog"><div class="ac-modal-content">
            <div class="ac-modal-header"><h5 class="ac-modal-title"><span class="dashicons dashicons-cart"></span> Cart</h5><button type="button" class="ac-modal-close" onclick="closeCartModal()">×</button></div>
            <div class="ac-modal-body">
                <div id="cart-empty-alert" class="ac-alert ac-alert-danger" role="alert" style="display: none;">
                    <strong>Cart is Empty</strong> — Add products to your cart to proceed.
                </div>
                <ul id="cart-items" class="ac-list ac-list-group"></ul>
            </div>
            <div class="ac-modal-footer">
                <div style="text-align: right;">
                    <button id="checkout-button" type="button" class="btn btn-success">Checkout on Amazon</button>
                    <p style="font-size: 12px; color: #666; margin: 8px 0 0 0;">You will be redirected to Amazon.com to complete your purchase.</p>
                    <p style="font-size: 11px; color: #999; margin: 12px 0 0 0;">As an Amazon Associate I earn from qualifying purchases.</p>
                </div>
            </div>
        </div></div>
    </div>
<?php });

// 7. Shortcode - Grid
add_shortcode('affilicart_grid', function($atts) {
    $a = shortcode_atts(array(
        'id' => null,
        'show_image' => 'yes',
        'show_title' => 'yes',
        'show_description' => 'yes',
        'show_price' => 'yes',
        'show_amazon_link' => 'no'
    ), $atts);
    
    // Determine which products to display
    $args = array('post_type' => 'amazon_product', 'posts_per_page' => -1, 'post_status' => 'publish');
    if ($a['id']) {
        $ids = array_map('intval', array_map('trim', explode(',', $a['id'])));
        $args['post__in'] = $ids;
        $args['orderby'] = 'post__in'; // Preserve the order
    }
    
    $query = new WP_Query($args);
    $html = '<div class="ac-shop-wrapper"><div class="row">';
    
    if ($query->have_posts()) {
        $associate_tag = get_option('affilicart_associate_id', 'default-20');
        
        while ($query->have_posts()) {
            $query->the_post();
            $product_id = get_the_ID();
            $product_url = get_the_permalink();
            $thumbnail_url = get_the_post_thumbnail_url($product_id, 'medium');
            $product_title = get_the_title($product_id);
            $description = wp_trim_words(get_post_meta($product_id, 'product_description', true), 15);
            $asin = get_post_meta($product_id, '_affilicart_asin', true);
            
            $html .= '<div class="col-md-4 mb-4">';
            $html .= '<div class="ac-product-card">';
            
            if ($a['show_image'] === 'yes' && $thumbnail_url) {
                // Make images clickable to link to single product page
                $html .= '<a href="' . esc_url($product_url) . '" style="display: block; text-decoration: none; color: inherit;"><img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($product_title) . '" class="ac-product-image" style="cursor: pointer;"></a>';
            }
            
            if ($a['show_title'] === 'yes') {
                $html .= '<a href="' . esc_url($product_url) . '" style="text-decoration: none; color: inherit; display: block;"><h5 class="ac-card-title">' . esc_html($product_title) . '</h5></a>';
            }
            
            if ($a['show_description'] === 'yes' && $description) {
                $html .= '<a href="' . esc_url($product_url) . '" style="text-decoration: none; color: inherit; display: block;"><p class="ac-card-text">' . esc_html($description) . '</p></a>';
            }
            
            $html .= '<button class="btn btn-primary w-100 ac-grid-btn" onclick="addToCart(' . intval($product_id) . ', false)" style="background-color: var(--ac-accent-color, #007cba); border-color: var(--ac-accent-color, #007cba);">Add to Cart</button>';
            
            if ($asin) {
                $amazon_url = 'https://www.amazon.com/dp/' . esc_attr($asin) . '?tag=' . esc_attr($associate_tag);
                $html .= '<a href="' . esc_url($amazon_url) . '" target="_blank" rel="noopener noreferrer" style="display: block; text-align: center; margin-top: 12px; color: #aaa; text-decoration: none; font-size: 14px;">View Price on Amazon <span class="dashicons dashicons-external" style="display: inline-block; width: auto; height: auto; font-size: 14px; margin-left: 4px; line-height: 1; vertical-align: middle;"></span></a>';
            }
            
            $html .= '<div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #eee; text-align: center;"><p style="font-size: 10px; color: #999; margin: 0; line-height: 1.4; white-space: nowrap;">As an Amazon Associate I earn from qualifying purchases.</p></div>';
            
            $html .= '</div></div>';
        }
        wp_reset_postdata();
    } else {
        $html .= '<div class="col-12 text-center text-muted">No products found.</div>';
    }
    
    $html .= '</div></div>';
    return $html;
});

// 8. Shortcode - Button
add_shortcode('affilicart_button', function($atts) {
    $a = shortcode_atts(array('id' => null), $atts);
    if (!$a['id']) {
        return '<div class="text-danger">Error: affilicart_button requires an id parameter</div>';
    }
    return '<div id="ac-button-container" data-product-id="'.esc_attr($a['id']).'"></div>';
});

// 9. Shortcode - Link with Hover Card
add_shortcode('affilicart_link', function($atts) {
    $a = shortcode_atts(array('id' => null, 'text' => null), $atts);
    if (!$a['id']) {
        return '<span class="text-danger">Error: affilicart_link requires an id parameter</span>';
    }
    $link_text = $a['text'] ? esc_html($a['text']) : '';
    return '<span class="ac-hover-link" data-product-id="'.esc_attr($a['id']).'" data-link-text="'.esc_attr($link_text).'"></span>';
});

// Add alias for plural form
add_shortcode('affilicart_links', function($atts) {
    $a = shortcode_atts(array('id' => null, 'text' => null), $atts);
    if (!$a['id']) {
        return '<span class="text-danger">Error: affilicart_links requires an id parameter</span>';
    }
    $link_text = $a['text'] ? esc_html($a['text']) : '';
    return '<span class="ac-hover-link" data-product-id="'.esc_attr($a['id']).'" data-link-text="'.esc_attr($link_text).'"></span>';
});

// Auto-Tagger: Moved to Affilicart Pro Plugin
// This functionality is now only available in the pro version


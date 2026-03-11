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
 * Text Domain: affilicart
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'AFFILICART_VERSION', '1.3' );
define( 'AFFILICART_PRO_ACTIVE', defined( 'AFFILICART_PRO_VERSION' ) );

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
    // For Pro users, allow custom slug. For free users, always use 'product'
    $custom_slug = AFFILICART_PRO_ACTIVE ? get_option('affilicart_post_slug', 'product') : 'product';
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
        'rewrite' => array( 'slug' => $custom_slug ),
    );
    register_post_type( 'amazon_product', $args );
}
add_action( 'init', 'affilicart_register_post_type' );

// 1a. Register Custom Taxonomies (Pro Feature)
function affilicart_register_taxonomies() {
    // Only register categories if Pro is active
    if ( ! AFFILICART_PRO_ACTIVE ) {
        return;
    }
    
    // Product Category Taxonomy
    register_taxonomy( 'amazon_product_category', 'amazon_product', array(
        'labels' => array(
            'name' => 'Product Categories',
            'singular_name' => 'Product Category',
        ),
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite' => array( 'slug' => 'product-category' ),
    ) );
}
add_action( 'init', 'affilicart_register_taxonomies', 11 );

// Add custom rewrite rule for product category archives (/product/category/category-name/)
add_action( 'init', function() {
    // For Pro users, allow custom slug. For free users, always use 'product'
    $custom_slug = AFFILICART_PRO_ACTIVE ? get_option( 'affilicart_post_slug', 'product' ) : 'product';
    
    // Add rewrite rule for /product/category/category-name/
    add_rewrite_rule(
        $custom_slug . '/category/([^/]+)/?$',
        'index.php?affilicart_category=$matches[1]',
        'top'
    );
    
    // Register query variable
    add_filter( 'query_vars', function( $vars ) {
        $vars[] = 'affilicart_category';
        return $vars;
    });
    
    // Handle template loading for product categories
    add_action( 'template_redirect', function() {
        $category = get_query_var( 'affilicart_category' );
        
        if ( ! empty( $category ) ) {
            $plugin_template = plugin_dir_path( __FILE__ ) . 'archive-amazon_product.php';
            if ( file_exists( $plugin_template ) ) {
                // Load template and exit
                include $plugin_template;
                exit;
            }
        }
    }, 5 );
}, 11 );

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
            <?php if ( AFFILICART_PRO_ACTIVE ) : ?>
                <a href="?post_type=amazon_product&page=affilicart-settings&tab=api" class="nav-tab <?php echo esc_attr( $current_tab === 'api' ? 'nav-tab-active' : '' ); ?>" style="color: #2fbdb6; font-weight: 600;">⚡ <?php esc_html_e( 'Price Sync', 'affilicart' ); ?></a>
            <?php endif; ?>
            <a href="?post_type=amazon_product&page=affilicart-settings&tab=disclaimer" class="nav-tab <?php echo esc_attr( $current_tab === 'disclaimer' ? 'nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Disclaimer', 'affilicart' ); ?></a>
            <?php if ( ! AFFILICART_PRO_ACTIVE ) : ?>
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
        <?php elseif ($current_tab === 'upgrade' && ! AFFILICART_PRO_ACTIVE): ?>
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
                            <td style="padding: 12px; border: 1px solid #ddd;">Product Images</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">✓</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">✓</td>
                        </tr>
                        <tr style="background: #fafafa;">
                            <td style="padding: 12px; border: 1px solid #ddd;">Amazon Affiliate Links</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">✓</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">✓</td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Image Lightbox</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                        <tr style="background: #fafafa;">
                            <td style="padding: 12px; border: 1px solid #ddd;">Product Categories</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                        <tr>
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
                            <td style="padding: 12px; border: 1px solid #ddd;">Automatic Price Sync (with API Keys)</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;">Priority Support</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;">—</td>
                            <td style="padding: 12px; text-align: center; border: 1px solid #ddd;"><strong style="color: #2fbdb6;">✓</strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <p style="color: #666; font-size: 13px;">Pro features help you organize and customize your affiliate store for better user experience.</p>
                
                <h3 style="margin-top: 40px;">Get Affilicart Pro</h3>
                <p>Ready to scale your affiliate store? Click the button below to get Affilicart Pro and unlock unlimited products:</p>
                
                <button disabled class="button button-primary" style="padding: 15px 40px; font-size: 16px; background: #cccccc; border: none; color: #666666; margin-top: 15px; display: inline-block; cursor: not-allowed; opacity: 0.6;">
                    Coming Soon
                </button>
                
                <p style="margin-top: 30px; padding: 15px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 3px; color: #155724;">
                    <strong>💡 Tip:</strong> Pro is installed as a separate plugin. After purchasing, download it from your account and install it alongside the free version. The Pro plugin will automatically unlock all premium features.
                </p>
            </div>
        <?php elseif ($current_tab === 'api' && AFFILICART_PRO_ACTIVE): ?>
            <div style="max-width: 900px;">
                <h2>⚡ Amazon Price Sync</h2>
                
                <div style="background: #f0f7ff; border-left: 4px solid #2fbdb6; padding: 15px; border-radius: 3px; margin: 20px 0;">
                    <p><strong>Live Price Updates:</strong> Connect your Amazon Product Advertising API credentials to automatically sync product prices from Amazon every 24 hours. This ensures your affiliate products always show current pricing.</p>
                </div>
                
                <form method="post" action="options.php">
                    <?php settings_fields('affilicart_pro_api_group'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="affilicart_api_enabled">
                                    <input type="checkbox" id="affilicart_api_enabled" name="affilicart_api_enabled" value="1" <?php checked(get_option('affilicart_api_enabled'), 1); ?> />
                                    <strong>Enable Price Sync</strong>
                                </label>
                            </th>
                            <td>
                                <p class="description">Check to enable automatic price updates from Amazon API.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="affilicart_api_key">Amazon API Key</label>
                            </th>
                            <td>
                                <input type="text" id="affilicart_api_key" name="affilicart_api_key" value="<?php echo esc_attr(get_option('affilicart_api_key')); ?>" class="regular-text" />
                                <p class="description">Your Amazon Product Advertising API Access Key ID.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="affilicart_api_secret">Amazon API Secret</label>
                            </th>
                            <td>
                                <input type="password" id="affilicart_api_secret" name="affilicart_api_secret" value="<?php echo esc_attr(get_option('affilicart_api_secret')); ?>" class="regular-text" placeholder="••••••••••••••••" />
                                <p class="description">Your Amazon Product Advertising API Secret Access Key. (Hidden for security)</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="affilicart_api_region">API Region</label>
                            </th>
                            <td>
                                <select id="affilicart_api_region" name="affilicart_api_region" class="regular-text">
                                    <option value="us" <?php selected(get_option('affilicart_api_region'), 'us'); ?>>United States (US)</option>
                                    <option value="ca" <?php selected(get_option('affilicart_api_region'), 'ca'); ?>>Canada (CA)</option>
                                    <option value="uk" <?php selected(get_option('affilicart_api_region'), 'uk'); ?>>United Kingdom (UK)</option>
                                    <option value="de" <?php selected(get_option('affilicart_api_region'), 'de'); ?>>Germany (DE)</option>
                                    <option value="fr" <?php selected(get_option('affilicart_api_region'), 'fr'); ?>>France (FR)</option>
                                    <option value="jp" <?php selected(get_option('affilicart_api_region'), 'jp'); ?>>Japan (JP)</option>
                                </select>
                                <p class="description">The AWS region where your API credentials are configured. This should match your associate store region.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Sync Status</th>
                            <td>
                                <?php 
                                $last_sync = get_option('affilicart_api_last_sync');
                                if ($last_sync) {
                                    $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_sync);
                                    echo '<p style="color: #27ae60;"><strong>✓ Last sync:</strong> ' . esc_html($date) . '</p>';
                                } else {
                                    echo '<p style="color: #999;">No sync attempts yet. Sync will run automatically every 24 hours after saving.</p>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                    
                    <div style="margin: 20px 0; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">
                        <p><strong>⚠️ Setup Required:</strong> Before enabling price sync, you must have:</p>
                        <ul style="margin-left: 20px;">
                            <li>An active <a href="https://affiliate-program.amazon.com/" target="_blank" rel="noopener noreferrer">Amazon Associates account</a></li>
                            <li>Registered your Amazon Product Advertising API credentials</li>
                            <li>Configured your API Key, Secret, and Associate ID with Amazon</li>
                        </ul>
                    </div>
                    
                    <?php submit_button('Save API Settings'); ?>
                </form>
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
    register_setting('affilicart_settings_group', 'affilicart_accent_color', array(
        'type' => 'string',
        'sanitize_callback' => function($value) {
            if (empty($value)) return '#0073aa';
            // Validate it's a hex color
            if (preg_match('/^#[a-fA-F0-9]{6}$/', $value)) {
                return $value;
            }
            return '#0073aa';
        },
        'default' => '#0073aa'
    ));
    register_setting('affilicart_settings_group', 'affilicart_cart_position', array(
        'type' => 'string',
        'sanitize_callback' => function($value) {
            $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
            $valid = array('top-left', 'top-right', 'bottom-left', 'bottom-right');
            if ($is_divi) {
                $valid[] = 'divi-menu';
            }
            if (in_array($value, $valid)) {
                return $value;
            }
            return $is_divi ? 'divi-menu' : 'bottom-right';
        },
        'default' => 'bottom-right'
    ));
    register_setting('affilicart_settings_group', 'affilicart_lightbox', array(
        'type' => 'boolean',
        'sanitize_callback' => function($value) {
            return (bool) $value;
        },
        'default' => true
    ));
    register_setting('affilicart_settings_group', 'affilicart_post_slug', array(
        'type' => 'string',
        'sanitize_callback' => function($value) {
            if (empty($value)) return 'product';
            // Allow only lowercase letters, numbers, and hyphens
            $sanitized = strtolower(preg_replace('/[^a-z0-9-]/', '', $value));
            return $sanitized ?: 'product';
        },
        'default' => 'product'
    ));
    
    // Register API settings (Pro feature)
    register_setting('affilicart_pro_api_group', 'affilicart_api_enabled', array(
        'type' => 'boolean',
        'sanitize_callback' => function($value) {
            return (bool) $value;
        },
        'default' => false
    ));
    register_setting('affilicart_pro_api_group', 'affilicart_api_key', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));
    register_setting('affilicart_pro_api_group', 'affilicart_api_secret', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));
    register_setting('affilicart_pro_api_group', 'affilicart_api_region', array(
        'type' => 'string',
        'sanitize_callback' => function($value) {
            $valid_regions = array('us', 'ca', 'mx', 'uk', 'de', 'fr', 'it', 'es', 'in', 'jp', 'au');
            return in_array($value, $valid_regions) ? $value : 'us';
        },
        'default' => 'us'
    ));
    
    
    add_settings_section('affilicart_main_section', 'Main Settings', null, 'affilicart-settings');
    
    add_settings_field('affilicart_associate_id', 'Amazon Associate ID', function() {
        $id = get_option('affilicart_associate_id', 'yourtag-20');
        echo '<input type="text" name="affilicart_associate_id" value="' . esc_attr($id) . '" class="regular-text">';
        echo '<p class="description">Enter your Amazon Associates tracking ID here.</p>';
    }, 'affilicart-settings', 'affilicart_main_section');
    add_settings_field('affilicart_post_slug', 'Product URL Slug', function() {
        if ( AFFILICART_PRO_ACTIVE ) {
            $slug = get_option('affilicart_post_slug', 'product');
            echo '<input type="text" name="affilicart_post_slug" value="' . esc_attr($slug) . '" class="regular-text">';
            echo '<p class="description">Customize the URL path for product pages. Default: "product" (URLs will be like /product/product-name). Use only lowercase letters, numbers, and hyphens.</p>';
            echo '<p class="description"><strong>Note:</strong> After changing this, you may need to visit Settings → Permalinks and save to update WordPress rewrite rules.</p>';
        } else {
            echo '<div style="padding: 10px; background: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 3px;">';
            echo '<strong>🔒 ' . esc_html__( 'Premium Feature', 'affilicart' ) . '</strong><br>';
            echo esc_html__( 'Customize product page URLs with Affilicart Pro. ', 'affilicart' );
            echo '<a href="' . esc_url( add_query_arg( array( 'post_type' => 'amazon_product', 'page' => 'affilicart-settings', 'tab' => 'upgrade' ), admin_url( 'edit.php' ) ) ) . '" style="color: #2196F3; font-weight: bold;">' . esc_html__( 'Upgrade to Pro →', 'affilicart' ) . '</a>';
            echo '</div>';
        }
    }, 'affilicart-settings', 'affilicart_main_section');
    add_settings_field('affilicart_accent_color', 'Accent Color', function() {
        if ( AFFILICART_PRO_ACTIVE ) {
            $color = get_option('affilicart_accent_color', '#0073aa');
            echo '<div style="display: flex; align-items: center; gap: 10px;">';
            echo '<input type="text" name="affilicart_accent_color" value="' . esc_attr($color) . '" id="affilicart_hex_input" placeholder="#0073aa" style="width: 120px; font-family: monospace; padding: 8px; border: 1px solid #ccc; border-radius: 3px;" maxlength="7">';
            echo '<input type="color" id="affilicart_accent_color" style="width: 50px; height: 40px; cursor: pointer; border: 1px solid #ccc; border-radius: 3px;">';
            echo '<button type="button" class="button" onclick="document.getElementById(\'affilicart_hex_input\').value = \'#0073aa\'; document.getElementById(\'affilicart_accent_color\').value = \'#0073aa\';">Reset to Default</button>';
            echo '</div>';
            echo '<p class="description">Enter hex code directly or use the color picker. Default: WordPress Blue (#0073aa).</p>';
            echo '<script>
                const hexInput = document.getElementById("affilicart_hex_input");
                const colorPicker = document.getElementById("affilicart_accent_color");
                
                // Set initial color picker value
                colorPicker.value = hexInput.value || "#0073aa";
                
                // Update color picker when hex input changes
                hexInput.addEventListener("input", function() {
                    if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                        colorPicker.value = this.value;
                    }
                });
                
                // Update hex input when color picker changes
                colorPicker.addEventListener("input", function() {
                    hexInput.value = this.value;
                });
            </script>';
        } else {
            echo '<div style="padding: 10px; background: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 3px;">';
            echo '<strong>🔒 ' . esc_html__( 'Premium Feature', 'affilicart' ) . '</strong><br>';
            echo esc_html__( 'Customize your accent color with Affilicart Pro. ', 'affilicart' );
            echo '<a href="' . esc_url( add_query_arg( array( 'post_type' => 'amazon_product', 'page' => 'affilicart-settings', 'tab' => 'upgrade' ), admin_url( 'edit.php' ) ) ) . '" style="color: #2196F3; font-weight: bold;">' . esc_html__( 'Upgrade to Pro →', 'affilicart' ) . '</a>';
            echo '</div>';
        }
    }, 'affilicart-settings', 'affilicart_main_section');
    add_settings_field('affilicart_cart_position', 'Cart Position', function() {
        if ( AFFILICART_PRO_ACTIVE ) {
            $position = get_option('affilicart_cart_position');
            $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
            
            // Ensure position is valid, default to bottom-right
            $valid_positions = array('top-left', 'top-right', 'bottom-left', 'bottom-right');
            if ($is_divi) {
                $valid_positions[] = 'divi-menu';
            }
            if (empty($position) || !in_array($position, $valid_positions)) {
                $position = $is_divi ? 'divi-menu' : 'bottom-right';
            }
            
            $positions = array(
                'top-left' => 'Top Left',
                'top-right' => 'Top Right',
                'bottom-left' => 'Bottom Left',
                'bottom-right' => 'Bottom Right'
            );
            
            if ($is_divi) {
                $positions['divi-menu'] = 'Divi Menu (alongside search icon)';
            }
            
            echo '<div style="display: flex; flex-direction: column; gap: 8px;">';
            foreach ($positions as $value => $label) {
                echo '<label style="display: flex; align-items: center; gap: 8px; margin: 0;">';
                echo '<input type="radio" name="affilicart_cart_position" value="' . esc_attr($value) . '" ' . checked($position, $value, false) . '>';
                echo esc_html($label);
                echo '</label>';
            }
            echo '</div>';
            echo '<p class="description">Choose where the shopping cart icon should appear. ' . ($is_divi ? 'Divi theme users can display in the Divi menu.' : '') . '</p>';
        } else {
            echo '<div style="padding: 10px; background: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 3px;">';
            echo '<strong>🔒 ' . esc_html__( 'Premium Feature', 'affilicart' ) . '</strong><br>';
            echo esc_html__( 'Customize your cart position with Affilicart Pro. ', 'affilicart' );
            echo '<a href="' . esc_url( add_query_arg( array( 'post_type' => 'amazon_product', 'page' => 'affilicart-settings', 'tab' => 'upgrade' ), admin_url( 'edit.php' ) ) ) . '" style="color: #2196F3; font-weight: bold;">' . esc_html__( 'Upgrade to Pro →', 'affilicart' ) . '</a>';
            echo '</div>';
        }
    }, 'affilicart-settings', 'affilicart_main_section');
    add_settings_field('affilicart_lightbox', 'Image Lightbox', function() {
        if ( AFFILICART_PRO_ACTIVE ) {
            $enabled = get_option('affilicart_lightbox', true);
            echo '<label><input type="checkbox" name="affilicart_lightbox" value="1" ' . checked($enabled, 1, false) . '> Enable lightbox effect when clicking product images</label>';
            echo '<p class="description">When enabled, clicking a product image on single product pages will open it in a fullscreen lightbox viewer.</p>';
        } else {
            echo '<div style="padding: 10px; background: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 3px;">';
            echo '<strong>🔒 ' . esc_html__( 'Premium Feature', 'affilicart' ) . '</strong><br>';
            echo esc_html__( 'Enable image lightbox with Affilicart Pro. ', 'affilicart' );
            echo '<a href="' . esc_url( add_query_arg( array( 'post_type' => 'amazon_product', 'page' => 'affilicart-settings', 'tab' => 'upgrade' ), admin_url( 'edit.php' ) ) ) . '" style="color: #2196F3; font-weight: bold;">' . esc_html__( 'Upgrade to Pro →', 'affilicart' ) . '</a>';
            echo '</div>';
        }
    }, 'affilicart-settings', 'affilicart_main_section');
});

// Sanitize post slug setting
add_filter('sanitize_option_affilicart_post_slug', function($value) {
    if (empty($value)) {
        return 'product';
    }
    // Allow only lowercase letters, numbers, and hyphens
    return sanitize_title_with_dashes($value);
});

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
    
    // Determine cart position
    $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
    $cart_position = get_option('affilicart_cart_position');
    
    // Validate position
    $valid_positions = array('top-left', 'top-right', 'bottom-left', 'bottom-right');
    if ($is_divi) {
        $valid_positions[] = 'divi-menu';
    }
    if (empty($cart_position) || !in_array($cart_position, $valid_positions)) {
        $cart_position = $is_divi ? 'divi-menu' : 'bottom-right';
    }
    
    wp_localize_script('affilicart-js', 'affilicart_data', array(
        'products' => $products,
        'associate_tag' => get_option('affilicart_associate_id', 'default-20'),
        'accent_color' => AFFILICART_PRO_ACTIVE ? get_option('affilicart_accent_color', '#0073aa') : '#0073aa',
        'lightbox_enabled' => (bool) get_option('affilicart_lightbox', true),
        'is_divi' => $is_divi,
        'cart_position' => $cart_position,
        'product_slug' => AFFILICART_PRO_ACTIVE ? get_option('affilicart_post_slug', 'product') : 'product',
        'api_enabled' => AFFILICART_PRO_ACTIVE && get_option('affilicart_api_enabled'),
        'is_pro' => AFFILICART_PRO_ACTIVE
    ));
});

// 6. Menu Icon & Modal (only add if NOT using floating cart)
add_filter('wp_nav_menu_items', function($items) {
    $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
    $cart_position = get_option('affilicart_cart_position', $is_divi ? 'divi-menu' : 'bottom-right');
    
    // For non-Divi themes, add menu item if NOT using floating positions
    $floating_positions = array('top-left', 'top-right', 'bottom-left', 'bottom-right');
    if (!$is_divi && !in_array($cart_position, $floating_positions)) {
        $items .= '<li class="menu-item ac-menu-cart"><a href="#" onclick="showCartModal(); return false;"><span class="dashicons dashicons-cart"></span> <span id="cart-count">0</span></a></li>';
    }
    return $items;
}, 10, 2);

// For Divi: inject cart into header via JavaScript (not as menu item)
add_action('wp_footer', function() {
    $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
    $cart_position = get_option('affilicart_cart_position', $is_divi ? 'divi-menu' : 'bottom-right');
    
    if ($is_divi && $cart_position === 'divi-menu') {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const navBar = document.getElementById("et-top-navigation");
                if (navBar && !document.getElementById("ac-top-cart")) {
                    const cartHtml = \'<div id="ac-top-cart"><a href="#" onclick="showCartModal(); return false;"><span class="dashicons dashicons-cart"></span> <span id="cart-count">0</span></a></div>\';
                    navBar.insertAdjacentHTML("beforeend", cartHtml);
                    
                    // Update cart count
                    const cart = JSON.parse(localStorage.getItem("ac_cart")) || [];
                    const totalQuantity = cart.reduce((sum, item) => sum + item.quantity, 0);
                    const countElement = document.getElementById("cart-count");
                    if (countElement) {
                        countElement.innerText = totalQuantity;
                    }
                }
            });
        </script>';
    }
});

// Add Divi cart styling using the same approach as Divi's search icon
add_action('wp_head', function() {
    $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
    $cart_position = get_option('affilicart_cart_position', $is_divi ? 'divi-menu' : 'bottom-right');
    
    if ($is_divi && $cart_position === 'divi-menu') {
        echo '<style>
            @keyframes ac-shake-divi {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
                20%, 40%, 60%, 80% { transform: translateX(2px); }
            }
            
            #ac-top-cart {
                float: right;
                margin: -5px 10px 0 15px;
                position: relative;
                display: block;
                width: auto;
                padding: 0;
                z-index: 10;
            }
            
            @media (max-width: 980px) {
                #ac-top-cart {
                    margin: 0 20px 0 0 !important;
                }
            }
            
            #ac-top-cart.ac-shake {
                animation: ac-shake-divi 0.8s ease-in-out;
            }
            
            .et_search_form_container input {
                background-color: inherit !important;
                color: inherit !important;
            }
            
            #ac-top-cart a {
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
                color: #666 !important;
                text-decoration: none !important;
                font-size: 16px !important;
                cursor: pointer;
                padding: 0 !important;
            }
            #ac-top-cart a:hover {
                opacity: 0.8;
            }
            #ac-top-cart .dashicons-cart {
                font-size: 16px;
            }
            #ac-top-cart #cart-count {
                background: var(--ac-accent-color, #007cba) !important;
                color: white !important;
                border-radius: 50% !important;
                width: 20px !important;
                height: 20px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 0.7rem !important;
                font-weight: bold !important;
                line-height: 1 !important;
                min-width: 20px !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
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
    }
    
    // Always include lightbox styles (not Divi-specific)
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

// Pass Divi info to JavaScript
add_action('wp_footer', function() {
    $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
    $cart_position = get_option('affilicart_cart_position', $is_divi ? 'divi-menu' : 'bottom-right');
    
    if ($is_divi && $cart_position === 'divi-menu') {
        echo '<script>window.affilicart_is_divi = true;</script>';
    }
}, 1);

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
        $custom_slug = AFFILICART_PRO_ACTIVE ? get_option('affilicart_post_slug', 'product') : 'product';
        
        while ($query->have_posts()) {
            $query->the_post();
            $product_id = get_the_ID();
            $product_slug = get_post_field('post_name', $product_id);
            $product_url = '/' . $custom_slug . '/' . $product_slug . '/';
            $thumbnail_url = get_the_post_thumbnail_url($product_id, 'medium');
            $product_title = get_the_title($product_id);
            $description = wp_trim_words(get_post_meta($product_id, 'product_description', true), 15);
            $asin = get_post_meta($product_id, '_affilicart_asin', true);
            
            $html .= '<div class="col-md-4 mb-4">';
            $html .= '<div class="ac-product-card">';
            
            if ($a['show_image'] === 'yes' && $thumbnail_url) {
                if ( AFFILICART_PRO_ACTIVE ) {
                    $html .= '<a href="' . esc_url($product_url) . '" style="text-decoration: none; color: inherit; display: block;">';
                    $html .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($product_title) . '" class="ac-product-image">';
                    $html .= '</a>';
                } else {
                    $html .= '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($product_title) . '" class="ac-product-image" style="cursor: default;">';
                }
            }
            
            if ($a['show_title'] === 'yes') {
                $html .= '<h5 class="ac-card-title">' . esc_html($product_title) . '</h5>';
            }
            
            if ($a['show_description'] === 'yes' && $description) {
                $html .= '<p class="ac-card-text">' . esc_html($description) . '</p>';
            }
            
            $html .= '<button class="btn btn-primary w-100 ac-grid-btn" onclick="addToCart(' . intval($product_id) . ', false)" style="background-color: var(--ac-accent-color, #007cba); border-color: var(--ac-accent-color, #007cba);">Add to Cart</button>';
            
            if ($asin) {
                $amazon_url = 'https://www.amazon.com/dp/' . esc_attr($asin) . '?tag=' . esc_attr($associate_tag);
                $html .= '<a href="' . esc_url($amazon_url) . '" target="_blank" rel="noopener noreferrer" style="display: block; text-align: center; margin-top: 12px; color: #aaa; text-decoration: none; font-size: 14px;"><span class="dashicons dashicons-external" style="display: inline; width: auto; height: auto; font-size: 14px; margin-right: 4px;"></span> View Price on Amazon</a>';
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

// 10. Custom Single Product Template
add_filter('template_include', function($template) {
    if (is_singular('amazon_product')) {
        return plugin_dir_path(__FILE__) . 'single-product.php';
    }
    return $template;
});
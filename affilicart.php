<?php
/**
 * Plugin Name: Affilicart Affiliate Manager
 * Description: A simple Amazon Affiliate product manager with settings and menu cart.
 * Version: 1.3
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Activation Hook - Flush Rewrite Rules
register_activation_hook( __FILE__, function() {
    affilicart_register_post_type();
    flush_rewrite_rules();
});

// 1. Register Custom Post Type
function affilicart_register_post_type() {
    $custom_slug = get_option('affilicart_post_slug', 'product');
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
        'taxonomies' => array( 'category', 'post_tag' ),
        'menu_icon' => 'dashicons-cart',
        'show_in_rest' => true,
        'rewrite' => array( 'slug' => $custom_slug ),
    );
    register_post_type( 'amazon_product', $args );
}
add_action( 'init', 'affilicart_register_post_type' );

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
    if ( !isset( $_POST['product_description_nonce'] ) || !wp_verify_nonce( $_POST['product_description_nonce'], 'product_description_nonce' ) ) return;
    if ( isset( $_POST['product_description'] ) ) {
        update_post_meta( $post_id, 'product_description', sanitize_textarea_field( $_POST['product_description'] ) );
    }
});

// Customize the product editor
add_action( 'edit_form_top', function( $post ) {
    if ( $post->post_type === 'amazon_product' ) {
        echo '<div style="background: #f0f6fc; border-left: 4px solid #007cba; padding: 15px; margin: 20px 0; border-radius: 3px;">';
        echo '<strong>💡 Need help?</strong> Visit <strong>Products → Settings → How To</strong> for a complete guide.';
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
add_action('manage_amazon_product_posts_custom_column', function($column, $post_id) {
    if ($column === 'affilicart_shortcode') {
        $grid_shortcode = '[affilicart_grid id="' . $post_id . '"]';
        $button_shortcode = '[affilicart_button id="' . $post_id . '"]';
        
        echo '<div style="display: flex; gap: 20px;">';
        
        // Grid Shortcode
        echo '<div>';
        echo '<div style="font-size: 12px; color: #666; margin-bottom: 4px;"><strong>Grid:</strong></div>';
        echo '<div style="display: flex; align-items: center; gap: 8px;">';
        echo '<code style="background:#eee; padding:5px; border-radius:3px;" id="affilicart-grid-' . $post_id . '">' . $grid_shortcode . '</code>';
        echo '<button type="button" style="background: none; border: none; cursor: pointer; padding: 0; color: #666666; font-size: 16px;" onclick="copyToClipboard(\'' . esc_attr($grid_shortcode) . '\', this)" title="Copy grid shortcode">';
        echo '<span class="dashicons dashicons-admin-page"></span>';
        echo '</button>';
        echo '</div>';
        echo '</div>';
        
        // Button Shortcode
        echo '<div>';
        echo '<div style="font-size: 12px; color: #666; margin-bottom: 4px;"><strong>Button:</strong></div>';
        echo '<div style="display: flex; align-items: center; gap: 8px;">';
        echo '<code style="background:#eee; padding:5px; border-radius:3px;" id="affilicart-button-' . $post_id . '">' . $button_shortcode . '</code>';
        echo '<button type="button" style="background: none; border: none; cursor: pointer; padding: 0; color: #666666; font-size: 16px;" onclick="copyToClipboard(\'' . esc_attr($button_shortcode) . '\', this)" title="Copy button shortcode">';
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
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
    ?>
    <div class="wrap">
        <h1>Affilicart Settings</h1>
        
        <div class="nav-tab-wrapper" style="border-bottom: 1px solid #ccc; margin-bottom: 20px;">
            <a href="?post_type=amazon_product&page=affilicart-settings&tab=settings" class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?post_type=amazon_product&page=affilicart-settings&tab=how-to" class="nav-tab <?php echo $current_tab === 'how-to' ? 'nav-tab-active' : ''; ?>">How To</a>
            <a href="?post_type=amazon_product&page=affilicart-settings&tab=disclaimer" class="nav-tab <?php echo $current_tab === 'disclaimer' ? 'nav-tab-active' : ''; ?>">Disclaimer</a>
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
                    <li><strong>Accent Color:</strong> Customize button colors to match your site's branding</li>
                    <li><strong>Divi Menu Cart:</strong> If using Divi theme, enable to show a shopping cart icon in your menu</li>
                    <li><strong>Image Lightbox:</strong> When enabled, clicking product images opens a fullscreen viewer with high-resolution images</li>
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
        <?php endif; ?>
    </div>
    <?php
}

add_action('admin_init', function() {
    register_setting('affilicart_settings_group', 'affilicart_associate_id');
    register_setting('affilicart_settings_group', 'affilicart_accent_color');
    register_setting('affilicart_settings_group', 'affilicart_divi_cart');
    register_setting('affilicart_settings_group', 'affilicart_cart_display');
    register_setting('affilicart_settings_group', 'affilicart_cart_position');
    register_setting('affilicart_settings_group', 'affilicart_lightbox');
    register_setting('affilicart_settings_group', 'affilicart_post_slug');
    add_settings_section('affilicart_main_section', 'Main Settings', null, 'affilicart-settings');
    add_settings_field('affilicart_post_slug', 'Product URL Slug', function() {
        $slug = get_option('affilicart_post_slug', 'product');
        echo '<input type="text" name="affilicart_post_slug" value="' . esc_attr($slug) . '" class="regular-text">';
        echo '<p class="description">Customize the URL path for product pages. Default: "product" (URLs will be like /product/product-name). Use only lowercase letters, numbers, and hyphens.</p>';
        echo '<p class="description"><strong>Note:</strong> After changing this, you may need to visit Settings → Permalinks and save to update WordPress rewrite rules.</p>';
    }, 'affilicart-settings', 'affilicart_main_section');
    add_settings_field('affilicart_associate_id', 'Amazon Associate ID', function() {
        $id = get_option('affilicart_associate_id', 'yourtag-20');
        echo '<input type="text" name="affilicart_associate_id" value="' . esc_attr($id) . '" class="regular-text">';
        echo '<p class="description">Enter your Amazon Associates tracking ID here.</p>';
    }, 'affilicart-settings', 'affilicart_main_section');
    add_settings_field('affilicart_accent_color', 'Accent Color', function() {
        $color = get_option('affilicart_accent_color', '#007cba');
        echo '<div style="display: flex; align-items: center; gap: 10px;">';
        echo '<input type="text" name="affilicart_accent_color" value="' . esc_attr($color) . '" id="affilicart_hex_input" placeholder="#007cba" style="width: 120px; font-family: monospace; padding: 8px; border: 1px solid #ccc; border-radius: 3px;" maxlength="7">';
        echo '<input type="color" id="affilicart_accent_color" style="width: 50px; height: 40px; cursor: pointer; border: 1px solid #ccc; border-radius: 3px;">';
        echo '<button type="button" class="button" onclick="document.getElementById(\'affilicart_hex_input\').value = \'#007cba\'; document.getElementById(\'affilicart_accent_color\').value = \'#007cba\';">Reset to Default</button>';
        echo '</div>';
        echo '<p class="description">Enter hex code directly or use the color picker. Default: WordPress Blue (#007cba).</p>';
        echo '<script>
            const hexInput = document.getElementById("affilicart_hex_input");
            const colorPicker = document.getElementById("affilicart_accent_color");
            
            // Set initial color picker value
            colorPicker.value = hexInput.value || "#007cba";
            
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
    }, 'affilicart-settings', 'affilicart_main_section');
    add_settings_field('affilicart_cart_display', 'Shopping Cart Display', function() {
        $display = get_option('affilicart_cart_display', 'auto');
        $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
        
        echo '<select name="affilicart_cart_display">';
        echo '<option value="auto" ' . selected($display, 'auto', false) . '>' . ($is_divi ? 'Auto (Divi Menu)' : 'Auto (Floating Button)') . '</option>';
        echo '<option value="floating" ' . selected($display, 'floating', false) . '>Floating Button</option>';
        if ($is_divi) {
            echo '<option value="menu" ' . selected($display, 'menu', false) . '>Divi Menu</option>';
        }
        echo '</select>';
        echo '<p class="description">Choose how the shopping cart icon is displayed. Floating works on all themes.</p>';
    }, 'affilicart-settings', 'affilicart_main_section');
    add_settings_field('affilicart_cart_position', 'Floating Cart Position', function() {
        $position = get_option('affilicart_cart_position', 'bottom-right');
        $positions = array(
            'top-left' => 'Top Left',
            'top-right' => 'Top Right',
            'bottom-left' => 'Bottom Left',
            'bottom-right' => 'Bottom Right'
        );
        
        echo '<div style="display: flex; flex-direction: column; gap: 8px;">';
        foreach ($positions as $value => $label) {
            echo '<label style="display: flex; align-items: center; gap: 8px; margin: 0;">';
            echo '<input type="radio" name="affilicart_cart_position" value="' . esc_attr($value) . '" ' . checked($position, $value, false) . '>';
            echo $label;
            echo '</label>';
        }
        echo '</div>';
        echo '<p class="description">Select where the floating cart icon should appear on the page. Only applies when using floating cart display.</p>';
    }, 'affilicart-settings', 'affilicart_main_section');
    add_settings_field('affilicart_divi_cart', 'Divi Menu Cart Display', function() {
        $enabled = get_option('affilicart_divi_cart', false);
        $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
        
        if (!$is_divi) {
            echo '<p style="color: #d63638;"><strong>⚠️ Divi is not active</strong> — This option only works on Divi sites.</p>';
            echo '<input type="hidden" name="affilicart_divi_cart" value="0">';
        } else {
            echo '<label><input type="checkbox" name="affilicart_divi_cart" value="1" ' . checked($enabled, 1, false) . '> Display cart icon in the Divi menu alongside the search icon</label>';
            echo '<p class="description">When enabled, the cart icon will appear in your Divi menu with a fixed position so it\'s always visible. (Deprecated: Use "Shopping Cart Display" option above instead)</p>';
        }
    }, 'affilicart-settings', 'affilicart_main_section');
    add_settings_field('affilicart_lightbox', 'Image Lightbox', function() {
        $enabled = get_option('affilicart_lightbox', true);
        echo '<label><input type="checkbox" name="affilicart_lightbox" value="1" ' . checked($enabled, 1, false) . '> Enable lightbox effect when clicking product images</label>';
        echo '<p class="description">When enabled, clicking a product image will open it in a fullscreen lightbox viewer.</p>';
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
    $price = get_post_meta( $post->ID, '_affilicart_price', true );
    ?>
    <p><label>ASIN:</label><input type="text" name="affilicart_asin" value="<?php echo esc_attr($asin); ?>" class="widefat"></p>
    <p><label>Price ($):</label><input type="text" name="affilicart_price" value="<?php echo esc_attr($price); ?>" class="widefat"></p>
    <?php
}
add_action( 'save_post', function($post_id) {
    if ( isset( $_POST['affilicart_asin'] ) ) update_post_meta( $post_id, '_affilicart_asin', sanitize_text_field( $_POST['affilicart_asin'] ) );
    if ( isset( $_POST['affilicart_price'] ) ) update_post_meta( $post_id, '_affilicart_price', sanitize_text_field( $_POST['affilicart_price'] ) );
});

// 5. Enqueue & Global Logic
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css');
    wp_enqueue_style('affilicart-style', plugins_url('style.css', __FILE__));
    wp_enqueue_script('bootstrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), null, true);
    wp_enqueue_script('affilicart-js', plugins_url('scripts.js', __FILE__), array('jquery', 'bootstrap-bundle'), null, true);

    $products = array();
    $query = new WP_Query(array('post_type' => 'amazon_product', 'posts_per_page' => -1, 'post_status' => 'publish'));
    foreach ($query->posts as $p) {
        $categories = wp_get_post_terms($p->ID, 'category', array('fields' => 'names'));
        $tags = wp_get_post_terms($p->ID, 'post_tag', array('fields' => 'names'));
        $products[] = array(
            'id' => $p->ID, 'name' => get_the_title($p->ID),
            'description' => wp_trim_words(get_post_meta($p->ID, 'product_description', true), 15),
            'image' => get_the_post_thumbnail_url($p->ID, 'medium'),
            'image_full' => get_the_post_thumbnail_url($p->ID, 'full'),
            'price' => get_post_meta($p->ID, '_affilicart_price', true),
            'asin' => get_post_meta($p->ID, '_affilicart_asin', true),
            'categories' => is_array($categories) ? $categories : array(),
            'tags' => is_array($tags) ? $tags : array(),
        );
    }
    
    // Determine cart display setting
    $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
    $cart_display = get_option('affilicart_cart_display', 'auto');
    $divi_cart_enabled = get_option('affilicart_divi_cart', false);
    
    // For backward compatibility: if old checkbox is unchecked on Divi, use floating
    if ($is_divi && !$divi_cart_enabled && $cart_display === 'auto') {
        $cart_display = 'floating';
    }
    
    wp_localize_script('affilicart-js', 'affilicart_data', array(
        'products' => $products,
        'associate_tag' => get_option('affilicart_associate_id', 'default-20'),
        'accent_color' => get_option('affilicart_accent_color', '#007cba'),
        'lightbox_enabled' => (bool) get_option('affilicart_lightbox', true),
        'is_divi' => $is_divi,
        'cart_display' => $cart_display,
        'cart_position' => get_option('affilicart_cart_position', 'bottom-right')
    ));
});

// 6. Menu Icon & Modal (only add if NOT using floating cart)
add_filter('wp_nav_menu_items', function($items) {
    $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
    $cart_display = get_option('affilicart_cart_display', 'auto');
    $divi_cart_enabled = get_option('affilicart_divi_cart', false);
    
    // For backward compatibility: if old checkbox is unchecked on Divi, use floating
    if ($is_divi && !$divi_cart_enabled && $cart_display === 'auto') {
        $cart_display = 'floating';
    }
    
    // Resolve 'auto' to actual display type
    if ($cart_display === 'auto') {
        $cart_display = $is_divi ? 'menu' : 'floating';
    }
    
    // For non-Divi themes, add menu item if using menu display
    if (!$is_divi && $cart_display !== 'floating') {
        $items .= '<li class="menu-item ac-menu-cart"><a href="#" data-bs-toggle="modal" data-bs-target="#cartModal"><i class="bi bi-cart"></i> <span id="cart-count">0</span></a></li>';
    }
    return $items;
}, 10, 2);

// For Divi: inject cart into header via JavaScript (not as menu item)
add_action('wp_footer', function() {
    $is_divi = function_exists('et_setup_theme') || defined('ET_BUILDER_PLUGIN_VERSION');
    $cart_display = get_option('affilicart_cart_display', 'auto');
    $divi_cart_enabled = get_option('affilicart_divi_cart', false);
    
    // For backward compatibility: if old checkbox is unchecked on Divi, use floating
    if ($is_divi && !$divi_cart_enabled && $cart_display === 'auto') {
        $cart_display = 'floating';
    }
    
    // Resolve 'auto' to actual display type
    if ($cart_display === 'auto') {
        $cart_display = $is_divi ? 'menu' : 'floating';
    }
    
    if ($is_divi && $cart_display === 'menu') {
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const navBar = document.getElementById("et-top-navigation");
                if (navBar && !document.getElementById("ac-top-cart")) {
                    const cartHtml = \'<div id="ac-top-cart"><a href="#" data-bs-toggle="modal" data-bs-target="#cartModal"><i class="bi bi-cart"></i> <span id="cart-count">0</span></a></div>\';
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
    $cart_display = get_option('affilicart_cart_display', 'auto');
    $divi_cart_enabled = get_option('affilicart_divi_cart', false);
    
    // For backward compatibility: if old checkbox is unchecked on Divi, use floating
    if ($is_divi && !$divi_cart_enabled && $cart_display === 'auto') {
        $cart_display = 'floating';
    }
    
    // Resolve 'auto' to actual display type
    if ($cart_display === 'auto') {
        $cart_display = $is_divi ? 'menu' : 'floating';
    }
    
    if ($is_divi && $cart_display === 'menu') {
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
            #ac-top-cart .bi-cart {
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
    $divi_cart_enabled = get_option('affilicart_divi_cart', false);
    
    if ($is_divi && $divi_cart_enabled) {
        echo '<script>window.affilicart_is_divi = true;</script>';
    }
}, 1);

add_action('wp_footer', function() { ?>
    <div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true" style="z-index: 999999;">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-cart3"></i> Cart</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div id="cart-empty-alert" class="alert alert-danger" role="alert" style="display: none;">
                    <strong>Cart is Empty</strong> — Add products to your cart to proceed.
                </div>
                <ul id="cart-items" class="list-group list-group-flush"></ul>
            </div>
            <div class="modal-footer d-flex justify-content-end align-items-end pe-3">
                <div style="text-align: right; margin-right: 20px;">
                    <div id="grand-total" class="fw-bold fs-5">Total: $0.00</div>
                </div>
                <div style="text-align: right;">
                    <button id="checkout-button" type="button" class="btn btn-success">Checkout</button>
                    <p style="font-size: 12px; color: #666; margin: 8px 0 0 0;">You will be redirected to Amazon.com to complete your purchase.</p>
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
        'show_price' => 'yes'
    ), $atts);
    return '<div class="ac-shop-wrapper"><div id="product-list" class="row" data-single-id="'.esc_attr($a['id']).'" data-show-image="'.esc_attr($a['show_image']).'" data-show-title="'.esc_attr($a['show_title']).'" data-show-description="'.esc_attr($a['show_description']).'" data-show-price="'.esc_attr($a['show_price']).'"></div></div>';
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
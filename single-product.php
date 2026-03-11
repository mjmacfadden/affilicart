<?php
/**
 * Single Product Template for Affilicart
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Single product pages are a Pro feature
if ( ! defined( 'AFFILICART_PRO_VERSION' ) ) {
    // Pro not active, redirect to shop or show message
    get_header();
    echo '<div style="max-width: 900px; margin: 60px auto; padding: 40px; text-align: center;">';
    echo '<h1>' . esc_html__( 'Single Product Pages are a Pro Feature', 'affilicart' ) . '</h1>';
    echo '<p style="font-size: 16px; color: #666; margin: 20px 0;">';
    echo esc_html__( 'This feature is only available with Affilicart Pro. ', 'affilicart' );
    echo '<a href="' . esc_url( admin_url( 'admin.php?post_type=amazon_product&page=affilicart-settings&tab=upgrade' ) ) . '" style="color: #2fbdb6; font-weight: bold;">' . esc_html__( 'Upgrade to Pro →', 'affilicart' ) . '</a>';
    echo '</p>';
    echo '</div>';
    get_footer();
    return;
}

get_header();

if (have_posts()) {
    the_post();
    $affilicart_product_id = get_the_ID();
    $affilicart_product_title = get_the_title();
    $affilicart_product_image = get_the_post_thumbnail_url($affilicart_product_id, 'large');
    $affilicart_product_description = get_post_meta($affilicart_product_id, 'product_description', true);
    $affilicart_product_asin = get_post_meta($affilicart_product_id, '_affilicart_asin', true);
    $affilicart_product_slug = get_option('affilicart_post_slug', 'product');
    ?>
    <div class="affilicart-single-product" id="ac-single-product">
        <style>
            .affilicart-single-product {
                max-width: 1200px;
                margin: 40px auto;
                padding: 0 20px;
            }
            .product-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 40px;
                align-items: start;
            }
            .product-image {
                width: 100%;
                max-width: 500px;
                border-radius: 8px;
                cursor: pointer;
                transition: transform 0.3s ease;
            }
            .product-image:hover {
                transform: scale(1.02);
            }
            .product-details {
                padding: 20px 0;
            }
            .product-title {
                font-size: 32px;
                font-weight: 700;
                margin: 0 0 20px 0;
                line-height: 1.3;
                color: #1a1a1a;
            }
            .product-price {
                font-size: 28px;
                font-weight: 600;
                color: var(--ac-accent-color, #007cba);
                margin: 0 0 10px 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .price-disclaimer {
                font-size: 12px;
                color: #999;
                margin-bottom: 25px;
            }
            .product-description {
                font-size: 16px;
                line-height: 1.7;
                color: #555;
                margin: 30px 0;
            }
            .product-actions {
                display: flex;
                gap: 15px;
                margin-top: 30px;
                flex-wrap: wrap;
            }
            .btn-add-to-cart {
                background-color: var(--ac-accent-color, #007cba);
                color: white;
                border: none;
                padding: 14px 32px;
                font-size: 16px;
                font-weight: 600;
                border-radius: 4px;
                cursor: pointer;
                transition: background-color 0.3s ease;
                min-width: 200px;
                text-align: center;
            }
            .btn-add-to-cart:hover {
                filter: brightness(0.9);
            }
            .share-icon {
                cursor: pointer;
                font-size: 20px;
                color: #666;
                transition: all 0.2s ease;
                padding: 4px 8px;
                margin-left: 12px;
            }
            .share-icon:hover {
                color: var(--ac-accent-color, #007cba);
                transform: scale(1.1);
            }
            .copy-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: var(--ac-accent-color, #007cba);
                color: white;
                padding: 12px 20px;
                border-radius: 4px;
                font-size: 14px;
                font-weight: 600;
                z-index: 999999;
                animation: slideIn 0.3s ease, slideOut 0.3s ease 1.7s;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
            .product-title-wrapper {
                display: flex;
                align-items: flex-start;
                gap: 4px;
            }
            .product-meta {
                margin-top: 40px;
                padding-top: 30px;
                border-top: 1px solid #eee;
                font-size: 13px;
                color: #999;
            }
            @media (max-width: 768px) {
                .product-container {
                    grid-template-columns: 1fr;
                    gap: 30px;
                }
                .product-title {
                    font-size: 24px;
                }
                .product-price {
                    font-size: 22px;
                }
                .btn-add-to-cart {
                    min-width: auto;
                    width: 100%;
                }
            }
        </style>

        <div class="product-container">
            <!-- Product Image (Left) -->
            <div class="product-image-container">
                <?php if ($affilicart_product_image): ?>
                    <img src="<?php echo esc_url($affilicart_product_image); ?>" alt="<?php echo esc_attr($affilicart_product_title); ?>" class="product-image" id="product-image-<?php echo esc_attr($affilicart_product_id); ?>" style="cursor: pointer;">
                <?php else: ?>
                    <div style="width: 100%; aspect-ratio: 1; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">No Image</div>
                <?php endif; ?>
            </div>

            <!-- Product Details (Right) -->
            <div class="product-details">
                <div class="product-title-wrapper">
                    <h1 class="product-title"><?php echo esc_html($affilicart_product_title); ?></h1>
                    <span class="dashicons dashicons-share" style="cursor: pointer;" onclick="affilicartCopyShareUrl(this);" title="Copy product URL to clipboard"></span>
                </div>
                
                <div style="margin: 12px 0; padding-bottom: 16px; border-bottom: 1px solid #eee;">
                    <p style="font-size: 11px; color: #999; margin: 0; line-height: 1.2;">As an Amazon Associate I earn from qualifying purchases.</p>
                </div>
                
                <?php 
                // Check if Pro version has API pricing available
                if ( function_exists( 'affilicart_get_product_price' ) ) {
                    $affilicart_api_price_data = affilicart_get_product_price( $affilicart_product_id );
                    if ( $affilicart_api_price_data ):
                        ?>
                        <div style="margin: 20px 0; padding: 16px; background: #f0f9ff; border-left: 4px solid #0073aa; border-radius: 4px;">
                            <div style="font-size: 28px; font-weight: 600; color: #0073aa; margin-bottom: 8px;">
                                <?php echo esc_html( $affilicart_api_price_data['price'] ); ?>
                                <span style="font-size: 12px; color: #999;">updated <?php echo esc_html( wp_date( 'M j, Y', strtotime( $affilicart_api_price_data['date'] ) ) ); ?></span>
                            </div>
                            <p style="font-size: 12px; color: #666; margin: 0;">Price may vary at checkout on Amazon.com</p>
                        </div>
                        <?php
                    else:
                        ?>
                        <div style="margin: 20px 0; margin-left: 0;">
                            <a href="https://www.amazon.com/dp/<?php echo esc_attr($affilicart_product_asin); ?>?tag=<?php echo esc_attr(get_option('affilicart_associate_id', 'default-20')); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--ac-accent-color, #0073aa); text-decoration: none; font-size: 28px; font-weight: 600; display: inline-block;">View Price on Amazon</a>
                        </div>
                        <?php
                    endif;
                }
                ?>
                
                <?php 
                // Get product categories
                $affilicart_categories = get_the_terms($affilicart_product_id, 'amazon_product_category');
                if ($affilicart_categories && !is_wp_error($affilicart_categories)):
                ?>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin: 20px 0;">
                        <?php foreach ($affilicart_categories as $affilicart_category): ?>
                            <a href="<?php echo esc_url(home_url('/' . $affilicart_product_slug . '/category/' . $affilicart_category->slug . '/')); ?>" style="display: inline-block; padding: 0 10px; background: #f0f0f0; border-radius: 16px; font-size: 11px; color: #333; text-decoration: none; border: 1px solid #ddd; transition: all 0.2s ease;">
                                <?php echo esc_html($affilicart_category->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($affilicart_product_description): ?>
                    <div class="product-description">
                        <?php echo wp_kses_post(nl2br($affilicart_product_description)); ?>
                    </div>
                <?php endif; ?>
                
            <div class="product-actions">
                    <div style="display: flex; flex-direction: column; align-items: center; width: 100%; max-width: 200px;">
                        <button class="btn-add-to-cart" onclick="addToCart(<?php echo esc_js($affilicart_product_id); ?>, false); this.textContent = 'Added to Cart'; this.classList.add('added'); setTimeout(() => { this.textContent = 'Add to Cart'; this.classList.remove('added'); }, 2000);">
                            Add to Cart
                        </button>
                        <?php if ($affilicart_product_asin): ?>
                            <div style="margin-top: 12px; text-align: center; width: 100%;">
                                <a href="<?php echo esc_url('https://www.amazon.com/dp/' . urlencode($affilicart_product_asin) . '?tag=' . get_option('affilicart_associate_id', 'default-20')); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 13px; color: #666; text-decoration: none; display: inline-block;">
                                    View on Amazon <span class="dashicons dashicons-external" style="display: inline; width: auto; height: auto; font-size: 11px;"></span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Block Editor Content -->
    <?php if (has_blocks()) : ?>
        <div class="affilicart-block-content" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
            <?php the_content(); ?>
        </div>
    <?php endif; ?>

    <?php
} else {
    echo '<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;"><p>Product not found.</p></div>';
}

get_footer();

// Share URL function
?>
<script>
function affilicartCopyShareUrl(icon) {
    const url = window.location.href;
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(() => {
            showCopyNotification();
        }).catch(err => {
            console.error('Failed to copy:', err);
            fallbackCopyToClipboard(url);
        });
    } else {
        fallbackCopyToClipboard(url);
    }
}

function showCopyNotification() {
    const notification = document.createElement('div');
    notification.className = 'copy-notification';
    notification.textContent = '✓ Link copied!';
    document.body.appendChild(notification);
    
    // Remove notification after animation completes
    setTimeout(() => {
        notification.remove();
    }, 2000);
}

function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    
    try {
        textArea.select();
        document.execCommand('copy');
        showCopyNotification();
    } catch (err) {
        console.error('Fallback copy failed:', err);
        alert('Could not copy URL. Please try again.');
    } finally {
        document.body.removeChild(textArea);
    }
}
</script>
<?php

<?php
/**
 * Single Product Template for Affilicart
 * Universal template that works with both Block Themes (Twenty Twenty-Five) and Classic Themes (Divi)
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Detect if this is a block theme
$is_block_theme = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();

if ( $is_block_theme ) {
    // Pre-render header and footer BEFORE wp_head() so their block assets
    // (navigation scripts/styles, etc.) are enqueued in time to appear in <head>.
    $affilicart_header_html = do_blocks( '<!-- wp:template-part {"slug":"header"} /-->' );
    $affilicart_footer_html = do_blocks( '<!-- wp:template-part {"slug":"footer"} /-->' );
    ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <div class="wp-site-blocks">
        <?php echo $affilicart_header_html; ?>
        <main id="main-content">
    <?php
} else {
    // Classic Theme approach: Use theme's get_header/get_footer
    get_header();
    echo '<div id="primary" class="content-area">';
    echo '<main id="main" class="site-main">';
}

// Check if this is a Pro feature
if ( ! defined( 'AFFILICART_PRO_VERSION' ) ) {
    // Show upgrade message for non-Pro users
    ?>
    <div style="max-width: 900px; margin: 60px auto; padding: 40px; text-align: center;">
        <h1><?php esc_html_e( 'Single Product Pages are a Pro Feature', 'affilicart' ); ?></h1>
        <p style="font-size: 16px; color: #666; margin: 20px 0;">
            <?php esc_html_e( 'This feature is only available with Affilicart Pro. ', 'affilicart' ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?post_type=amazon_product&page=affilicart-settings&tab=upgrade' ) ); ?>" style="color: #2fbdb6; font-weight: bold;">
                <?php esc_html_e( 'Upgrade to Pro →', 'affilicart' ); ?>
            </a>
        </p>
    </div>
    <?php
} else {
    // Pro feature - display product
    if ( have_posts() ) {
        the_post();
        $product_id = get_the_ID();
        $product_title = get_the_title();
        $product_image = get_the_post_thumbnail_url( $product_id, 'large' );
        $product_description = get_post_meta( $product_id, 'product_description', true );
        $product_asin = get_post_meta( $product_id, '_affilicart_asin', true );
        $product_slug = get_option( 'affilicart_post_slug', 'product' );
        
        // Retrieve categories using wp_get_post_terms for better reliability
        $product_categories = wp_get_post_terms( $product_id, 'amazon_product_category', array( 'fields' => 'all' ) );
        if ( is_wp_error( $product_categories ) ) {
            $product_categories = array();
        }
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
                    <?php if ( $product_image ) : ?>
                        <img src="<?php echo esc_url( $product_image ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" class="product-image" id="product-image-<?php echo esc_attr( $product_id ); ?>" style="cursor: pointer" />
                    <?php else : ?>
                        <div style="width: 100%; max-width: 500px; height: 500px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <p><?php esc_html_e( 'No image available', 'affilicart' ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Details (Right) -->
                <div class="product-details">
                    <div class="product-title-wrapper">
                        <h1 class="product-title"><?php echo esc_html( $product_title ); ?></h1>
                        <span class="dashicons dashicons-share" style="cursor: pointer" onclick="affilicartCopyShareUrl(this)" title="<?php esc_attr_e( 'Copy product URL to clipboard', 'affilicart' ); ?>"></span>
                    </div>

                    <div style="margin: 12px 0; padding-bottom: 16px; border-bottom: 1px solid #eee;">
                        <p style="font-size: 11px; color: #999; margin: 0; line-height: 1.2;">
                            <?php esc_html_e( 'As an Amazon Associate I earn from qualifying purchases.', 'affilicart' ); ?>
                        </p>
                    </div>

                    <?php if ( $product_asin ) : ?>
                        <div style="margin: 20px 0; margin-left: 0">
                            <a href="<?php echo esc_url( 'https://www.amazon.com/dp/' . urlencode( $product_asin ) . '?tag=' . get_option( 'affilicart_associate_id', 'default-20' ) ); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--ac-accent-color, #0073aa); text-decoration: none; font-size: 28px; font-weight: 600; display: inline-block;">
                                <?php esc_html_e( 'View Price on Amazon', 'affilicart' ); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $product_categories ) ) : ?>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin: 20px 0;">
                            <?php foreach ( $product_categories as $category ) : ?>
                                <a href="<?php echo esc_url( home_url( '/' . $product_slug . '/category/' . $category->slug . '/' ) ); ?>" style="display: inline-block; padding: 0 10px; background: #f0f0f0; border-radius: 16px; font-size: 11px; color: #333; text-decoration: none; border: 1px solid #ddd; transition: all 0.2s ease;">
                                    <?php echo esc_html( $category->name ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $product_description ) : ?>
                        <div class="product-description">
                            <?php echo wp_kses_post( nl2br( $product_description ) ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="product-actions">
                        <div style="display: flex; flex-direction: column; align-items: center; width: 100%; max-width: 200px;">
                            <button class="btn-add-to-cart" onclick="addToCart(<?php echo esc_js( $product_id ); ?>, false); this.textContent = '<?php esc_attr_e( 'Added to Cart', 'affilicart' ); ?>'; this.classList.add('added'); setTimeout(() => { this.textContent = '<?php esc_attr_e( 'Add to Cart', 'affilicart' ); ?>'; this.classList.remove('added'); }, 2000);">
                                <?php esc_html_e( 'Add to Cart', 'affilicart' ); ?>
                            </button>
                            <?php if ( $product_asin ) : ?>
                                <div style="margin-top: 12px; text-align: center; width: 100%;">
                                    <a href="<?php echo esc_url( 'https://www.amazon.com/dp/' . urlencode( $product_asin ) . '?tag=' . get_option( 'affilicart_associate_id', 'default-20' ) ); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 13px; color: #666; text-decoration: none; display: inline-block;">
                                        <?php esc_html_e( 'View on Amazon', 'affilicart' ); ?>
                                        <span class="dashicons dashicons-external" style="display: inline; width: auto; height: auto; font-size: 11px;"></span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Block Editor Content -->
            <?php if ( has_blocks() ) : ?>
                <div class="affilicart-block-content" style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    } else {
        echo '<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;"><p>' . esc_html__( 'Product not found.', 'affilicart' ) . '</p></div>';
    }
}

// Close main and container tags based on theme type
if ( $is_block_theme ) {
    ?>
        </main><!-- #main-content -->
        <?php echo $affilicart_footer_html; ?>
    </div><!-- .wp-site-blocks -->
    <?php wp_footer(); ?>
</body>
</html>
    <?php
} else {
    // Classic theme closing structure
    echo '</main>';
    echo '</div>';
    get_footer();
}

// JavaScript functions - only for classic themes (block themes handle scripts via blocks)
if ( ! $is_block_theme ) {
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
} // End of if ( ! $is_block_theme )

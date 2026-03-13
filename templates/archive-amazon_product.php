<?php
/**
 * Archive Template for Amazon Products - Affilicart
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

// Archive page heading and description
echo '<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">';
if ( get_query_var( 'affilicart_show_all' ) ) {
    echo '<h1>' . esc_html__( 'All Products (A–Z)', 'affilicart' ) . '</h1>';
} else {
    echo '<h1>' . post_type_archive_title() . '</h1>';
}
echo '</div>';

// Display products in a grid
if ( have_posts() ) {
    echo '<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">';
    echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 30px;">';
    
    while ( have_posts() ) {
        the_post();
        $product_id = get_the_ID();
        $product_title = get_the_title();
        $product_image = get_the_post_thumbnail_url( $product_id, 'medium' );
        $product_description = get_post_meta( $product_id, 'product_description', true );
        $product_url = get_the_permalink();
        $product_asin = get_post_meta( $product_id, '_affilicart_asin', true );
        $associate_id = get_option( 'affilicart_associate_id', 'default-20' );
        ?>
        <div style="display: flex; flex-direction: column; align-items: center; text-align: center; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; transition: all 0.3s ease; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px;">
            <!-- Product Image -->
            <div style="width: 100%; height: 200px; margin-bottom: 15px; overflow: hidden; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
                <?php if ( $product_image ) : ?>
                    <a href="<?php echo esc_url( $product_url ); ?>" style="display: flex; align-items: center; justify-content: center; text-decoration: none; width: 100%; height: 100%;">
                        <img src="<?php echo esc_url( $product_image ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" style="max-width: 100%; max-height: 100%; height: auto; width: auto; border-radius: 6px;" />
                    </a>
                <?php else : ?>
                    <p style="color: #999; font-size: 14px;"><?php esc_html_e( 'No image', 'affilicart' ); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Product Title -->
            <h3 style="font-size: 15px; font-weight: 600; margin: 0 0 10px 0; line-height: 1.4; color: #333;">
                <a href="<?php echo esc_url( $product_url ); ?>" style="color: #333; text-decoration: none;">
                    <?php echo esc_html( $product_title ); ?>
                </a>
            </h3>
            
            <!-- Product Description -->
            <?php if ( $product_description ) : ?>
                <p style="font-size: 13px; color: #666; margin: 0 0 12px 0; line-height: 1.5; flex-grow: 1;">
                    <?php echo wp_kses_post( wp_trim_words( $product_description, 15 ) ); ?>
                </p>
            <?php endif; ?>
            
            <!-- Add to Cart Button -->
            <button onclick="addToCart(<?php echo esc_js( $product_id ); ?>, false); this.textContent = '<?php esc_attr_e( 'Added to Cart', 'affilicart' ); ?>'; this.classList.add('added'); setTimeout(() => { this.textContent = '<?php esc_attr_e( 'Add to Cart', 'affilicart' ); ?>'; this.classList.remove('added'); }, 2000);" style="padding: 8px 16px; margin-bottom: 12px; background-color: var(--ac-accent-color, #007cba); color: white; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: 600; transition: opacity 0.2s ease; border: none; cursor: pointer;">
                <?php esc_html_e( 'Add to Cart', 'affilicart' ); ?>
            </button>
            
            <!-- View Price on Amazon Link -->
            <?php if ( $product_asin ) : ?>
                <a href="<?php echo esc_url( 'https://www.amazon.com/dp/' . urlencode( $product_asin ) . '?tag=' . $associate_id ); ?>" target="_blank" rel="noopener noreferrer" style="color: #666; text-decoration: none; font-size: 13px; font-weight: 600; margin-bottom: 10px; display: inline-flex; align-items: center; gap: 4px;">
                    <span><?php esc_html_e( 'View Price on Amazon', 'affilicart' ); ?></span>
                    <span class="dashicons dashicons-external" style="display: inline-block; width: auto; height: auto; font-size: 11px; line-height: 1; vertical-align: middle;"></span>
                </a>
            <?php endif; ?>
            
            <!-- Amazon Disclaimer -->
            <p style="font-size: 11px; color: #999; margin: 0; line-height: 1.4;">
                <?php esc_html_e( 'As an Amazon Associate I earn from qualifying purchases.', 'affilicart' ); ?>
            </p>
        </div>
        <?php
    }
    
    echo '</div>'; // End grid
    echo '</div>'; // End max-width container
    
    // Pagination
    echo '<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px; text-align: center;">';
    echo paginate_links();
    echo '</div>';
} else {
    echo '<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">';
    echo '<p>' . esc_html__( 'No products found.', 'affilicart' ) . '</p>';
    echo '</div>';
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

<?php
/**
 * Archive Template for Amazon Products by Category
 * Displays products filtered by category
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div class="ac-archive-wrapper" style="padding: 40px 20px; max-width: 1200px; margin: 0 auto;">
    
    <!-- Page Title -->
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 32px; margin-bottom: 10px; color: #333;">
            <?php
                $affilicart_category_slug = get_query_var( 'affilicart_category' );
                $affilicart_category = get_term_by( 'slug', $affilicart_category_slug, 'amazon_product_category' );
                
                if ( $affilicart_category ) {
                    echo esc_html( $affilicart_category->name ) . ' Products';
                } else {
                    echo 'Products';
                }
            ?>
        </h1>
        <?php
            if ( $affilicart_category && ! empty( $affilicart_category->description ) ) {
                echo '<p style="color: #666; font-size: 16px;">' . wp_kses_post( $affilicart_category->description ) . '</p>';
            }
        ?>
    </div>

    <!-- Product Grid -->
    <div class="ac-shop-wrapper">
        <div class="row">
            <?php
                $affilicart_category_slug = get_query_var( 'affilicart_category' );
                $affilicart_category = get_term_by( 'slug', $affilicart_category_slug, 'amazon_product_category' );
                $affilicart_category_id = $affilicart_category ? $affilicart_category->term_id : null;
                
                // Query products
                $affilicart_args = array(
                    'post_type' => 'amazon_product',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'orderby' => 'title',
                    'order' => 'ASC'
                );
                
                // Filter by category if we have one
                if ( $affilicart_category_id ) {
                    $affilicart_args['tax_query'] = array(
                        array(
                            'taxonomy' => 'amazon_product_category',
                            'field' => 'term_id',
                            'terms' => $affilicart_category_id
                        )
                    );
                }
                
                $affilicart_products_query = new WP_Query( $affilicart_args );
                $affilicart_associate_tag = get_option( 'affilicart_associate_id', 'default-20' );
                
                if ( $affilicart_products_query->have_posts() ) {
                    while ( $affilicart_products_query->have_posts() ) {
                        $affilicart_products_query->the_post();
                        $affilicart_product_id = get_the_ID();
                        $affilicart_product_slug = get_post_field( 'post_name', $affilicart_product_id );
                        $affilicart_product_url = get_permalink( $affilicart_product_id );
                        $affilicart_thumbnail_url = get_the_post_thumbnail_url( $affilicart_product_id, 'medium' );
                        $affilicart_product_title = get_the_title( $affilicart_product_id );
                        $affilicart_price = get_post_meta( $affilicart_product_id, '_affilicart_price', true );
                        $affilicart_description = wp_trim_words( get_post_meta( $affilicart_product_id, 'product_description', true ), 15 );
                        $affilicart_asin = get_post_meta( $affilicart_product_id, '_affilicart_asin', true );
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="ac-product-card">
                                <?php if ( $affilicart_thumbnail_url ) : ?>
                                    <a href="<?php echo esc_url( $affilicart_product_url ); ?>" style="text-decoration: none; color: inherit; display: block;">
                                        <img src="<?php echo esc_url( $affilicart_thumbnail_url ); ?>" alt="<?php echo esc_attr( $affilicart_product_title ); ?>" class="ac-product-image">
                                    </a>
                                <?php endif; ?>
                                
                                <h5 class="ac-card-title" style="margin: 15px 0 10px; font-size: 16px; font-weight: 600; color: #333;">
                                    <?php echo esc_html( $affilicart_product_title ); ?>
                                </h5>
                                
                                <?php if ( $affilicart_description ) : ?>
                                    <p class="ac-card-text" style="font-size: 14px; color: #666; margin-bottom: 12px; flex-grow: 1;">
                                        <?php echo esc_html( $affilicart_description ); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ( $affilicart_price ) : ?>
                                    <div class="ac-price" style="margin-bottom: 15px; font-size: 18px; font-weight: 600; color: var(--ac-accent-color, #007cba);">
                                        <span><?php echo strpos( $affilicart_price, '$' ) === 0 ? esc_html( $affilicart_price ) : '$' . esc_html( $affilicart_price ); ?></span>
                                        <span class="dashicons dashicons-info" style="font-size: 12px; color: #999; cursor: help; margin-left: 6px;"></span>
                                    </div>
                                <?php endif; ?>
                                
                                <button class="btn btn-primary w-100 ac-grid-btn" onclick="addToCart(<?php echo intval( $affilicart_product_id ); ?>, false)" style="background-color: var(--ac-accent-color, #007cba); border-color: var(--ac-accent-color, #007cba); margin-bottom: 8px;">
                                    Add to Cart
                                </button>
                                
                                <?php if ( $affilicart_asin ) : ?>
                                    <div style="text-align: center; margin-top: 8px;">
                                        <a href="<?php echo esc_url( 'https://www.amazon.com/dp/' . $affilicart_asin . '?tag=' . $affilicart_associate_tag ); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 12px; color: #666; text-decoration: none;">
                                            <span class="dashicons dashicons-external" style="display: inline; width: auto; height: auto; font-size: 14px;"></span> View on Amazon
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                    }
                    wp_reset_postdata();
                } else {
                    ?>
                    <div class="col-12 text-center text-muted" style="padding: 40px;">
                        <p style="font-size: 16px;">No products found in this category.</p>
                    </div>
                    <?php
                }
            ?>
        </div>
    </div>

</div>

<?php get_footer(); ?>

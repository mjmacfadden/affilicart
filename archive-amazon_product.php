<?php
/**
 * Archive Template for Amazon Products by Category
 * Displays products filtered by category
 */

get_header();
?>

<div class="ac-archive-wrapper" style="padding: 40px 20px; max-width: 1200px; margin: 0 auto;">
    
    <!-- Page Title -->
    <div style="margin-bottom: 40px;">
        <h1 style="font-size: 32px; margin-bottom: 10px; color: #333;">
            <?php
                $category_slug = get_query_var( 'affilicart_category' );
                $category = get_term_by( 'slug', $category_slug, 'amazon_product_category' );
                
                if ( $category ) {
                    echo esc_html( $category->name ) . ' Products';
                } else {
                    echo 'Products';
                }
            ?>
        </h1>
        <?php
            if ( $category && ! empty( $category->description ) ) {
                echo '<p style="color: #666; font-size: 16px;">' . wp_kses_post( $category->description ) . '</p>';
            }
        ?>
    </div>

    <!-- Product Grid -->
    <div class="ac-shop-wrapper">
        <div class="row">
            <?php
                $category_slug = get_query_var( 'affilicart_category' );
                $category = get_term_by( 'slug', $category_slug, 'amazon_product_category' );
                $category_id = $category ? $category->term_id : null;
                
                // Query products
                $args = array(
                    'post_type' => 'amazon_product',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'orderby' => 'title',
                    'order' => 'ASC'
                );
                
                // Filter by category if we have one
                if ( $category_id ) {
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'amazon_product_category',
                            'field' => 'term_id',
                            'terms' => $category_id
                        )
                    );
                }
                
                $products_query = new WP_Query( $args );
                $associate_tag = get_option( 'affilicart_associate_id', 'default-20' );
                
                if ( $products_query->have_posts() ) {
                    while ( $products_query->have_posts() ) {
                        $products_query->the_post();
                        $product_id = get_the_ID();
                        $product_slug = get_post_field( 'post_name', $product_id );
                        $product_url = get_permalink( $product_id );
                        $thumbnail_url = get_the_post_thumbnail_url( $product_id, 'medium' );
                        $product_title = get_the_title( $product_id );
                        $price = get_post_meta( $product_id, '_affilicart_price', true );
                        $description = wp_trim_words( get_post_meta( $product_id, 'product_description', true ), 15 );
                        $asin = get_post_meta( $product_id, '_affilicart_asin', true );
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="ac-product-card">
                                <?php if ( $thumbnail_url ) : ?>
                                    <a href="<?php echo esc_url( $product_url ); ?>" style="text-decoration: none; color: inherit; display: block;">
                                        <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" class="ac-product-image">
                                    </a>
                                <?php endif; ?>
                                
                                <h5 class="ac-card-title" style="margin: 15px 0 10px; font-size: 16px; font-weight: 600; color: #333;">
                                    <?php echo esc_html( $product_title ); ?>
                                </h5>
                                
                                <?php if ( $description ) : ?>
                                    <p class="ac-card-text" style="font-size: 14px; color: #666; margin-bottom: 12px; flex-grow: 1;">
                                        <?php echo esc_html( $description ); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ( $price ) : ?>
                                    <div class="ac-price" style="margin-bottom: 15px; font-size: 18px; font-weight: 600; color: var(--ac-accent-color, #007cba);">
                                        <span><?php echo strpos( $price, '$' ) === 0 ? esc_html( $price ) : '$' . esc_html( $price ); ?></span>
                                        <i class="bi bi-info-circle" style="font-size: 12px; color: #999; cursor: help; margin-left: 6px;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <button class="btn btn-primary w-100 ac-grid-btn" onclick="addToCart(<?php echo intval( $product_id ); ?>, false)" style="background-color: var(--ac-accent-color, #007cba); border-color: var(--ac-accent-color, #007cba); margin-bottom: 8px;">
                                    Add to Cart
                                </button>
                                
                                <?php if ( $asin ) : ?>
                                    <div style="text-align: center; margin-top: 8px;">
                                        <a href="<?php echo esc_url( 'https://www.amazon.com/dp/' . $asin . '?tag=' . $associate_tag ); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 12px; color: #666; text-decoration: none;">
                                            <i class="bi bi-box-arrow-up-right"></i> View on Amazon
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

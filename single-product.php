<?php
/**
 * Single Product Template for Affilicart
 */

get_header();

if (have_posts()) {
    the_post();
    $product_id = get_the_ID();
    $product_title = get_the_title();
    $product_image = get_the_post_thumbnail_url($product_id, 'large');
    $product_description = get_post_meta($product_id, 'product_description', true);
    $product_price = get_post_meta($product_id, '_affilicart_price', true);
    $product_asin = get_post_meta($product_id, '_affilicart_asin', true);
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
                <?php if ($product_image): ?>
                    <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($product_title); ?>" class="product-image" id="product-image-<?php echo esc_attr($product_id); ?>" style="cursor: pointer;">
                <?php else: ?>
                    <div style="width: 100%; aspect-ratio: 1; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">No Image</div>
                <?php endif; ?>
            </div>

            <!-- Product Details (Right) -->
            <div class="product-details">
                <h1 class="product-title"><?php echo esc_html($product_title); ?></h1>
                
                <?php if ($product_price): ?>
                    <div class="product-price">
                        <span><?php echo esc_html($product_price); ?></span> <i class="bi bi-info-circle" style="font-size: 12px; color: #999; cursor: help;"></i>
                    </div>
                    
                    <?php 
                    // Get product categories
                    $categories = get_the_terms($product_id, 'amazon_product_category');
                    if ($categories && !is_wp_error($categories)):
                    ?>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin: 40px 0;">
                            <?php foreach ($categories as $category): ?>
                                <a href="<?php echo esc_url(home_url('/product/category/' . $category->slug . '/')); ?>" style="display: inline-block; padding: 0 10px; background: #f0f0f0; border-radius: 16px; font-size: 11px; color: #333; text-decoration: none; border: 1px solid #ddd; transition: all 0.2s ease;">
                                    <?php echo esc_html($category->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($product_description): ?>
                    <div class="product-description">
                        <?php echo wp_kses_post(nl2br($product_description)); ?>
                    </div>
                <?php endif; ?>
                
            <div class="product-actions">
                    <div style="display: flex; flex-direction: column; align-items: center; width: 100%; max-width: 200px;">
                        <button class="btn-add-to-cart" onclick="addToCart(<?php echo esc_js($product_id); ?>, false); this.textContent = 'Added to Cart'; this.classList.add('added'); setTimeout(() => { this.textContent = 'Add to Cart'; this.classList.remove('added'); }, 2000);">
                            Add to Cart
                        </button>
                        <?php if ($product_asin): ?>
                            <div style="margin-top: 12px; text-align: center; width: 100%;">
                                <a href="<?php echo esc_url('https://www.amazon.com/dp/' . urlencode($product_asin) . '?tag=' . get_option('affilicart_associate_id', 'default-20')); ?>" target="_blank" rel="noopener noreferrer" style="font-size: 13px; color: #666; text-decoration: none; display: inline-block;">
                                    View on Amazon <i class="bi bi-box-arrow-up-right" style="font-size: 11px;"></i>
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

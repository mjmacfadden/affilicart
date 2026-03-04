# Affilicart Freemium System

This document explains how the Affilicart freemium model works.

## Overview

**Affilicart** is now a freemium plugin with two versions:

1. **Affilicart (Free)** - Limited to 3 products
2. **Affilicart Pro** - Unlimited products + premium features

## How It Works

### Free Version

The free version (`affilicart/affilicart.php`) includes:
- ✓ Product manager
- ✓ Shopping cart
- ✓ Product categories & tags
- ✓ Amazon affiliate links
- ✓ Image lightbox
- ✓ **Limited to 3 published products**

### What Happens When You Hit the Limit

When users try to publish a 4th product:
1. The plugin automatically reverts it to "Draft" status
2. A red error notice appears
3. The product count shows "4 / 3" in the products list
4. An upgrade prompt appears in the admin

### Pro Version

The Pro plugin (`affilicart-pro/affilicart-pro.php`) extends the free version by:
- Detecting the free Affilicart plugin
- Removing the 3-product limit via filter
- Allowing unlimited published products
- Showing a "Pro" badge in the admin
- Setting up for future premium features

**How users activate Pro:**
1. Purchase Affilicart Pro
2. Download the `affilicart-pro.zip` file
3. Install it as a separate plugin (alongside the free version)
4. Activate it
5. The product limit is automatically removed

## Technical Implementation

### Filter System

The product limit uses a filter that both plugins can override:

```php
// Free version defines the default limit
define( 'AFFILICART_PRODUCT_LIMIT', apply_filters( 'affilicart_product_limit', 3 ) );

// Pro version removes the limit
add_filter( 'affilicart_product_limit', function() {
    return PHP_INT_MAX; // Unlimited
});
```

### Detection

The free plugin checks if Pro is active:
```php
define( 'AFFILICART_PRO_ACTIVE', defined( 'AFFILICART_PRO_VERSION' ) );
```

When Pro is active:
- The "Upgrade to Pro" tab hides
- Product limit checks are bypassed
- Admin notices are adjusted

### Enforced Checks

The free plugin enforces the limit in three places:

1. **Save Post Hook** - When publishing a new product beyond the limit:
   ```php
   add_action( 'save_post_amazon_product', function( $post_id ) {
       if ( $published_count > AFFILICART_PRODUCT_LIMIT ) {
           wp_update_post( array( 'post_status' => 'draft' ) );
       }
   });
   ```

2. **Product List Display** - Shows the product count:
   ```php
   add_action( 'manage_posts_extra_tablenav', function() {
       echo "Products: $published_count / $limit";
   });
   ```

3. **Settings Page** - "Upgrade to Pro" tab appears only in free version

## Migration Path for Users

### User Starts with Free Version
1. Installs Affilicart (free)
2. Adds 1-3 products
3. Tests the plugin

### User Wants to Expand
1. Tries to add 4th product → Gets unpublished with notice
2. Clicks "Upgrade to Pro" link
3. Purchases Pro version
4. Installs `affilicart-pro` plugin
5. Limit automatically removed
6. Can now publish unlimited products

### User Previously Had More Than 3
If users previously had more than 3 products (before free version was released):
1. Products beyond the limit will be in "Draft" status
2. After installing Pro, they can republish them:
   - Go to Products → All Products
   - Filter by "Draft" status
   - Click Edit on each
   - Change status to "Publish"

## For Developers/Customizers

### Customizing the Product Limit

To change the free version limit from 3 to another number, modify:

```php
// In affilicart.php, line ~13
define( 'AFFILICART_PRODUCT_LIMIT', apply_filters( 'affilicart_product_limit', 5 ) ); // Changed to 5
```

### Adding Custom Checks

To add your own product limit logic:

```php
// In your custom plugin or theme functions.php
add_filter( 'affilicart_product_limit', function( $limit ) {
    // Custom logic here
    return custom_get_product_limit(); // Return your custom limit
});
```

## Monetization Notes

This freemium model provides:
1. **Free tier** - Gets users interested with limited functionality
2. **Clear upgrade path** - Users hit the limit and see upgrade prompts naturally
3. **Easy activation** - Pro is a simple plugin install, no license keys needed
4. **Scalable** - Future features (analytics, bulk import) can be Pro-only

## Installation Instructions

### For End Users

**Activate Free Version:**
1. Plugins → Add New
2. Search "Affilicart"
3. Click Install Now
4. Click Activate

**Upgrade to Pro (Optional):**
1. Purchase Pro license
2. Download affilicart-pro.zip
3. Plugins → Add New
4. Click Upload Plugin
5. Select affilicart-pro.zip
6. Click Install Now
7. Click Activate

### For Developers

Both plugins are in `/wp-content/plugins/`:
- `affilicart/` - Free version
- `affilicart-pro/` - Pro version

Pro detects and depends on the free version being active.

## Files Structure

```
wp-content/plugins/
├── affilicart/                 # Free version
│   ├── affilicart.php         # Main file (has product limit)
│   ├── scripts.js
│   ├── style.css
│   ├── single-product.php
│   ├── archive-amazon_product.php
│   └── ...other files
│
└── affilicart-pro/             # Pro extension
    ├── affilicart-pro.php      # Removes product limit
    └── README.md
```

## Support & Questions

- **Free version issues**: Support forum
- **Pro version**: Priority support email
- **Technical questions**: Developer documentation

---

**Last Updated:** March 3, 2026

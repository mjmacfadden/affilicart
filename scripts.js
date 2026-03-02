(function() {
    // 1. Get dynamic data from WordPress Settings
    const products = affilicart_data.products;
    const ASSOCIATE_TAG = affilicart_data.associate_tag;
    const ACCENT_COLOR = affilicart_data.accent_color;
    const LIGHTBOX_ENABLED = affilicart_data.lightbox_enabled;
    const CART_POSITION = affilicart_data.cart_position || 'bottom-right';
    let cart = JSON.parse(localStorage.getItem('ac_cart')) || [];

    // Set the CSS custom property for accent color
    document.documentElement.style.setProperty('--ac-accent-color', ACCENT_COLOR);

    // Style checkout button with accent color
    const style = document.createElement('style');
    style.textContent = `
        #checkout-button {
            background-color: var(--ac-accent-color, #007cba) !important;
            border-color: var(--ac-accent-color, #007cba) !important;
        }
        #checkout-button:hover {
            background-color: ${ACCENT_COLOR}dd !important;
            border-color: ${ACCENT_COLOR}dd !important;
        }
    `;
    document.head.appendChild(style);

    // Lightbox function for product images
    window.openImageLightbox = function(imageSrc, imageAlt) {
        // Create lightbox overlay
        const lightbox = document.createElement('div');
        lightbox.className = 'ac-lightbox';
        lightbox.innerHTML = `
            <div class="ac-lightbox-content">
                <button class="ac-lightbox-close" aria-label="Close lightbox"><i class="bi bi-x-circle"></i></button>
                <img src="${imageSrc}" alt="${imageAlt}" class="ac-lightbox-image">
            </div>
        `;
        lightbox.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            animation: fadeIn 0.3s ease-in-out;
        `;
        
        const lightboxContent = lightbox.querySelector('.ac-lightbox-content');
        lightboxContent.style.cssText = `
            position: relative;
            max-width: 85vw;
            max-height: 85vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
            box-sizing: border-box;
        `;
        
        const lightboxImage = lightbox.querySelector('.ac-lightbox-image');
        lightboxImage.style.cssText = `
            max-width: 100%;
            max-height: calc(100vh - 60px);
            object-fit: contain;
            border-radius: 4px;
        `;
        
        const closeBtn = lightbox.querySelector('.ac-lightbox-close');
        closeBtn.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            color: white;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
            line-height: 1;
            transition: opacity 0.2s;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000000;
            text-shadow: 0 0 6px rgba(0, 0, 0, 0.8);
        `;
        closeBtn.onmouseover = function() { this.style.opacity = '0.6'; };
        closeBtn.onmouseout = function() { this.style.opacity = '1'; };
        
        // Close lightbox
        function closeLightbox() {
            lightbox.style.animation = 'fadeOut 0.3s ease-in-out';
            setTimeout(() => lightbox.remove(), 300);
        }
        
        // Close on close button click
        closeBtn.addEventListener('click', closeLightbox);
        
        // Close on overlay click
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });
        
        // Close on ESC key
        const escapeHandler = function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
        
        document.body.appendChild(lightbox);
    };

    // 2. Render Products to the Grid
    function displayProducts() {
        const productList = document.getElementById('product-list');
        if (!productList) return;

        // Handle single-product or comma-separated IDs [affilicart_grid id="123"] or [affilicart_grid id="123,456,789"]
        const singleId = productList.getAttribute('data-single-id');
        const showImage = productList.getAttribute('data-show-image') !== 'no';
        const showTitle = productList.getAttribute('data-show-title') !== 'no';
        const showDescription = productList.getAttribute('data-show-description') !== 'no';
        const showPrice = productList.getAttribute('data-show-price') !== 'no';
        const showAmazonLink = productList.getAttribute('data-show-amazon-link') === 'yes';
        
        let displayArray = products;
        if (singleId && singleId !== "") {
            const ids = singleId.split(',').map(id => id.trim());
            // Preserve the order specified in the shortcode
            displayArray = ids.map(id => products.find(p => p.id.toString() === id)).filter(p => p);
        }

        if (displayArray.length === 0) {
            productList.innerHTML = '<div class="col-12 text-center text-muted">No products found.</div>';
            return;
        }

        productList.innerHTML = displayArray.map(product => `
            <div class="col-md-4 mb-4">
                <div class="ac-product-card">
                    ${showImage ? `<img src="${product.image || ''}" alt="${product.name}" class="ac-product-image" style="${LIGHTBOX_ENABLED ? 'cursor: pointer;' : ''}">` : ''}
                    ${showTitle ? `<h5 class="ac-card-title">${product.name}</h5>` : ''}
                    ${showDescription ? `<p class="ac-card-text">${product.description}</p>` : ''}
                    ${showPrice ? `<div class="ac-price"><span>${product.price.startsWith('$') ? '' : '$'}${product.price}</span> <i class="bi bi-info-circle" style="font-size: 12px; color: #999; cursor: help;"></i></div>` : ''}
                    <button class="btn btn-primary w-100 ac-grid-btn" onclick="addToCart(${product.id}, false)">
                        Add to Cart
                    </button>
                    ${showAmazonLink ? `<a href="https://www.amazon.com/dp/${product.asin.trim()}?tag=${ASSOCIATE_TAG}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary w-100 mt-2" style="font-size: 12px;"><i class="bi bi-box-arrow-up-right"></i> View on Amazon</a>` : ''}
                </div>
            </div>
        `).join('');
        
        // Add lightbox to product images (only if enabled)
        if (LIGHTBOX_ENABLED) {
            document.querySelectorAll('.ac-product-image').forEach(img => {
                img.addEventListener('click', function(e) {
                    e.stopPropagation();
                    // Get the full-size image from the product data
                    const productCard = this.closest('.ac-product-card');
                    const productName = productCard.querySelector('.ac-card-title')?.textContent || this.alt;
                    
                    // Find the product in the data to get the full-size image
                    const productImg = this.getAttribute('src');
                    const product = products.find(p => p.image === productImg);
                    const fullImageUrl = product?.image_full || productImg;
                    
                    openImageLightbox(fullImageUrl, productName);
                });
            });
        }
        
        // Add feedback handler to grid buttons
        document.querySelectorAll('.ac-grid-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const originalText = this.textContent;
                this.textContent = 'Added to Cart';
                this.disabled = true;
                
                setTimeout(() => {
                    this.textContent = originalText;
                    this.disabled = false;
                }, 2000);
            });
        });
    }

    // 2a. Render Single Button
    function displayButton() {
        const buttonContainer = document.getElementById('ac-button-container');
        if (!buttonContainer) return;

        const productId = buttonContainer.getAttribute('data-product-id');
        const product = products.find(p => p.id == productId);

        if (!product) {
            buttonContainer.innerHTML = '<div class="text-muted">Product not found.</div>';
            return;
        }

        buttonContainer.innerHTML = `<button class="btn btn-primary" onclick="addToCart(${product.id})" style="min-width: 120px; background-color: var(--ac-accent-color, #007cba); border-color: var(--ac-accent-color, #007cba);">Add to Cart</button>`;
    }

    // 2b. Render Hover Links
    function displayHoverLinks() {
        document.querySelectorAll('.ac-hover-link').forEach(link => {
            const productId = link.getAttribute('data-product-id');
            const customText = link.getAttribute('data-link-text');
            const showAmazonLink = link.getAttribute('data-show-amazon-link') === 'yes';
            const product = products.find(p => p.id == productId);

            if (!product) {
                link.innerHTML = '<span class="text-muted">Product not found</span>';
                return;
            }

            const linkText = customText || product.name;
            
            link.innerHTML = `<a href="#" class="ac-link" style="color: var(--ac-accent-color, #007cba); text-decoration: none; cursor: pointer;" onclick="event.preventDefault();">${linkText}</a>`;
            
            const linkElement = link.querySelector('.ac-link');
            
            linkElement.addEventListener('mouseenter', function(e) {
                const card = document.createElement('div');
                card.className = 'ac-hover-card';
                card.innerHTML = `
                    <div style="padding: 12px; width: 220px; text-align: center;">
                        ${product.image ? `<img src="${product.image}" alt="${product.name}" style="width: 100%; max-height: 150px; object-fit: contain; border-radius: 8px; margin-bottom: 10px; display: block;">` : ''}
                        <div style="font-weight: 600; margin-bottom: 8px; font-size: 14px;">${product.name}</div>
                        ${product.price ? `<div style="font-size: 13px; color: #666; margin-bottom: 10px;"><span>${product.price.startsWith('$') ? '' : '$'}${product.price}</span> <i class="bi bi-info-circle" style="font-size: 12px; color: #999; cursor: help;"></i></div>` : ''}
                        <button class="btn btn-primary btn-sm w-100" onclick="addToCart(${product.id}, false)" style="background-color: var(--ac-accent-color, #007cba); border-color: var(--ac-accent-color, #007cba); font-size: 12px; margin-bottom: ${showAmazonLink ? '6px' : '0'};">Add to Cart</button>
                        ${showAmazonLink ? `<a href="https://www.amazon.com/dp/${product.asin.trim()}?tag=${ASSOCIATE_TAG}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary btn-sm w-100" style="font-size: 11px; padding: 5px; color: #666; border-color: #ddd;"><i class="bi bi-box-arrow-up-right"></i> Amazon</a>` : ''}
                    </div>
                `;
                document.body.appendChild(card);
                
                // Position the card
                const rect = linkElement.getBoundingClientRect();
                const cardHeight = card.offsetHeight;
                card.style.position = 'fixed';
                card.style.top = (rect.top - cardHeight - 8) + 'px';
                card.style.left = rect.left + 'px';
                card.style.zIndex = '9999';
                
                // Add feedback to the button
                const cartBtn = card.querySelector('.btn-primary');
                if (cartBtn) {
                    cartBtn.addEventListener('click', function(e) {
                        const originalText = this.textContent;
                        this.textContent = 'Added to Cart';
                        this.disabled = true;
                        
                        setTimeout(() => {
                            this.textContent = originalText;
                            this.disabled = false;
                        }, 2000);
                    });
                }
                
                // Store reference to card on link
                linkElement.hoverCard = card;
                
                // Keep card visible on hover
                card.addEventListener('mouseenter', function() {
                    card.style.display = 'block';
                });
                card.addEventListener('mouseleave', function() {
                    card.remove();
                    linkElement.hoverCard = null;
                });
            });
            
            linkElement.addEventListener('mouseleave', function() {
                if (linkElement.hoverCard) {
                    setTimeout(() => {
                        if (linkElement.hoverCard && !linkElement.hoverCard.matches(':hover')) {
                            linkElement.hoverCard.remove();
                            linkElement.hoverCard = null;
                        }
                    }, 100);
                }
            });
        });
    }

    // 3. Add to Cart
    window.addToCart = function(productId, showModal = true) {
        const product = products.find(p => p.id == productId);
        const cartItem = cart.find(item => item.id == productId);

        if (cartItem) {
            cartItem.quantity++;
        } else {
            cart.push({ ...product, quantity: 1 });
        }
        updateCart();
        
        // Function to shake cart elements
        function shakeCartElements() {
            // For Divi top cart, shake the whole container
            const topCart = document.querySelector('#ac-top-cart');
            if (topCart) {
                topCart.classList.add('ac-shake');
                setTimeout(() => topCart.classList.remove('ac-shake'), 800);
            }
            
            // For floating cart, shake the icon inside
            const floatingCartIcon = document.querySelector('#ac-floating-cart i');
            if (floatingCartIcon) {
                floatingCartIcon.classList.add('ac-shake');
                setTimeout(() => floatingCartIcon.classList.remove('ac-shake'), 500);
            }
            
            // For menu carts, shake the icon
            const menuCart = document.querySelector('.ac-menu-cart .bi-cart');
            if (menuCart) {
                menuCart.classList.add('ac-shake');
                setTimeout(() => menuCart.classList.remove('ac-shake'), 500);
            }
        }
        
        // Shake immediately and with delays for carts that might be injected later
        shakeCartElements();
        setTimeout(shakeCartElements, 100);
        setTimeout(shakeCartElements, 200);
        
        // Open modal automatically when item is added (unless disabled)
        if (showModal) {
            const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
            cartModal.show();
        }
    };

    // 4. Remove from Cart
    window.removeFromCart = function(productId) {
        cart = cart.filter(item => item.id != productId);
        updateCart();
    };

    // 4a. Increase Quantity
    window.increaseQuantity = function(productId) {
        const cartItem = cart.find(item => item.id == productId);
        if (cartItem) {
            cartItem.quantity++;
            updateCart();
        }
    };

    // 4b. Decrease Quantity
    window.decreaseQuantity = function(productId) {
        const cartItem = cart.find(item => item.id == productId);
        if (cartItem) {
            if (cartItem.quantity > 1) {
                cartItem.quantity--;
            } else {
                removeFromCart(productId);
                return;
            }
            updateCart();
        }
    };

    // 5. Update UI and Storage
    function updateCart() {
        localStorage.setItem('ac_cart', JSON.stringify(cart));
        const totalQuantity = cart.reduce((sum, item) => sum + item.quantity, 0);
        
        // Update all cart count elements (menu cart and/or Divi cart)
        document.querySelectorAll('#cart-count').forEach(el => {
            el.innerText = totalQuantity;
        });
        
        displayCartItems();
    }

    // 6. Display Items inside the Modal
    function displayCartItems() {
        const cartItemsList = document.getElementById('cart-items');
        const totalElement = document.getElementById('grand-total');
        const alertDiv = document.getElementById('cart-empty-alert');
        if (!cartItemsList) return;

        // Hide alert when displaying cart
        if (alertDiv) {
            alertDiv.style.display = 'none';
        }

        let grandTotal = 0;
        cartItemsList.innerHTML = cart.map(item => {
            const priceNum = parseFloat(item.price.replace(/[$,]/g, '')) || 0;
            const subtotal = priceNum * item.quantity;
            grandTotal += subtotal;
            return `
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        ${item.image ? `<img src="${item.image}" alt="${item.name}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">` : ''}
                        <div>
                            <div class="fw-bold">${item.name}</div>
                            <div class="ac-quantity-controls" style="display: flex; align-items: center; gap: 6px;">
                                <button class="btn btn-sm btn-outline-secondary" onclick="decreaseQuantity(${item.id})" style="width: 24px; height: 24px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 12px;">−</button>
                                <span class="ac-qty-display" style="min-width: 32px; text-align: center; font-weight: 500; font-size: 14px;">${item.quantity}</span>
                                <button class="btn btn-sm btn-outline-secondary" onclick="increaseQuantity(${item.id})" style="width: 24px; height: 24px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 12px;">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">$${subtotal.toFixed(2)}</div>
                        <i class="bi bi-x-circle ac-remove-item" onclick="removeFromCart(${item.id})"></i>
                    </div>
                </li>
            `;
        }).join('');

        if (cart.length === 0) cartItemsList.innerHTML = '<li class="text-center py-3 text-muted">Cart is empty</li>';
        if (totalElement) totalElement.innerText = `Total: $${grandTotal.toFixed(2)}`;
    }

    // 7. Price Info Tooltips
    function initPriceTooltips() {
        document.querySelectorAll('.bi-info-circle:not([data-tooltip-init])').forEach(icon => {
            icon.setAttribute('data-tooltip-init', 'true');
            
            icon.addEventListener('mouseenter', function(e) {
                // Remove any existing tooltip
                if (this.tooltipElement) {
                    this.tooltipElement.remove();
                }
                
                const tooltip = document.createElement('div');
                tooltip.className = 'ac-price-tooltip';
                tooltip.textContent = 'Price is accurate as of the time of adding to the site and may differ at checkout';
                tooltip.style.cssText = `
                    position: fixed;
                    background: #333;
                    color: white;
                    padding: 8px 12px;
                    border-radius: 4px;
                    font-size: 12px;
                    line-height: 1.2;
                    white-space: normal;
                    max-width: 200px;
                    z-index: 10000;
                    pointer-events: none;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                `;
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                const tooltipHeight = tooltip.offsetHeight;
                const tooltipWidth = tooltip.offsetWidth;
                
                // Position tooltip above the icon (always)
                let top = rect.top - tooltipHeight - 8;
                let left = rect.left - tooltipWidth / 2 + rect.width / 2;
                
                // Check if tooltip goes off-screen on the left
                if (left < 10) {
                    left = 10;
                }
                
                // Check if tooltip goes off-screen on the right
                const viewportWidth = window.innerWidth;
                if (left + tooltipWidth + 10 > viewportWidth) {
                    left = viewportWidth - tooltipWidth - 10;
                }
                
                tooltip.style.top = top + 'px';
                tooltip.style.left = left + 'px';
                
                this.tooltipElement = tooltip;
            });
            
            icon.addEventListener('mouseleave', function() {
                setTimeout(() => {
                    if (this.tooltipElement) {
                        this.tooltipElement.remove();
                        this.tooltipElement = null;
                    }
                }, 100);
            });
        });
    }

    // 8. Initialization & Checkout Logic
    document.addEventListener('DOMContentLoaded', () => {
        displayProducts();
        displayButton();
        displayHoverLinks();
        initPriceTooltips();
        updateCart();

        // Determine cart display type
        const isDivi = affilicart_data.is_divi;
        const cartDisplay = affilicart_data.cart_display;
        
        // Resolve 'auto' to actual display type
        let displayType = cartDisplay;
        if (displayType === 'auto') {
            displayType = isDivi ? 'menu' : 'floating';
        }
        
        // Inject floating cart ONLY if display type is explicitly set to 'floating'
        // Don't inject anything if 'menu' - the PHP filter handles menu injection
        if (displayType === 'floating') {
            if (!document.getElementById('ac-floating-cart')) {
                const floatingCartHtml = '<div id="ac-floating-cart"><a href="#" data-bs-toggle="modal" data-bs-target="#cartModal" title="Shopping Cart"><i class="bi bi-cart"></i> <span id="cart-count">0</span></a></div>';
                document.body.insertAdjacentHTML('beforeend', floatingCartHtml);
                
                // Position the floating cart based on setting
                const floatingCart = document.getElementById('ac-floating-cart');
                let positionStyles = 'position: fixed; z-index: 9999; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;';
                
                switch (CART_POSITION) {
                    case 'top-left':
                        positionStyles += ' top: 20px; left: 20px;';
                        break;
                    case 'top-right':
                        positionStyles += ' top: 20px; right: 20px;';
                        break;
                    case 'bottom-left':
                        positionStyles += ' bottom: 20px; left: 20px;';
                        break;
                    case 'bottom-right':
                    default:
                        positionStyles += ' bottom: 20px; right: 20px;';
                }
                
                floatingCart.setAttribute('style', positionStyles);
                
                // Update the cart count after injecting
                const cartData = JSON.parse(localStorage.getItem('ac_cart')) || [];
                const totalQuantity = cartData.reduce((sum, item) => sum + item.quantity, 0);
                const countElement = document.getElementById('cart-count');
                if (countElement) {
                    countElement.innerText = totalQuantity;
                }
            }
        }

        // Sync search input styling with header for Divi theme customizer changes
        if (window.affilicart_is_divi) {
            function syncSearchInputStyles() {
                const headerEl = document.getElementById('main-header');
                const searchInputs = document.querySelectorAll('.et_search_form_container input');
                
                if (headerEl && searchInputs.length > 0) {
                    const headerStyles = window.getComputedStyle(headerEl);
                    const bgColor = headerStyles.backgroundColor;
                    const textColor = headerStyles.color;
                    
                    searchInputs.forEach(input => {
                        if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)') {
                            input.style.setProperty('background-color', bgColor, 'important');
                        }
                        if (textColor) {
                            input.style.setProperty('color', textColor, 'important');
                        }
                    });
                }
            }
            
            // Sync on load and after a short delay for Divi to finish rendering
            syncSearchInputStyles();
            setTimeout(syncSearchInputStyles, 500);
            
            // Watch for style changes (theme customizer updates)
            const headerEl = document.getElementById('main-header');
            if (headerEl) {
                const styleObserver = new MutationObserver(syncSearchInputStyles);
                styleObserver.observe(headerEl, { attributes: true, attributeFilter: ['style', 'class'] });
            }
        }

        const checkoutBtn = document.getElementById('checkout-button');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', () => {
                if (cart.length === 0) {
                    const alertDiv = document.getElementById('cart-empty-alert');
                    if (alertDiv) {
                        alertDiv.style.display = 'block';
                        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    return;
                }
                let url = 'https://www.amazon.com/gp/aws/cart/add.html?';
                cart.forEach((item, i) => {
                    url += `ASIN.${i + 1}=${item.asin.trim()}&Quantity.${i + 1}=${item.quantity}&`;
                });
                url += `AssociateTag=${ASSOCIATE_TAG}`;
                window.open(url, '_blank');
            });
        }
    });

    // Re-initialize tooltips whenever DOM changes
    const observer = new MutationObserver(() => {
        initPriceTooltips();
    });
    
    document.addEventListener('DOMContentLoaded', () => {
        observer.observe(document.body, { childList: true, subtree: true });
    });
})();
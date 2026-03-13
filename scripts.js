(function() {
    // Prevent browser scroll restoration
    if ('scrollRestoration' in window.history) {
        window.history.scrollRestoration = 'manual';
    }
    
    // 1. Get dynamic data from WordPress Settings
    const products = affilicart_data.products;
    const ASSOCIATE_TAG = affilicart_data.associate_tag;
    const ACCENT_COLOR = affilicart_data.accent_color;
    const LIGHTBOX_ENABLED = affilicart_data.is_pro && affilicart_data.lightbox_enabled;
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
                <button class="ac-lightbox-close" aria-label="Close lightbox"><span class="dashicons dashicons-no"></span></button>
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

    // 2. Attach click handlers to grid buttons
    function initGridButtons() {
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
                
                // Create image HTML - make it clickable for pro users
                let imageHTML = '';
                if (product.image) {
                    const imgElement = `<img src="${product.image}" alt="${product.name}" style="width: 100%; max-height: 150px; object-fit: contain; border-radius: 8px; margin-bottom: 10px; display: block;">`;
                    if (affilicart_data.is_pro) {
                        const productSlug = affilicart_data.product_slug || 'product';
                        imageHTML = `<a href="/${productSlug}/${product.slug}/" style="text-decoration: none; color: inherit; display: block;">${imgElement}</a>`;
                    } else {
                        imageHTML = imgElement;
                    }
                }
                
                card.innerHTML = `
                    <div style="padding: 12px; width: 220px; text-align: center;">
                        ${imageHTML}
                        <div style="font-weight: 600; margin-bottom: 8px; font-size: 14px;">${product.name}</div>
                        <button class="btn btn-primary btn-sm w-100" onclick="addToCart(${product.id}, false)" style="background-color: var(--ac-accent-color, #007cba); border-color: var(--ac-accent-color, #007cba); font-size: 12px; margin-bottom: 8px;">Add to Cart</button>
                        <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #eee;">
                            <a href="https://www.amazon.com/dp/${product.asin.trim()}?tag=${ASSOCIATE_TAG}" target="_blank" rel="noopener noreferrer" style="font-size: 11px; color: #666; text-decoration: none;">View Price on Amazon <span class="dashicons dashicons-external" style="display: inline-block; width: auto; height: auto; font-size: 11px; line-height: 1; vertical-align: middle;"></span></a>
                            <p style="font-size: 10px; color: #999; margin: 6px 0 0 0; line-height: 1.2;">As an Amazon Associate I earn from qualifying purchases.</p>
                        </div>
                    </div>
                `;
                document.body.appendChild(card);
                
                // Position the card
                const rect = linkElement.getBoundingClientRect();
                const cardHeight = card.offsetHeight;
                card.style.position = 'fixed';
                card.style.top = (rect.top - (cardHeight - rect.height) / 2) + 'px';
                card.style.left = rect.left + 'px';
                card.style.zIndex = '99999';
                
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

    // 2. Modal Functions (vanilla JS, no Bootstrap)
    window.showCartModal = function() {
        const cartModal = document.getElementById('cartModal');
        if (cartModal) {
            cartModal.style.display = 'flex';
            cartModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeCartModal = function() {
        const cartModal = document.getElementById('cartModal');
        if (cartModal) {
            cartModal.style.display = 'none';
            cartModal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
    };

    // Close modal when clicking outside the dialog
    document.addEventListener('click', function(event) {
        const cartModal = document.getElementById('cartModal');
        if (event.target === cartModal) {
            window.closeCartModal();
        }
    });

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
            const menuCart = document.querySelector('.ac-menu-cart .dashicons-cart');
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
            window.showCartModal();
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

        const apiEnabled = affilicart_data.api_enabled || false;
        let grandTotal = 0;
        cartItemsList.innerHTML = cart.map(item => {
            let priceContent = '';
            if (apiEnabled && item.price) {
                const priceNum = parseFloat(item.price.replace(/[$,]/g, '')) || 0;
                const subtotal = priceNum * item.quantity;
                grandTotal += subtotal;
                priceContent = `<div class="fw-bold">$${subtotal.toFixed(2)}</div>`;
            }
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
                        ${priceContent}
                        <span class="dashicons dashicons-no ac-remove-item" onclick="removeFromCart(${item.id})" style="cursor: pointer;"></span>
                    </div>
                </li>
            `;
        }).join('');

        if (cart.length === 0) cartItemsList.innerHTML = '<li class="text-center py-3 text-muted">Cart is empty</li>';
        if (totalElement && apiEnabled) totalElement.innerText = `Total: $${grandTotal.toFixed(2)}`;
    }

    // 7. Price Info Tooltips
    function initPriceTooltips() {
        document.querySelectorAll('.dashicons-info:not([data-tooltip-init])').forEach(icon => {
            icon.setAttribute('data-tooltip-init', 'true');
            
            icon.addEventListener('mouseenter', function(e) {
                // Remove any existing tooltip
                if (this.tooltipElement) {
                    this.tooltipElement.remove();
                }
                
                const tooltip = document.createElement('div');
                tooltip.className = 'ac-price-tooltip';
                const priceDate = this.getAttribute('data-price-date') || 'Unknown date';
                tooltip.textContent = 'Price updated: ' + priceDate;
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
                    z-index: 999999;
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
    function initAffiliCart() {
        // Scroll to top on page load
        window.scrollTo(0, 0);
        
        initGridButtons();
        displayButton();
        displayHoverLinks();
        initPriceTooltips();
        updateCart();

        // Determine cart position and inject floating cart if needed
        const isDivi = affilicart_data.is_divi;
        const cartPosition = affilicart_data.cart_position || (isDivi ? 'divi-menu' : 'bottom-right');
        
        // Define valid floating positions
        const floatingPositions = ['top-left', 'top-right', 'bottom-left', 'bottom-right'];
        
        // Inject floating cart ONLY if position is a floating position
        // Don't inject anything if 'divi-menu' - the PHP footer action handles Divi menu injection
        if (floatingPositions.includes(cartPosition)) {
            if (!document.getElementById('ac-floating-cart')) {
                const floatingCartHtml = '<div id="ac-floating-cart"><a href="#" onclick="showCartModal(); return false;" title="Shopping Cart"><span class="dashicons dashicons-cart"></span> <span id="cart-count">0</span></a></div>';
                document.body.insertAdjacentHTML('beforeend', floatingCartHtml);
                
                // Position the floating cart based on setting
                const floatingCart = document.getElementById('ac-floating-cart');
                let positionStyles = 'position: fixed; z-index: 999999;';
                
                // Check if WordPress admin bar is present
                const adminBar = document.getElementById('wpadminbar');
                const adminBarHeight = adminBar ? 32 : 0;
                
                switch (cartPosition) {
                    case 'top-left':
                        positionStyles += ` top: ${20 + adminBarHeight}px; left: 20px;`;
                        break;
                    case 'top-right':
                        positionStyles += ` top: ${20 + adminBarHeight}px; right: 20px;`;
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
                checkoutOnAmazon(url);
            });
        }
    }
    
    // Deep link handler for Amazon shopping cart
    window.checkoutOnAmazon = function(cartUrl) {
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

        if (isMobile) {
            // Strategy: Attempt to trigger the Amazon app-specific protocol.
            // If the app opens, the timer pauses. If the timer finishes, we redirect to browser.
            const start = Date.now();
            
            // Attempt to trigger the Amazon app-specific protocol
            window.location.href = cartUrl.replace('https://', 'amazon://');

            setTimeout(() => {
                // If we're still on this page after 500ms, the app didn't open.
                if (Date.now() - start < 1000) {
                    window.location.href = cartUrl; // Fallback to standard browser cart
                }
            }, 500);
        } else {
            // Desktop just opens in a new tab
            window.open(cartUrl, '_blank');
        }
    };
    
    // Run on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', initAffiliCart);
    
    // Also run on load event to catch cases where DOMContentLoaded already fired
    window.addEventListener('load', () => {
        window.scrollTo(0, 0);
    });

    // Re-initialize tooltips whenever DOM changes
    const observer = new MutationObserver(() => {
        initPriceTooltips();
    });
    
    document.addEventListener('DOMContentLoaded', () => {
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Add lightbox to single product image if on single product page
        const singleProductContainer = document.getElementById('ac-single-product');
        if (singleProductContainer && LIGHTBOX_ENABLED) {
            const productImg = singleProductContainer.querySelector('.product-image');
            if (productImg) {
                productImg.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const productName = singleProductContainer.querySelector('.product-title')?.textContent || productImg.alt;
                    openImageLightbox(productImg.getAttribute('src'), productName);
                });
            }
        }
    });
})();
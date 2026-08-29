/**
 * KANDY CO. - Frontend Interactivity & UI Logic
 */

document.addEventListener('DOMContentLoaded', () => {

    /* 1. Mobile Menu Drawer Toggle */
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileDrawer = document.getElementById('mobileDrawer');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const mobileDrawerClose = document.getElementById('mobileDrawerClose');

    function openMobileDrawer() {
        if (mobileDrawer && mobileOverlay) {
            mobileDrawer.classList.add('active');
            mobileOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMobileDrawer() {
        if (mobileDrawer && mobileOverlay) {
            mobileDrawer.classList.remove('active');
            mobileOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openMobileDrawer);
    if (mobileDrawerClose) mobileDrawerClose.addEventListener('click', closeMobileDrawer);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobileDrawer);


    /* 2. Full-Screen Search Modal */
    const searchOpenBtn = document.getElementById('searchOpenBtn');
    const searchCloseBtn = document.getElementById('searchCloseBtn');
    const searchOverlay = document.getElementById('searchOverlay');
    const searchInput = document.getElementById('searchInput');

    function openSearchModal() {
        if (searchOverlay) {
            searchOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            if (searchInput) setTimeout(() => searchInput.focus(), 150);
        }
    }

    function closeSearchModal() {
        if (searchOverlay) {
            searchOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (searchOpenBtn) searchOpenBtn.addEventListener('click', openSearchModal);
    if (searchCloseBtn) searchCloseBtn.addEventListener('click', closeSearchModal);

    // ESC key closes search modal or mobile menu
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSearchModal();
            closeMobileDrawer();
            closeSizeGuideModal();
        }
    });


    /* 3. Product Gallery Switcher */
    const mainGalleryImg = document.getElementById('mainGalleryImg');
    const thumbnails = document.querySelectorAll('.thumb-item');

    if (mainGalleryImg && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const newSrc = this.getAttribute('data-img');
                if (newSrc) {
                    mainGalleryImg.src = newSrc;
                    thumbnails.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                }
            });
        });
    }


    /* 4. Variant Selection (Size-Based) */
    const sizeBtns = document.querySelectorAll('.size-btn');
    const selectedSizeInput = document.getElementById('selectedSize');
    const selectedVariantIdInput = document.getElementById('selectedVariantId');
    const stockStatusElem = document.getElementById('stockStatusElem');

    if (sizeBtns.length > 0) {
        sizeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.classList.contains('disabled')) return;
                sizeBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                if (selectedSizeInput) {
                    selectedSizeInput.value = this.getAttribute('data-size');
                }
                updateVariantMatch();
            });
        });
    }

    function updateVariantMatch() {
        if (!selectedSizeInput) return;
        const size = selectedSizeInput.value;

        // Check window.productVariants dataset if injected by PHP
        if (window.productVariants && Array.isArray(window.productVariants)) {
            const matched = window.productVariants.find(v => v.size.toLowerCase() === size.toLowerCase());
            if (matched) {
                if (selectedVariantIdInput) selectedVariantIdInput.value = matched.id;
                if (stockStatusElem) {
                    if (matched.stock_quantity > 0) {
                        stockStatusElem.innerHTML = `<span class="stock-indicator"></span> IN STOCK (${matched.stock_quantity} AVAILABLE)`;
                    } else {
                        stockStatusElem.innerHTML = `<span class="stock-indicator low"></span> OUT OF STOCK`;
                    }
                }
            } else {
                if (stockStatusElem) {
                    stockStatusElem.innerHTML = `<span class="stock-indicator low"></span> SIZE UNAVAILABLE`;
                }
            }
        }
    }


    /* 5. Quantity Stepper */
    const qtyMinusBtn = document.getElementById('qtyMinus');
    const qtyPlusBtn = document.getElementById('qtyPlus');
    const qtyInput = document.getElementById('qtyInput');

    if (qtyMinusBtn && qtyPlusBtn && qtyInput) {
        qtyMinusBtn.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            if (val > 1) qtyInput.value = val - 1;
        });

        qtyPlusBtn.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            qtyInput.value = val + 1;
        });
    }


    /* 6. Product Information Accordion */
    const accordionTitles = document.querySelectorAll('.accordion-title');
    accordionTitles.forEach(title => {
        title.addEventListener('click', function() {
            const item = this.parentElement;
            item.classList.toggle('active');
        });
    });


    /* 7. Size Guide Modal */
    const sizeGuideTrigger = document.getElementById('sizeGuideTrigger');
    const sizeGuideModal = document.getElementById('sizeGuideModal');
    const sizeGuideClose = document.getElementById('sizeGuideClose');

    function openSizeGuideModal() {
        if (sizeGuideModal) {
            sizeGuideModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSizeGuideModal() {
        if (sizeGuideModal) {
            sizeGuideModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (sizeGuideTrigger) sizeGuideTrigger.addEventListener('click', openSizeGuideModal);
    if (sizeGuideClose) sizeGuideClose.addEventListener('click', closeSizeGuideModal);
    if (sizeGuideModal) {
        sizeGuideModal.addEventListener('click', (e) => {
            if (e.target === sizeGuideModal) closeSizeGuideModal();
        });
    }

});

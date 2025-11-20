document.addEventListener('DOMContentLoaded', () => {

    const productGrid = document.getElementById('product-grid');
    const categoryFilter = document.getElementById('category-filter');
    const loadingSpinner = document.getElementById('loading-spinner');
    const noProductsMessage = document.getElementById('no-products-message');
    const categorySlider = document.getElementById('category-slider');
    const bannerSlider = document.getElementById('banner-slider');
    
    let allProducts = []; // To store the master list of products

    /**
     * Fetches categories and populates both the filter dropdown and the top slider
     */
    async function fetchCategories() {
        try {
            const response = await fetch('api/categories/read.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const categories = await response.json();
            
            // Clear loading placeholder
            if (categorySlider) categorySlider.innerHTML = '';

            if (categories && categories.length > 0) {
                categories.forEach(category => {
                    // 1. Populate Dropdown
                    const option = document.createElement('option');
                    option.value = category.category_id;
                    option.textContent = category.category_name;
                    categoryFilter.appendChild(option);

                    // 2. Populate Slider
                    if (categorySlider) {
                        const slide = document.createElement('div');
                        slide.className = 'flex-shrink-0 w-40 h-24 bg-gradient-to-br from-blue-500 to-blue-700 rounded-lg flex items-center justify-center text-white font-bold text-center p-2 shadow-md cursor-pointer hover:from-blue-600 hover:to-blue-800 transition transform hover:-translate-y-1 select-none';
                        slide.textContent = category.category_name;
                        
                        // Click to filter
                        slide.addEventListener('click', () => {
                            categoryFilter.value = category.category_id;
                            filterProducts();
                            // Scroll catalog into view
                            document.getElementById('catalog').scrollIntoView({ behavior: 'smooth' });
                        });
                        
                        categorySlider.appendChild(slide);
                    }
                });
            }
        } catch (error) {
            console.error("Error fetching categories:", error);
            if(categorySlider) categorySlider.innerHTML = '<p class="text-red-400 w-full text-center">Error loading.</p>';
        }
    }

    /**
     * Fetches banner images for the bottom slider
     */
    async function fetchBanners() {
        if (!bannerSlider) return;
        
        try {
            const response = await fetch('api/public/get_banners.php');
            const images = await response.json();

            if (images && images.length > 0) {
                images.forEach(src => {
                    const img = document.createElement('img');
                    // Assuming square images as requested
                    img.className = 'h-32 w-32 object-cover rounded-lg shadow-md flex-shrink-0 bg-white';
                    img.src = src;
                    img.alt = 'Partner Banner';
                    bannerSlider.appendChild(img);
                });
            } else {
                bannerSlider.innerHTML = '<p class="text-gray-400 text-center w-full">No banners available.</p>';
            }
        } catch (error) {
            console.error("Error fetching banners:", error);
        }
    }

    /**
     * Fetches all products from the API
     */
    async function fetchProducts() {
        showLoading(true);
        try {
            const response = await fetch('api/products/read.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            allProducts = await response.json();
            
            if (allProducts && allProducts.length > 0) {
                displayProducts(allProducts);
            } else {
                showNoProducts(true);
            }
        } catch (error) {
            console.error("Error fetching products:", error);
            productGrid.innerHTML = `<p class="text-red-500 text-center col-span-full">Error loading products. Please try again later.</p>`;
            showNoProducts(true);
        } finally {
            showLoading(false);
        }
    }

    /**
     * Displays products in the grid
     * @param {Array} products - An array of product objects to display
     */
    function displayProducts(products) {
        productGrid.innerHTML = ''; // Clear existing products
        
        if (products.length === 0) {
            showNoProducts(true);
            return;
        }

        showNoProducts(false);
        
        products.forEach(product => {
            const productCard = document.createElement('div');
            productCard.className = 'bg-white rounded-lg shadow-md overflow-hidden transition-transform transform hover:-translate-y-1 border border-gray-100';
            
            const imageUrl = product.image ? 
                product.image : 
                `https://placehold.co/600x400/eeeeee/cccccc?text=${encodeURIComponent(product.name)}`;
                
            // Handle price formatting with "Rs:"
            const displayPrice = (product.price && parseFloat(product.price) > 0) ? 
                `Rs: ${parseFloat(product.price).toFixed(2)}` : 
                'Price on request';

            const categoryName = product.category_name || 'Uncategorized';
            const description = product.description ? product.description : 'No description available.';

            productCard.innerHTML = `
                <div class="relative">
                    <img src="${imageUrl}" alt="${product.name}" class="w-full h-48 object-cover" onerror="this.src='https://placehold.co/600x400/eeeeee/cccccc?text=Image+Not+Found'">
                </div>
                <div class="p-4">
                    <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full mb-2">${categoryName}</span>
                    <h3 class="text-lg font-bold text-gray-900 mb-1 truncate" title="${product.name}">${product.name}</h3>
                    <p class="text-sm text-gray-500 mb-4 h-10 overflow-hidden line-clamp-2" title="${description}">${description}</p>
                    <div class="flex justify-between items-center border-t pt-3">
                        <div class="text-xl font-bold text-blue-600">
                            ${displayPrice}
                        </div>
                    </div>
                </div>
            `;
            productGrid.appendChild(productCard);
        });
    }

    /**
     * Filters products based on the selected category
     */
    function filterProducts() {
        const selectedCategoryId = categoryFilter.value;
        
        if (selectedCategoryId === 'all') {
            displayProducts(allProducts);
        } else {
            const filteredProducts = allProducts.filter(product => 
                product.category_id == selectedCategoryId
            );
            displayProducts(filteredProducts);
        }
    }

    function showLoading(isLoading) {
        if (isLoading) {
            loadingSpinner.classList.remove('hidden');
            loadingSpinner.classList.add('flex');
            productGrid.classList.add('hidden');
        } else {
            loadingSpinner.classList.add('hidden');
            loadingSpinner.classList.remove('flex');
            productGrid.classList.remove('hidden');
        }
    }
    
    function showNoProducts(show) {
        if (show) {
            noProductsMessage.classList.remove('hidden');
        } else {
            noProductsMessage.classList.add('hidden');
        }
    }

    // --- Event Listeners ---
    categoryFilter.addEventListener('change', filterProducts);

    // --- Initial Load ---
    async function init() {
        await fetchCategories(); 
        await fetchProducts();   
        await fetchBanners();
    }
    
    init();
});
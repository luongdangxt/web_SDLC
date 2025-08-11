// Image Handler - Xử lý tất cả các vấn đề về ảnh
(function() {
    'use strict';
    
    console.log('🖼️ Image handler initializing...');
    
    // Tạo ảnh placeholder
    const createPlaceholderSVG = (width = 200, height = 200, text = 'No Image') => {
        return `data:image/svg+xml;base64,${btoa(`
            <svg width="${width}" height="${height}" xmlns="http://www.w3.org/2000/svg">
                <rect width="100%" height="100%" fill="#f0f0f0" stroke="#ddd" stroke-width="2"/>
                <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="14" 
                      fill="#999" text-anchor="middle" dy=".3em">${text}</text>
            </svg>
        `)}`;
    };
    
    // Xử lý ảnh lỗi
    function handleImageError(img, retryCount = 0) {
        if (retryCount < 2) {
            // Thử lại với đường dẫn khác
            const originalSrc = img.getAttribute('data-original-src') || img.src;
            img.setAttribute('data-original-src', originalSrc);
            
            // Thử các đường dẫn khác nhau
            const possiblePaths = [
                originalSrc,
                originalSrc.replace(/^\/+/, ''), // Bỏ slash đầu
                `./${originalSrc.replace(/^\/+/, '')}`, // Thêm ./
                `../${originalSrc.replace(/^\/+/, '')}`, // Thêm ../
                originalSrc.replace('uploads/', './uploads/'), // Thêm ./
                originalSrc.replace('uploads/', '../uploads/') // Thêm ../
            ];
            
            const nextPath = possiblePaths[retryCount + 1];
            if (nextPath && nextPath !== img.src) {
                console.log(`Retrying image load (${retryCount + 1}):`, nextPath);
                img.src = nextPath;
                return;
            }
        }
        
        // Nếu thử hết rồi vẫn lỗi, dùng placeholder
        console.warn('Image failed to load after retries:', img.src);
        const width = img.width || img.offsetWidth || 200;
        const height = img.height || img.offsetHeight || 200;
        img.src = createPlaceholderSVG(width, height, 'Image not found');
        img.alt = 'Image not found';
        img.classList.add('image-error');
    }
    
    // Xử lý ảnh thành công
    function handleImageSuccess(img) {
        console.log('✅ Image loaded successfully:', img.src);
        img.classList.remove('image-error');
        img.classList.add('image-loaded');
    }
    
    // Theo dõi tất cả ảnh
    function watchImages() {
        const images = document.querySelectorAll('img');
        
        images.forEach(img => {
            // Nếu đã xử lý rồi thì bỏ qua
            if (img.hasAttribute('data-image-watched')) return;
            
            img.setAttribute('data-image-watched', 'true');
            
            // Xử lý ảnh đã lỗi
            if (img.complete && img.naturalWidth === 0) {
                handleImageError(img);
                return;
            }
            
            // Xử lý ảnh đã load thành công
            if (img.complete && img.naturalWidth > 0) {
                handleImageSuccess(img);
                return;
            }
            
            // Thêm event listeners
            img.addEventListener('load', function() {
                handleImageSuccess(this);
            });
            
            img.addEventListener('error', function() {
                const retryCount = parseInt(this.getAttribute('data-retry-count') || '0');
                this.setAttribute('data-retry-count', retryCount + 1);
                handleImageError(this, retryCount);
            });
        });
    }
    
    // Tạo ảnh preview cho upload
    function createImagePreview(file, container) {
        return new Promise((resolve, reject) => {
            if (!file || !file.type.startsWith('image/')) {
                reject('File is not an image');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '200px';
                img.style.maxHeight = '200px';
                img.style.objectFit = 'cover';
                img.style.border = '1px solid #ddd';
                img.style.borderRadius = '4px';
                img.alt = file.name;
                
                if (container) {
                    container.appendChild(img);
                }
                
                resolve(img);
            };
            
            reader.onerror = function() {
                reject('Error reading file');
            };
            
            reader.readAsDataURL(file);
        });
    }
    
    // Xử lý upload ảnh
    async function handleImageUpload(formData, uploadUrl = 'upload_image.php') {
        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Image uploaded successfully:', result.imageUrl);
                return result;
            } else {
                throw new Error(result.message || 'Upload failed');
            }
        } catch (error) {
            console.error('❌ Image upload failed:', error);
            throw error;
        }
    }
    
    // Kiểm tra ảnh tồn tại
    function checkImageExists(src) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = src;
        });
    }
    
    // Tự động sửa đường dẫn ảnh
    function fixImagePaths() {
        const images = document.querySelectorAll('img[src*="uploads/room_images/"]');
        
        images.forEach(async (img) => {
            const originalSrc = img.src;
            const exists = await checkImageExists(originalSrc);
            
            if (!exists) {
                console.warn('Image not found, trying alternative paths:', originalSrc);
                // Thử các đường dẫn khác
                const alternatives = [
                    originalSrc.replace(/^.*\/uploads\//, './uploads/'),
                    originalSrc.replace(/^.*\/uploads\//, '../uploads/'),
                    originalSrc.replace(/^.*\/uploads\//, 'uploads/')
                ];
                
                for (const alt of alternatives) {
                    if (await checkImageExists(alt)) {
                        console.log('✅ Found alternative path:', alt);
                        img.src = alt;
                        return;
                    }
                }
                
                // Nếu không tìm thấy, dùng placeholder
                handleImageError(img, 999);
            }
        });
    }
    
    // CSS cho ảnh
    function addImageStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .image-error {
                opacity: 0.7;
                filter: grayscale(100%);
            }
            
            .image-loaded {
                transition: opacity 0.3s ease;
            }
            
            .image-loading {
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: loading 1.5s infinite;
            }
            
            @keyframes loading {
                0% { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Khởi tạo
    function init() {
        addImageStyles();
        watchImages();
        fixImagePaths();
        
        // Theo dõi ảnh mới được thêm vào
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'IMG') {
                            watchImages();
                        } else if (node.querySelectorAll) {
                            const images = node.querySelectorAll('img');
                            if (images.length > 0) {
                                watchImages();
                            }
                        }
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('✅ Image handler initialized');
    }
    
    // Export functions globally
    window.ImageHandler = {
        createPlaceholder: createPlaceholderSVG,
        createPreview: createImagePreview,
        uploadImage: handleImageUpload,
        checkExists: checkImageExists,
        init: init
    };
    
    // Auto initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Also run after delays to catch dynamically added images
    setTimeout(init, 1000);
    setTimeout(init, 3000);
    
})();
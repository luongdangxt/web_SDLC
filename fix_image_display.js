// Fix Image Display Issues
class ImageDisplayFixer {
    constructor() {
        this.baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');
    }

    // Sửa đường dẫn ảnh
    fixImagePath(imagePath) {
        if (!imagePath) return '';
        
        // Nếu đường dẫn đã có http/https, giữ nguyên
        if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
            return imagePath;
        }
        
        // Nếu đường dẫn bắt đầu bằng /, thêm domain
        if (imagePath.startsWith('/')) {
            return this.baseUrl + imagePath.substring(1);
        }
        
        // Nếu đường dẫn tương đối, thêm base URL
        return this.baseUrl + imagePath;
    }

    // Hiển thị ảnh với fallback
    displayImage(imgElement, imagePath) {
        const fixedPath = this.fixImagePath(imagePath);
        
        imgElement.onload = function() {
            console.log('✅ Ảnh tải thành công:', fixedPath);
        };
        
        imgElement.onerror = function() {
            console.error('❌ Lỗi tải ảnh:', fixedPath);
            // Hiển thị ảnh placeholder
            imgElement.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjBmMGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlIG5vdCBmb3VuZDwvdGV4dD48L3N2Zz4=';
            imgElement.alt = 'Image not found';
        };
        
        imgElement.src = fixedPath;
    }

    // Cập nhật tất cả ảnh trong trang
    fixAllImages() {
        const images = document.querySelectorAll('img[src*="uploads/room_images/"]');
        images.forEach(img => {
            const originalSrc = img.getAttribute('src');
            this.displayImage(img, originalSrc);
        });
    }

    // Tạo ảnh preview với fallback
    createImagePreview(container, imagePath, options = {}) {
        const defaultOptions = {
            width: '100px',
            height: '100px',
            objectFit: 'cover',
            border: '1px solid #ddd',
            borderRadius: '4px'
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        const img = document.createElement('img');
        img.style.width = finalOptions.width;
        img.style.height = finalOptions.height;
        img.style.objectFit = finalOptions.objectFit;
        img.style.border = finalOptions.border;
        img.style.borderRadius = finalOptions.borderRadius;
        img.alt = 'Room Image';
        
        this.displayImage(img, imagePath);
        
        if (container) {
            container.appendChild(img);
        }
        
        return img;
    }

    // Kiểm tra ảnh có tồn tại không
    async checkImageExists(imagePath) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = this.fixImagePath(imagePath);
        });
    }

    // Debug: Hiển thị thông tin ảnh
    debugImageInfo(imagePath) {
        console.log('=== Debug Image Info ===');
        console.log('Original path:', imagePath);
        console.log('Fixed path:', this.fixImagePath(imagePath));
        console.log('Base URL:', this.baseUrl);
        console.log('Current location:', window.location.href);
    }
}

// Khởi tạo khi trang được tải
document.addEventListener('DOMContentLoaded', () => {
    window.imageFixer = new ImageDisplayFixer();
    
    // Tự động sửa tất cả ảnh
    setTimeout(() => {
        window.imageFixer.fixAllImages();
    }, 1000);
    
    console.log('🖼️ Image Display Fixer đã được khởi tạo');
});

// Hàm global để sử dụng
function fixImageDisplay(imgElement, imagePath) {
    if (window.imageFixer) {
        window.imageFixer.displayImage(imgElement, imagePath);
    }
}

function createImagePreview(container, imagePath, options) {
    if (window.imageFixer) {
        return window.imageFixer.createImagePreview(container, imagePath, options);
    }
} 
// Admin Room Management Functions
class AdminRoomManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Add room form submission
        const addRoomForm = document.getElementById('addRoomForm');
        if (addRoomForm) {
            addRoomForm.addEventListener('submit', (e) => this.handleAddRoom(e));
        }

        // Update room form submission
        const updateRoomForm = document.getElementById('updateRoomForm');
        if (updateRoomForm) {
            updateRoomForm.addEventListener('submit', (e) => this.handleUpdateRoom(e));
        }

        // Image upload handling
        this.setupImageUpload();
    }

    setupImageUpload() {
        const imageInputs = document.querySelectorAll('input[type="file"][accept="image/*"]');
        imageInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleImageSelection(e));
        });
    }

    handleImageSelection(event) {
        const files = event.target.files;
        const previewContainer = event.target.parentElement.querySelector('.image-preview') || 
                               event.target.closest('.form-group').querySelector('.image-preview');
        
        if (previewContainer) {
            previewContainer.innerHTML = '';
            
            for (let file of files) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '100px';
                        img.style.height = '100px';
                        img.style.objectFit = 'cover';
                        img.style.margin = '5px';
                        img.style.border = '1px solid #ddd';
                        img.style.borderRadius = '4px';
                        previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            }
        }
    }

    async handleAddRoom(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData();
        
        // Get form data
        const roomData = {
            roomNumber: form.querySelector('[name="roomNumber"]').value,
            roomTypeID: form.querySelector('[name="roomTypeID"]').value,
            price: form.querySelector('[name="price"]').value,
            capacity: form.querySelector('[name="capacity"]').value,
            status: form.querySelector('[name="status"]').value,
            images: [] // Will be populated from existing images if any
        };

        // Add room data to FormData
        formData.append('roomData', JSON.stringify(roomData));
        
        // Add image files
        const imageInput = form.querySelector('input[type="file"]');
        if (imageInput && imageInput.files.length > 0) {
            for (let file of imageInput.files) {
                formData.append('images[]', file);
            }
        }

        try {
            const response = await fetch('admin_add_room.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                alert('Phòng đã được thêm thành công!');
                form.reset();
                this.clearImagePreviews();
                // Refresh room list if needed
                if (typeof loadRooms === 'function') {
                    loadRooms();
                }
            } else {
                alert('Lỗi: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi thêm phòng');
        }
    }

    async handleUpdateRoom(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData();
        
        // Get form data
        const roomData = {
            roomID: form.querySelector('[name="roomID"]').value,
            roomNumber: form.querySelector('[name="roomNumber"]').value,
            roomTypeID: form.querySelector('[name="roomTypeID"]').value,
            price: form.querySelector('[name="price"]').value,
            capacity: form.querySelector('[name="capacity"]').value,
            status: form.querySelector('[name="status"]').value,
            images: [] // Will be populated from existing images if any
        };

        // Add existing images
        const existingImages = form.querySelectorAll('.existing-image');
        existingImages.forEach(img => {
            if (img.dataset.url) {
                roomData.images.push({
                    url: img.dataset.url,
                    caption: img.dataset.caption || ''
                });
            }
        });

        // Add room data to FormData
        formData.append('roomData', JSON.stringify(roomData));
        
        // Add new image files
        const imageInput = form.querySelector('input[type="file"]');
        if (imageInput && imageInput.files.length > 0) {
            for (let file of imageInput.files) {
                formData.append('images[]', file);
            }
        }

        try {
            const response = await fetch('admin_update_room.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                alert('Phòng đã được cập nhật thành công!');
                // Refresh room list if needed
                if (typeof loadRooms === 'function') {
                    loadRooms();
                }
            } else {
                alert('Lỗi: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi cập nhật phòng');
        }
    }

    clearImagePreviews() {
        const previews = document.querySelectorAll('.image-preview');
        previews.forEach(preview => {
            preview.innerHTML = '';
        });
    }

    // Function to delete an image
    async deleteImage(imageUrl) {
        if (!confirm('Bạn có chắc chắn muốn xóa ảnh này?')) {
            return;
        }

        try {
            const response = await fetch('delete_image.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ imageUrl: imageUrl })
            });

            const result = await response.json();
            
            if (result.success) {
                // Remove image element from DOM
                const imageElement = document.querySelector(`[data-url="${imageUrl}"]`);
                if (imageElement) {
                    imageElement.remove();
                }
                alert('Ảnh đã được xóa thành công!');
            } else {
                alert('Lỗi: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi xóa ảnh');
        }
    }

    // Function to load room data for editing
    async loadRoomForEdit(roomId) {
        try {
            const response = await fetch(`admin_get_room.php?id=${roomId}`);
            const room = await response.json();
            
            if (room.error) {
                alert('Lỗi: ' + room.error);
                return;
            }

            // Populate form fields
            const form = document.getElementById('updateRoomForm');
            if (form) {
                form.querySelector('[name="roomID"]').value = room.RoomID;
                form.querySelector('[name="roomNumber"]').value = room.RoomNumber;
                form.querySelector('[name="roomTypeID"]').value = room.RoomTypeID;
                form.querySelector('[name="price"]').value = room.Price;
                form.querySelector('[name="capacity"]').value = room.Capacity;
                form.querySelector('[name="status"]').value = room.Status;

                // Display existing images
                this.displayExistingImages(room.images || [], form);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải thông tin phòng');
        }
    }

    displayExistingImages(images, form) {
        const previewContainer = form.querySelector('.existing-images');
        if (previewContainer) {
            previewContainer.innerHTML = '';
            
            images.forEach(image => {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'existing-image-container';
                imgContainer.style.display = 'inline-block';
                imgContainer.style.margin = '5px';
                imgContainer.style.position = 'relative';

                const img = document.createElement('img');
                img.style.width = '100px';
                img.style.height = '100px';
                img.style.objectFit = 'cover';
                img.style.border = '1px solid #ddd';
                img.style.borderRadius = '4px';
                img.dataset.url = image.ImageURL;
                img.dataset.caption = image.Caption || '';
                img.className = 'existing-image';
                img.alt = 'Room Image';

                // Sử dụng ImageDisplayFixer để hiển thị ảnh
                if (window.imageFixer) {
                    window.imageFixer.displayImage(img, image.ImageURL);
                } else {
                    img.src = image.ImageURL;
                }

                const deleteBtn = document.createElement('button');
                deleteBtn.innerHTML = '×';
                deleteBtn.style.position = 'absolute';
                deleteBtn.style.top = '-5px';
                deleteBtn.style.right = '-5px';
                deleteBtn.style.background = '#ff4444';
                deleteBtn.style.color = 'white';
                deleteBtn.style.border = 'none';
                deleteBtn.style.borderRadius = '50%';
                deleteBtn.style.width = '20px';
                deleteBtn.style.height = '20px';
                deleteBtn.style.cursor = 'pointer';
                deleteBtn.onclick = () => this.deleteImage(image.ImageURL);

                imgContainer.appendChild(img);
                imgContainer.appendChild(deleteBtn);
                previewContainer.appendChild(imgContainer);
            });
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminRoomManager();
}); 
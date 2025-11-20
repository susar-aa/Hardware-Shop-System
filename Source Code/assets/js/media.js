document.addEventListener('DOMContentLoaded', () => {
    const mediaGrid = document.getElementById('media-grid');
    const loader = document.getElementById('loader');
    const noMediaMessage = document.getElementById('no-media-message');

    // --- NEW: Upload Form Elements ---
    const uploadForm = document.getElementById('upload-form');
    const fileInput = document.getElementById('media-file-input');
    const uploadButton = document.getElementById('upload-button');
    const uploadStatus = document.getElementById('upload-status');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const imagePreview = document.getElementById('image-preview');

    const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB

    // --- NEW: File Input Change Listener (for preview and validation) ---
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        
        // Clear previous status
        uploadStatus.textContent = '';
        uploadStatus.className = 'text-sm mt-3';

        if (!file) {
            imagePreviewContainer.classList.add('hidden');
            return;
        }

        // Client-side size validation
        if (file.size > MAX_FILE_SIZE) {
            uploadStatus.textContent = 'Error: File is larger than 2MB.';
            uploadStatus.classList.add('text-red-600');
            fileInput.value = ''; // Clear the input
            imagePreviewContainer.classList.add('hidden');
            return;
        }

        // Show image preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.src = e.target.result;
            imagePreviewContainer.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });

    // --- NEW: Upload Form Submit Listener ---
    uploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const file = fileInput.files[0];

        // Final check
        if (!file) {
            uploadStatus.textContent = 'Please select a file to upload.';
            uploadStatus.className = 'text-sm mt-3 text-red-600';
            return;
        }
        if (file.size > MAX_FILE_SIZE) {
            uploadStatus.textContent = 'Error: File is larger than 2MB.';
            uploadStatus.className = 'text-sm mt-3 text-red-600';
            return;
        }

        uploadButton.disabled = true;
        uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Uploading...';
        uploadStatus.textContent = 'Uploading, please wait...';
        uploadStatus.className = 'text-sm mt-3 text-blue-600';

        const formData = new FormData();
        formData.append('mediaFile', file);

        try {
            const response = await fetch('api/media/upload.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'An unknown error occurred.');
            }

            uploadStatus.textContent = 'Upload successful! Refreshing...';
            uploadStatus.className = 'text-sm mt-3 text-green-600';

            // Clear form and preview
            uploadForm.reset();
            imagePreviewContainer.classList.add('hidden');
            imagePreview.src = '';

            // Refresh the media grid to show the new image
            await loadMedia();

            // Clear success message after a delay
            setTimeout(() => {
                uploadStatus.textContent = '';
            }, 3000);

        } catch (error) {
            console.error('Upload error:', error);
            uploadStatus.textContent = `Error: ${error.message}`;
            uploadStatus.className = 'text-sm mt-3 text-red-600';
        } finally {
            uploadButton.disabled = false;
            uploadButton.innerHTML = '<i class="fas fa-upload mr-2"></i>Upload';
        }
    });


    // --- Existing Media Loader Function ---
    async function loadMedia() {
        loader.classList.remove('hidden');
        mediaGrid.classList.add('hidden');
        noMediaMessage.classList.add('hidden');

        try {
            // Add cache-busting param to ensure we get fresh data after upload
            const response = await fetch(`api/media/read.php?t=${new Date().getTime()}`);
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
            }
            
            const images = await response.json();

            mediaGrid.innerHTML = ''; // Clear old data

            if (images.length === 0) {
                noMediaMessage.classList.remove('hidden');
            } else {
                images.forEach(url => {
                    const div = document.createElement('div');
                    div.className = 'aspect-square bg-gray-200 border-2 border-transparent hover:border-blue-500 rounded-lg overflow-hidden transition-all duration-150 cursor-pointer shadow-sm';
                    
                    const img = document.createElement('img');
                    img.src = url; // The URL should be relative, like 'uploads/image.jpg'
                    img.alt = 'Media Image';
                    img.className = 'w-full h-full object-cover';
                    
                    // Handle image load errors
                    img.onerror = () => {
                        div.innerHTML = `<div class="p-2 text-xs text-red-600 break-words">Error loading:<br>${url}</div>`;
                    };

                    div.appendChild(img);
                    
                    // Add click event to select the image
                    div.addEventListener('click', () => {
                        // Check if the opener window and the function exist
                        if (window.opener && typeof window.opener.selectImageFromMedia === 'function') {
                            window.opener.selectImageFromMedia(url);
                            window.close(); // Close the popup
                        } else {
                            // Fallback if the opener is lost or if window was opened directly
                            console.log('Image selected, but no opener window found to send the URL to.');
                            // Visually indicate selection
                            document.querySelectorAll('.border-blue-500').forEach(el => {
                                el.classList.remove('border-blue-500', 'border-4');
                            });
                            div.classList.add('border-blue-500', 'border-4');
                            // Do not close the window and do not show an alert
                        }
                    });
                    
                    mediaGrid.appendChild(div);
                });
            }
        } catch (error) {
            console.error('Error loading media:', error);
            mediaGrid.innerHTML = `<p class="text-red-500 col-span-full">${error.message}</p>`;
        } finally {
            loader.classList.add('hidden');
            mediaGrid.classList.remove('hidden');
        }
    }

    // Initial load
    loadMedia();
});
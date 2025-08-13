<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: view_products.php");
    exit();
}

$product_id = intval($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $size = trim($_POST['size']);
    $color = trim($_POST['color']);
    $brand = trim($_POST['brand']);
    $price = floatval($_POST['price']);
    $supplier_id = intval($_POST['supplier_id']);
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = "Product name is required";
    if ($category_id <= 0) $errors[] = "Please select a category";
    if (empty($size)) $errors[] = "Size is required";
    if (empty($color)) $errors[] = "Color is required";
    if (empty($brand)) $errors[] = "Brand is required";
    if ($price <= 0) $errors[] = "Price must be greater than 0";
    if ($supplier_id <= 0) $errors[] = "Please select a supplier";
    
    // Get current product data to preserve image if no new image uploaded
    $stmt = $conn->prepare("SELECT image_path FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $current_product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $image_path = $current_product['image_path']; // Keep current image by default
    
    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/products/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $file_name = $_FILES['product_image']['name'];
        $file_size = $_FILES['product_image']['size'];
        $file_type = $_FILES['product_image']['type'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.";
        }
        
        // Validate file size (5MB max)
        if ($file_size > 5 * 1024 * 1024) {
            $errors[] = "File size too large. Maximum size is 5MB.";
        }
        
        if (empty($errors)) {
            // Generate unique filename
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $unique_filename = uniqid('product_') . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($file_tmp, $target_path)) {
                // Delete old image if exists
                if (!empty($current_product['image_path']) && file_exists($current_product['image_path'])) {
                    unlink($current_product['image_path']);
                    // Also delete thumbnail if exists
                    $thumb_path = dirname($current_product['image_path']) . '/thumb_' . basename($current_product['image_path']);
                    if (file_exists($thumb_path)) {
                        unlink($thumb_path);
                    }
                }
                
                $image_path = 'uploads/products/' . $unique_filename;
                
                // Create thumbnail
                createThumbnail($target_path, $upload_dir . 'thumb_' . $unique_filename, 200, 200);
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }
    
    // Handle image removal
    if (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
        // Delete current image
        if (!empty($current_product['image_path']) && file_exists($current_product['image_path'])) {
            unlink($current_product['image_path']);
            // Also delete thumbnail if exists
            $thumb_path = dirname($current_product['image_path']) . '/thumb_' . basename($current_product['image_path']);
            if (file_exists($thumb_path)) {
                unlink($thumb_path);
            }
        }
        $image_path = null;
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE products 
            SET name = ?, category_id = ?, size = ?, color = ?, brand = ?, price = ?, supplier_id = ?, image_path = ?
            WHERE product_id = ?
        ");
        $stmt->bind_param("sisssdssi", $name, $category_id, $size, $color, $brand, $price, $supplier_id, $image_path, $product_id);
        
        if ($stmt->execute()) {
            header("Location: view_products.php?success=updated");
            exit();
        } else {
            $errors[] = "Error updating product: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Function to create thumbnail
function createThumbnail($source, $destination, $width, $height) {
    $info = getimagesize($source);
    if (!$info) return false;
    
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    $original_width = imagesx($image);
    $original_height = imagesy($image);
    
    // Calculate new dimensions maintaining aspect ratio
    $ratio = min($width / $original_width, $height / $original_height);
    $new_width = $original_width * $ratio;
    $new_height = $original_height * $ratio;
    
    $thumbnail = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($mime == 'image/png' || $mime == 'image/gif') {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefill($thumbnail, 0, 0, $transparent);
    }
    
    imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
    
    // Save thumbnail
    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($thumbnail, $destination, 85);
            break;
        case 'image/png':
            imagepng($thumbnail, $destination);
            break;
        case 'image/gif':
            imagegif($thumbnail, $destination);
            break;
        case 'image/webp':
            imagewebp($thumbnail, $destination, 85);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($thumbnail);
    
    return true;
}

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: view_products.php");
    exit();
}

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY name");
?>

<?php include '../includes/header.php'; ?>

<h2><i class="fas fa-edit"></i> Edit Product</h2>

<div class="card">
    <div class="card-header">
        <h3>Update Product Information</h3>
        <a href="view_products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="productForm" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <div class="form-group">
                        <label for="name"><i class="fas fa-tag"></i> Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($product['name']); ?>"
                               placeholder="Enter product name">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id"><i class="fas fa-list"></i> Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $category['category_id']; ?>"
                                        <?php echo $category['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="size"><i class="fas fa-ruler"></i> Size *</label>
                        <input type="text" id="size" name="size" class="form-control" required
                               value="<?php echo htmlspecialchars($product['size']); ?>"
                               placeholder="e.g., S, M, L, XL, 32, 10">
                    </div>
                    
                    <div class="form-group">
                        <label for="color"><i class="fas fa-palette"></i> Color *</label>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" id="color" name="color" class="form-control" required
                                   value="<?php echo htmlspecialchars($product['color']); ?>"
                                   placeholder="Enter color name">
                            <input type="color" id="colorPicker" style="width: 50px; height: 40px; border: none; border-radius: 5px;">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_image"><i class="fas fa-image"></i> Product Image</label>
                        
                        <!-- Current Image Display -->
                        <?php if (!empty($product['image_path']) && file_exists($product['image_path'])): ?>
                            <div class="current-image-container" id="currentImageContainer">
                                <div class="current-image-label">Current Image:</div>
                                <div class="current-image-display">
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" 
                                         alt="Current product image"
                                         class="current-image">
                                    <div class="current-image-actions">
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeCurrentImage()">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewFullImage('<?php echo htmlspecialchars($product['image_path']); ?>')">
                                            <i class="fas fa-expand"></i> View Full
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-image-notice">
                                <i class="fas fa-image"></i>
                                <span>No image currently uploaded</span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Image Upload -->
                        <div class="image-upload-container">
                            <input type="file" id="product_image" name="product_image" class="form-control" 
                                   accept="image/*" onchange="previewImage(this)">
                            <input type="hidden" id="remove_image" name="remove_image" value="0">
                            <div class="upload-help">
                                <i class="fas fa-info-circle"></i>
                                Upload a new image to replace the current one (JPG, PNG, GIF, WebP - Max: 5MB)
                            </div>
                            <div id="imagePreview" class="image-preview" style="display: none;">
                                <img id="previewImg" src="" alt="Preview">
                                <button type="button" class="remove-image" onclick="removePreview()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label for="brand"><i class="fas fa-certificate"></i> Brand *</label>
                        <input type="text" id="brand" name="brand" class="form-control" required
                               value="<?php echo htmlspecialchars($product['brand']); ?>"
                               placeholder="Enter brand name">
                    </div>
                    
                    <div class="form-group">
                        <label for="price"><i class="fas fa-dollar-sign"></i> Price *</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required
                               value="<?php echo $product['price']; ?>"
                               placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="supplier_id"><i class="fas fa-truck"></i> Supplier *</label>
                        <select id="supplier_id" name="supplier_id" class="form-control" required>
                            <option value="">Select Supplier</option>
                            <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                <option value="<?php echo $supplier['supplier_id']; ?>"
                                        <?php echo $supplier['supplier_id'] == $product['supplier_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supplier['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-boxes"></i> Current Stock</label>
                        <div style="padding: 0.75rem; background: rgba(102, 126, 234, 0.1); border-radius: 8px;">
                            <strong><?php echo $product['stock_quantity']; ?> units</strong>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #666;">
                                <i class="fas fa-info-circle"></i> Stock can be updated in the Stock Management section
                            </p>
                        </div>
                    </div>
                    
                    <!-- Image Upload Guidelines -->
                    <div class="upload-guidelines">
                        <h4><i class="fas fa-camera"></i> Image Guidelines</h4>
                        <ul>
                            <li>Use high-quality images for best results</li>
                            <li>Square images (1:1 ratio) work best</li>
                            <li>Minimum resolution: 300x300 pixels</li>
                            <li>Clear product visibility preferred</li>
                            <li>Good lighting and contrast</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e1e8ed;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Product
                </button>
                <a href="view_products.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <a href="../stock/restock.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-warning">
                    <i class="fas fa-boxes"></i> Manage Stock
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="image-modal" style="display: none;">
    <div class="image-modal-content">
        <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
        <img id="modalImage" src="" alt="">
        <div id="modalCaption" class="image-modal-caption">Product Image</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorInput = document.getElementById('color');
    const colorPicker = document.getElementById('colorPicker');
    const form = document.getElementById('productForm');
    
    // Initialize color picker with current color
    const currentColor = colorInput.value.toLowerCase();
    const hex = colorNameToHex(currentColor);
    if (hex) {
        colorPicker.value = hex;
    }
    
    // Color picker functionality
    colorPicker.addEventListener('change', function() {
        const hex = this.value;
        const colorName = hexToColorName(hex);
        colorInput.value = colorName;
    });
    
    colorInput.addEventListener('input', function() {
        const colorName = this.value.toLowerCase();
        const hex = colorNameToHex(colorName);
        if (hex) {
            colorPicker.value = hex;
        }
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const price = parseFloat(document.getElementById('price').value);
        
        if (price <= 0) {
            e.preventDefault();
            showNotification('Price must be greater than 0', 'danger');
            return;
        }
        
        // Confirm update
        if (!confirm('Are you sure you want to update this product?')) {
            e.preventDefault();
        }
    });
    
    // Real-time price formatting
    document.getElementById('price').addEventListener('input', function() {
        const value = parseFloat(this.value);
        if (!isNaN(value)) {
            this.setAttribute('title', `Price: ${value.toFixed(2)}`);
        }
    });
    
    function hexToColorName(hex) {
        const colors = {
            '#FF0000': 'Red',
            '#00FF00': 'Green',
            '#0000FF': 'Blue',
            '#FFFF00': 'Yellow',
            '#FF00FF': 'Magenta',
            '#00FFFF': 'Cyan',
            '#000000': 'Black',
            '#FFFFFF': 'White',
            '#808080': 'Gray',
            '#800000': 'Maroon',
            '#008000': 'Dark Green',
            '#000080': 'Navy',
            '#808000': 'Olive',
            '#800080': 'Purple',
            '#008080': 'Teal',
            '#C0C0C0': 'Silver',
            '#FFA500': 'Orange',
            '#A52A2A': 'Brown',
            '#FFC0CB': 'Pink'
        };
        
        return colors[hex.toUpperCase()] || hex;
    }
    
    function colorNameToHex(colorName) {
        const colors = {
            'red': '#FF0000',
            'green': '#00FF00',
            'blue': '#0000FF',
            'yellow': '#FFFF00',
            'magenta': '#FF00FF',
            'cyan': '#00FFFF',
            'black': '#000000',
            'white': '#FFFFFF',
            'gray': '#808080',
            'grey': '#808080',
            'maroon': '#800000',
            'dark green': '#008000',
            'navy': '#000080',
            'olive': '#808000',
            'purple': '#800080',
            'teal': '#008080',
            'silver': '#C0C0C0',
            'orange': '#FFA500',
            'brown': '#A52A2A',
            'pink': '#FFC0CB'
        };
        
        return colors[colorName.toLowerCase()];
    }
});

// Image preview functionality
function previewImage(input) {
    const file = input.files[0];
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (file) {
        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('File size too large. Maximum size is 5MB.', 'danger');
            input.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showNotification('Invalid file type. Please upload JPG, PNG, GIF, or WebP images only.', 'danger');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
        
        showNotification('New image selected. It will replace the current image when you save.', 'info');
    } else {
        preview.style.display = 'none';
    }
}

function removePreview() {
    document.getElementById('product_image').value = '';
    document.getElementById('imagePreview').style.display = 'none';
}

function removeCurrentImage() {
    if (confirm('Are you sure you want to remove the current product image? This action cannot be undone.')) {
        document.getElementById('remove_image').value = '1';
        document.getElementById('currentImageContainer').style.display = 'none';
        showNotification('Current image marked for removal. Save the product to confirm.', 'warning');
    }
}

function viewFullImage(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    
    modal.style.display = 'block';
    modalImg.src = imageSrc;
    
    // Add keyboard event listener
    document.addEventListener('keydown', handleModalKeydown);
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
    
    // Remove keyboard event listener
    document.removeEventListener('keydown', handleModalKeydown);
}

function handleModalKeydown(event) {
    if (event.key === 'Escape') {
        closeImageModal();
    }
}

// Close modal when clicking outside the image
document.getElementById('imageModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeImageModal();
    }
});
</script>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

.form-control:focus {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

#colorPicker {
    cursor: pointer;
    transition: transform 0.2s ease;
}

#colorPicker:hover {
    transform: scale(1.1);
}

/* Current Image Display */
.current-image-container {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e1e8ed;
}

.current-image-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.current-image-display {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.current-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e1e8ed;
    cursor: pointer;
    transition: all 0.3s ease;
}

.current-image:hover {
    transform: scale(1.05);
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.current-image-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.no-image-notice {
    padding: 2rem;
    text-align: center;
    background: #f8f9fa;
    border: 2px dashed #e1e8ed;
    border-radius: 8px;
    color: #6c757d;
    margin-bottom: 1rem;
}

.no-image-notice i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* Image Upload Container */
.image-upload-container {
    position: relative;
}

.upload-help {
    margin-top: 0.5rem;
    font-size: 0.85rem;
    color: #666;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.upload-help i {
    color: #3498db;
}

.image-preview {
    margin-top: 1rem;
    position: relative;
    display: inline-block;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
    padding: 0.5rem;
    background: #f8f9fa;
}

.image-preview img {
    max-width: 200px;
    max-height: 200px;
    object-fit: cover;
    border-radius: 4px;
    display: block;
}

.remove-image {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: all 0.2s ease;
}

.remove-image:hover {
    background: #c0392b;
    transform: scale(1.1);
}

.upload-guidelines {
    background: rgba(52, 152, 219, 0.1);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.upload-guidelines h4 {
    color: #3498db;
    margin-bottom: 0.75rem;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.upload-guidelines ul {
    margin: 0;
    padding-left: 1.5rem;
    font-size: 0.9rem;
    color: #666;
}

.upload-guidelines li {
    margin-bottom: 0.25rem;
}

/* File input styling */
input[type="file"] {
    padding: 0.5rem;
    border: 2px dashed #e1e8ed;
    border-radius: 8px;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s ease;
}

input[type="file"]:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

input[type="file"]:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Image Modal Styles */
.image-modal {
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.9);
    animation: fadeIn 0.3s ease;
}

.image-modal-content {
    margin: auto;
    display: block;
    width: 80%;
    max-width: 800px;
    position: relative;
    top: 50%;
    transform: translateY(-50%);
    animation: zoomIn 0.3s ease;
}

.image-modal-content img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
}

.image-modal-close {
    position: absolute;
    top: -40px;
    right: 0;
    color: #fff;
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    background: rgba(0, 0, 0, 0.5);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.image-modal-close:hover,
.image-modal-close:focus {
    color: #ff6b6b;
    transform: scale(1.1);
    background: rgba(0, 0, 0, 0.7);
}

.image-modal-caption {
    margin: auto;
    display: block;
    width: 80%;
    max-width: 800px;
    text-align: center;
    color: #ccc;
    padding: 10px 0;
    font-size: 1.1rem;
    font-weight: 600;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes zoomIn {
    from { 
        opacity: 0;
        transform: translateY(-50%) scale(0.7);
    }
    to { 
        opacity: 1;
        transform: translateY(-50%) scale(1);
    }
}

/* Button enhancements */
.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.25rem;
}

.current-image-actions .btn {
    width: 100%;
    justify-content: center;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .current-image-display {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .current-image-actions {
        flex-direction: row;
        width: 100%;
    }
    
    .image-modal-content {
        width: 95%;
        margin: 5% auto;
        transform: none;
        top: auto;
    }
    
    .image-modal-close {
        top: 10px;
        right: 10px;
        font-size: 1.5rem;
        width: 35px;
        height: 35px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
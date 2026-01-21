<?php
require_once __DIR__ . '/config/config.php';
requireRole('landlord');

$page_title = "Edit Property";
$error = '';
$success = '';
$conn = getDBConnection();

$user_id = $_SESSION['user_id'];
$property_id = $_GET['id'] ?? 0;

// Get property details
$stmt = $conn->prepare("SELECT * FROM properties WHERE id = ? AND landlord_id = ?");
$stmt->bind_param("ii", $property_id, $user_id);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$property) {
    redirect('my_properties.php');
}

// Get property images
$stmt = $conn->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->bind_param("i", $property_id);
$stmt->execute();
$images = $stmt->get_result();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $area = sanitizeInput($_POST['area'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $rent_price = $_POST['rent_price'] ?? 0;
    $bedrooms = $_POST['bedrooms'] ?? 0;
    $bathrooms = $_POST['bathrooms'] ?? 1;
    $square_feet = $_POST['square_feet'] ?? null;
    $available_from = $_POST['available_from'] ?? null;
    $status = sanitizeInput($_POST['status'] ?? 'available');
    
    if (empty($title) || empty($description) || empty($category) || empty($area) || empty($location) || empty($address) || $rent_price <= 0) {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($category, ['apartment', 'flat', 'house'])) {
        $error = 'Invalid category selected.';
    } else {
        $stmt = $conn->prepare("UPDATE properties SET title = ?, description = ?, category = ?, area = ?, location = ?, address = ?, rent_price = ?, bedrooms = ?, bathrooms = ?, square_feet = ?, available_from = ?, status = ? WHERE id = ? AND landlord_id = ?");
        $stmt->bind_param("ssssssdiiissii", $title, $description, $category, $area, $location, $address, $rent_price, $bedrooms, $bathrooms, $square_feet, $available_from, $status, $property_id, $user_id);
        
        if ($stmt->execute()) {
            // Handle new image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = PROPERTY_IMAGES_DIR;
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM property_images WHERE property_id = ? AND is_primary = 1");
                $stmt_check->bind_param("i", $property_id);
                $stmt_check->execute();
                $has_primary = $stmt_check->get_result()->fetch_row()[0] > 0;
                $stmt_check->close();
                $is_primary = !$has_primary;
                
                foreach ($_FILES['images']['name'] as $key => $filename) {
                    if ($_FILES['images']['error'][$key] == 0) {
                        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($file_ext, $allowed_exts)) {
                            $new_filename = 'property_' . $property_id . '_' . time() . '_' . $key . '.' . $file_ext;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $upload_path)) {
                                $image_path = 'uploads/properties/' . $new_filename;
                                $stmt_img = $conn->prepare("INSERT INTO property_images (property_id, image_path, is_primary) VALUES (?, ?, ?)");
                                $stmt_img->bind_param("isi", $property_id, $image_path, $is_primary);
                                $stmt_img->execute();
                                $stmt_img->close();
                                $is_primary = false;
                            }
                        }
                    }
                }
            }
            
            $success = 'Property updated successfully!';
            // Refresh property data
            $stmt = $conn->prepare("SELECT * FROM properties WHERE id = ? AND landlord_id = ?");
            $stmt->bind_param("ii", $property_id, $user_id);
            $stmt->execute();
            $property = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // Refresh images
            $stmt = $conn->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, id ASC");
            $stmt->bind_param("i", $property_id);
            $stmt->execute();
            $images = $stmt->get_result();
            $stmt->close();
        } else {
            $error = 'Failed to update property. Please try again.';
        }
        $stmt->close();
    }
}

// Handle image deletion
if (isset($_GET['delete_image']) && $_GET['delete_image'] > 0) {
    $image_id = (int)$_GET['delete_image'];
    $stmt = $conn->prepare("SELECT image_path FROM property_images WHERE id = ? AND property_id = ?");
    $stmt->bind_param("ii", $image_id, $property_id);
    $stmt->execute();
    $img = $stmt->get_result()->fetch_assoc();
    if ($img) {
        $file_path = __DIR__ . '/../' . $img['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $stmt = $conn->prepare("DELETE FROM property_images WHERE id = ? AND property_id = ?");
        $stmt->bind_param("ii", $image_id, $property_id);
        $stmt->execute();
    }
    $stmt->close();
    redirect('edit_property.php?id=' . $property_id);
}

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Edit Property</h1>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data" class="form-card">
                <div class="form-row">
                    <div class="form-group">
                        <label for="title">Property Title *</label>
                        <input type="text" name="title" id="title" required 
                               value="<?php echo htmlspecialchars($property['title']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select name="category" id="category" required>
                            <option value="apartment" <?php echo $property['category'] == 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                            <option value="flat" <?php echo $property['category'] == 'flat' ? 'selected' : ''; ?>>Flat</option>
                            <option value="house" <?php echo $property['category'] == 'house' ? 'selected' : ''; ?>>House</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea name="description" id="description" rows="5" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="area">Area *</label>
                        <input type="text" name="area" id="area" required 
                               value="<?php echo htmlspecialchars($property['area']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location/City *</label>
                        <input type="text" name="location" id="location" required 
                               value="<?php echo htmlspecialchars($property['location']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Full Address *</label>
                    <textarea name="address" id="address" rows="2" required><?php echo htmlspecialchars($property['address']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="rent_price">Monthly Rent (৳) *</label>
                        <input type="number" name="rent_price" id="rent_price" required min="0" step="0.01"
                               value="<?php echo $property['rent_price']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="available" <?php echo $property['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="booked" <?php echo $property['status'] == 'booked' ? 'selected' : ''; ?>>Booked</option>
                            <option value="unavailable" <?php echo $property['status'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="available_from">Available From</label>
                        <input type="date" name="available_from" id="available_from"
                               value="<?php echo $property['available_from'] ? date('Y-m-d', strtotime($property['available_from'])) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bedrooms">Bedrooms</label>
                        <input type="number" name="bedrooms" id="bedrooms" min="0" 
                               value="<?php echo $property['bedrooms']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bathrooms">Bathrooms</label>
                        <input type="number" name="bathrooms" id="bathrooms" min="1" 
                               value="<?php echo $property['bathrooms']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="square_feet">Square Feet</label>
                        <input type="number" name="square_feet" id="square_feet" min="0" 
                               value="<?php echo $property['square_feet'] ?? ''; ?>">
                    </div>
                </div>
                
                <?php if ($images->num_rows > 0): ?>
                    <div class="form-group">
                        <label>Current Images</label>
                        <div class="current-images">
                            <?php while ($img = $images->fetch_assoc()): ?>
                                <div class="image-preview">
                                    <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Property image">
                                    <?php if ($img['is_primary']): ?>
                                        <span class="primary-badge">Primary</span>
                                    <?php endif; ?>
                                    <a href="?id=<?php echo $property_id; ?>&delete_image=<?php echo $img['id']; ?>" 
                                       class="delete-image"
                                       onclick="return confirm('Delete this image?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="images">Add More Images</label>
                    <input type="file" name="images[]" id="images" multiple accept="image/*">
                    <small>You can select multiple images.</small>
                </div>
                
                <div class="form-actions">
                    <a href="my_properties.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Property</button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>


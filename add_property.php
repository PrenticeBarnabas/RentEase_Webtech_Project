<?php
require_once __DIR__ . '/config/config.php';
requireRole('landlord');

$page_title = "Add Property";
$error = '';
$success = '';
$conn = getDBConnection();

$user_id = $_SESSION['user_id'];

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
    
    if (empty($title) || empty($description) || empty($category) || empty($area) || empty($location) || empty($address) || $rent_price <= 0) {
        $error = 'Please fill in all required fields.';
    } elseif (!in_array($category, ['apartment', 'flat', 'house'])) {
        $error = 'Invalid category selected.';
    } else {
        $stmt = $conn->prepare("INSERT INTO properties (landlord_id, title, description, category, area, location, address, rent_price, bedrooms, bathrooms, square_feet, available_from) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssdiiis", $user_id, $title, $description, $category, $area, $location, $address, $rent_price, $bedrooms, $bathrooms, $square_feet, $available_from);
        
        if ($stmt->execute()) {
            $property_id = $conn->insert_id;
            
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = PROPERTY_IMAGES_DIR;
                $is_primary = true;
                
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
                                $is_primary = false; // Only first image is primary
                            }
                        }
                    }
                }
            }
            
            $success = 'Property added successfully!';
            header('refresh:2;url=my_properties.php');
        } else {
            $error = 'Failed to add property. Please try again.';
        }
        $stmt->close();
    }
}

closeDBConnection($conn);

include 'includes/header.php';
?>
<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Add New Property</h1>
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
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select name="category" id="category" required>
                            <option value="">Select Category</option>
                            <option value="apartment" <?php echo (isset($_POST['category']) && $_POST['category'] == 'apartment') ? 'selected' : ''; ?>>Apartment</option>
                            <option value="flat" <?php echo (isset($_POST['category']) && $_POST['category'] == 'flat') ? 'selected' : ''; ?>>Flat</option>
                            <option value="house" <?php echo (isset($_POST['category']) && $_POST['category'] == 'house') ? 'selected' : ''; ?>>House</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea name="description" id="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="area">Area *</label>
                        <input type="text" name="area" id="area" required 
                               value="<?php echo isset($_POST['area']) ? htmlspecialchars($_POST['area']) : ''; ?>"
                               placeholder="e.g., Downtown, Suburb">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location/City *</label>
                        <input type="text" name="location" id="location" required 
                               value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Full Address *</label>
                    <textarea name="address" id="address" rows="2" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="rent_price">Monthly Rent (৳) *</label>
                        <input type="number" name="rent_price" id="rent_price" required min="0" step="0.01"
                               value="<?php echo isset($_POST['rent_price']) ? htmlspecialchars($_POST['rent_price']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="available_from">Available From</label>
                        <input type="date" name="available_from" id="available_from"
                               value="<?php echo isset($_POST['available_from']) ? htmlspecialchars($_POST['available_from']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bedrooms">Bedrooms</label>
                        <input type="number" name="bedrooms" id="bedrooms" min="0" 
                               value="<?php echo isset($_POST['bedrooms']) ? htmlspecialchars($_POST['bedrooms']) : '0'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bathrooms">Bathrooms</label>
                        <input type="number" name="bathrooms" id="bathrooms" min="1" 
                               value="<?php echo isset($_POST['bathrooms']) ? htmlspecialchars($_POST['bathrooms']) : '1'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="square_feet">Square Feet</label>
                        <input type="number" name="square_feet" id="square_feet" min="0" 
                               value="<?php echo isset($_POST['square_feet']) ? htmlspecialchars($_POST['square_feet']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="images">Property Images</label>
                    <input type="file" name="images[]" id="images" multiple accept="image/*">
                    <small>You can select multiple images. First image will be used as primary.</small>
                </div>
                
                <div class="form-actions">
                    <a href="my_properties.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Property</button>
                </div>
            </form>
        </div>
    </div>
</main>
<?php include 'includes/footer.php'; ?>


<?php
// Function to load marker data from JSON file
function loadMarkerData() {
    $markersJson = file_get_contents('data/markers.json'); // Corrected path
    return json_decode($markersJson, true);
}

// Function to save marker data to JSON file
function saveMarkerData($markers) {
    $markersJson = json_encode(['STATION' => $markers], JSON_PRETTY_PRINT);
    file_put_contents('data/markers.json', $markersJson); // Corrected path
}

// Function to add or update a marker
function addOrUpdateMarker($markerData) {
    // Check if a new file was uploaded
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        // Handle uploading and updating picture

        // Delete old picture if it exists
        if (!empty($markerData['old_picture'])) {
            $oldPicturePath = 'pictures/' . $markerData['old_picture'];
            if (file_exists($oldPicturePath)) {
                unlink($oldPicturePath);
            }
        }
        $markerData['picture'] = $_FILES['picture']['name']; // Update picture filename
    } elseif (isset($markerData['old_picture'])) {
        // Keep the old picture if no new picture is selected
        $markerData['picture'] = $markerData['old_picture'];
    } else {
        // Set picture to an empty string if no new picture is uploaded and no old picture exists
        $markerData['picture'] = '';
    }

    $markers = loadMarkerData();
    $existingMarker = getMarkerById($markerData['id']);
    if ($existingMarker) {
        // Update existing marker
        $markers['STATION'] = array_map(function($marker) use ($markerData) {
            if ($marker['id'] == $markerData['id']) {
                return $markerData;
            }
            return $marker;
        }, $markers['STATION']);
    } else {
        // Add new marker
        $markers['STATION'][] = $markerData;
    }
    saveMarkerData($markers['STATION']);
}

// Function to delete a marker
function deleteMarker($id) {
    $markers = loadMarkerData();
    $markers['STATION'] = array_values(array_filter($markers['STATION'], function($marker) use ($id) {
        if ($marker['id'] == $id) {
            // Delete picture if it exists
            if (!empty($marker['picture'])) {
                unlink('pictures/' . $marker['picture']);
            }
            return false;
        }
        return true;
    }));
    saveMarkerData($markers['STATION']);
}

// Function to get marker data by ID
function getMarkerById($id) {
    $markers = loadMarkerData();
    foreach ($markers['STATION'] as $marker) {
        if ($marker['id'] == $id) {
            return $marker;
        }
    }
    return null;
}

// Check if form is submitted for adding, updating, or deleting marker
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'id' => $_POST['id'],
        'latitude' => $_POST['latitude'],
        'longitude' => $_POST['longitude'],
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'product' => $_POST['product'],
        'other_product' => $_POST['other_product'],
        'service' => $_POST['service'],
        'province' => $_POST['province'],
        'old_picture' => $_POST['old_picture'] ?? '' // Set old_picture to existing picture filename or empty string
    ];

    // Check if a new file was uploaded
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        // Code to handle uploading and updating picture
        $formData['picture'] = $_FILES['picture']['name']; // Capture the file name

        // Delete old picture if it exists
        if (!empty($formData['old_picture'])) {
            $oldPicturePath = 'pictures/' . $formData['old_picture'];
            if (file_exists($oldPicturePath)) {
                unlink($oldPicturePath);
            }
        }
    } else {
        // Keep the old picture if no new picture is selected
        $formData['picture'] = $formData['old_picture'];
    }

    // Add or update the marker
    addOrUpdateMarker($formData);

    // Handle file upload
    $uploadDir = 'pictures/'; // Directory where uploaded files will be stored
    $uploadedFile = $uploadDir . basename($_FILES['picture']['name']);
    move_uploaded_file($_FILES['picture']['tmp_name'], $uploadedFile);

    // Redirect back to the HTML page
    header('Location: index.html'); // Change index.html to the name of your HTML file
    exit();
}

// Check if request is for deleting a marker
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id']; // Get id parameter from the query string
    deleteMarker($id);
    exit(json_encode(['success' => true]));
}

// Function to load marker data by ID for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $marker = getMarkerById($id);
    if ($marker) {
        header('Content-Type: application/json');
        echo json_encode(['STATION' => [$marker]], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['error' => 'Marker not found'], JSON_PRETTY_PRINT);
    }
    exit();
}

// Load marker data for GET request
header('Content-Type: application/json');
echo json_encode(loadMarkerData(), JSON_PRETTY_PRINT);
?>

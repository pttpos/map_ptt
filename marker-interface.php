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
    // Check if a file was uploaded
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'pictures/'; // Directory to upload pictures
        
        // Delete old picture if updating an existing marker
        if (!empty($markerData['old_picture'])) {
            unlink($uploadDir . $markerData['old_picture']);
        }

        $uploadFileName = uniqid() . '_' . basename($_FILES['picture']['name']);
        $uploadFile = $uploadDir . $uploadFileName;
        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['picture']['tmp_name'], $uploadFile)) {
            // Add the picture path to the marker data
            $markerData['picture'] = $uploadFileName;
        } else {
            echo "Failed to upload picture.";
            return;
        }
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
        'old_picture' => $_POST['old_picture'] ?? '' // Store old picture filename if exists
    ];
    
    // Add or update the marker
    addOrUpdateMarker($formData);
    
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

// Load marker data for GET request
header('Content-Type: application/json');
echo json_encode(loadMarkerData(), JSON_PRETTY_PRINT);
?>

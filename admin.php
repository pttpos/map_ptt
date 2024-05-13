<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Interface</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>


<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>






</head>

<body>
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Marker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="marker-form" method="POST" action="marker-interface.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="id" class="form-label">Station ID:</label>
                            <input type="text" class="form-control" id="id" name="id" required>
                        </div>
                        <div class="mb-3">
                            <label for="title" class="form-label">Station Name :</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <select id="edit-province" class="form-select" name="province" required>
                            <option value="" selected disabled>Select Province</option>
                        </select>
                        <div class="mb-3">
                            <label for="latitude" class="form-label">Latitude:</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" required>
                        </div>
                        <div class="mb-3">
                            <label for="longitude" class="form-label">Longitude:</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" required>
                        </div>
                        <!-- product -->
                        <div class="mb-3">
                            <label class="form-label">Product:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ulg95" name="product[]"
                                    value="ULG 95">
                                <label class="form-check-label" for="ulg95">ULG 95</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ulr91" name="product[]"
                                    value="ULR 91">
                                <label class="form-check-label" for="7eleven">ULR 91</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="hsd" name="product[]" value="HSD">
                                <label class="form-check-label" for="ev">HSD</label>
                            </div>
                            <!-- Add more checkboxes as needed -->
                        </div>
                        <!-- other_product -->
                        <div class="mb-3">
                            <label class="form-label">Other Product:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ev" name="other_product[]"
                                    value="EV">
                                <label class="form-check-label" for="ev">EV</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="onion" name="other_product[]"
                                    value="Onion">
                                <label class="form-check-label" for="onion">Onion</label>
                            </div>
                            <!-- Add more checkboxes as needed -->
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="amazon" name="description[]"
                                    value="Amazon">
                                <label class="form-check-label" for="amazon">Amazon</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="7eleven" name="description[]"
                                    value="7-Eleven">
                                <label class="form-check-label" for="7eleven">7-Eleven</label>
                            </div>

                            <!-- Add more checkboxes as needed -->
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="fleetcard" name="service[]"
                                    value="Fleet card">
                                <label class="form-check-label" for="fleetcard">Fleet Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="aba" name="service[]" value="ABA">
                                <label class="form-check-label" for="aba">ABA QR</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="test" name="service[]" value="Cash">
                                <label class="form-check-label" for="test">Cash</label>
                            </div>
                            <!-- Add more checkboxes as needed -->
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">
                                <Address></Address> :
                            </label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        <div class="mb-3">
                            <label for="picture" class="form-label">Picture:</label>
                            <input type="file" class="form-control" id="picture" name="picture">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Marker</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Add a new modal form for editing markers -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Marker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-marker-form" method="POST" action="marker-interface.php"
                        enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="edit-id" class="form-label">Station ID:</label>
                            <input type="text" class="form-control" id="edit-id" name="id" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit-title" class="form-label">Station Name:</label>
                            <input type="text" class="form-control" id="edit-title" name="title" required>
                        </div>
                        <div class="mb-3">
                        <label for="edit-picture" class="form-label">Province:</label>
                            <select id="edit-province" class="form-select" name="province" required>
                                <option value="" selected disabled>Select Province</option>
                                <option value="Banteay Meanchey">Banteay Meanchey</option>
                                <option value="Battambang">Battambang</option>
                                <option value="Kampong Cham">Kampong Cham</option>
                                <option value="Kampong Chhnang">Kampong Chhnang</option>
                                <option value="Kampong Speu">Kampong Speu</option>
                                <option value="Kampong Thom">Kampong Thom</option>
                                <option value="Kampot">Kampot</option>
                                <option value="Kandal">Kandal</option>
                                <option value="Koh Kong">Koh Kong</option>
                                <option value="Kratié">Kratié</option>
                                <option value="Mondulkiri">Mondulkiri</option>
                                <option value="Oddar Meanchey">Oddar Meanchey</option>
                                <option value="Phnom Penh">Phnom Penh</option>
                                <option value="Pailin">Pailin</option>
                                <option value="Preah Sihanouk">Preah Sihanouk</option>
                                <option value="Preah Vihear">Preah Vihear</option>
                                <option value="Pursat">Pursat</option>
                                <option value="Ratanakiri">Ratanakiri</option>
                                <option value="Siem Reap">Siem Reap</option>
                                <option value="Prey Veng">Prey Veng</option>
                                <option value="Stung Treng">Stung Treng</option>
                                <option value="Svay Rieng">Svay Rieng</option>
                                <option value="Takéo">Takéo</option>
                                <option value="Kep">Kep</option>
                                <option value="Otdar Meanchey">Otdar Meanchey</option>
                                <option value="Pursat">Pursat</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="edit-latitude" class="form-label">Latitude:</label>
                            <input type="text" class="form-control" id="edit-latitude" name="latitude" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-longitude" class="form-label">Longitude:</label>
                            <input type="text" class="form-control" id="edit-longitude" name="longitude" required>
                        </div>

                        <!-- product  -->
                        <div class="mb-3">
                            <label class="form-label">Product:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-ulg95" name="product[]"
                                    value="ULG 95">
                                <label class="form-check-label" for="edit-ulg95">ULG 95</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-ulr91" name="product[]"
                                    value="ULR 91">
                                <label class="form-check-label" for="edit-ulr91">ULR 91</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-hsd" name="product[]"
                                    value="HSD">
                                <label class="form-check-label" for="edit-hsd">HSD</label>
                            </div>
                            <!-- Add more checkboxes as needed -->
                        </div>

                        <!-- other product  -->
                        <div class="mb-3">
                            <label class="form-label">Other Product:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-ev" name="other_product[]"
                                    value="EV">
                                <label class="form-check-label" for="edit-ev">EV</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-onion" name="other_product[]"
                                    value="Onion">
                                <label class="form-check-label" for="edit-onion">Onion</label>
                            </div>
                            <!-- Add more checkboxes as needed -->
                        </div>
                        <!-- service  -->
                        <div class="mb-3">
                            <label class="form-label">Service:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-amazon" name="description[]"
                                    value="Amazon">
                                <label class="form-check-label" for="edit-amazon">Amazon</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-7eleven" name="description[]"
                                    value="7-Eleven">
                                <label class="form-check-label" for="edit-7eleven">7-Eleven</label>
                            </div>
                            <!-- Add more checkboxes as needed -->
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-fleetcard" name="service[]"
                                    value="Fleet card">
                                <label class="form-check-label" for="edit-fleetcard">Fleet Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-aba" name="service[]"
                                    value="ABA">
                                <label class="form-check-label" for="edit-aba">ABA QR</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-test" name="service[]"
                                    value="Cash">
                                <label class="form-check-label" for="edit-test">Cash</label>
                            </div>
                            <!-- Add more checkboxes as needed -->
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">
                                Address:
                            </label>
                            <input type="text" class="form-control" id="edit-address" name="address" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-picture" class="form-label">New Picture:</label>
                            <input type="file" class="form-control" id="edit-picture" name="picture">
                        </div>
                        <input type="hidden" id="old-picture" name="old_picture">
                        <!-- Display selected file name -->
                        <div id="edit-file-name"></div>
                        <button type="submit" class="btn btn-primary">Update Marker</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to update the selected file name in the form
        document.getElementById('edit-picture').addEventListener('change', function () {
            var fileName = this.value.split('\\').pop(); // Get the file name without path
            document.getElementById('edit-file-name').textContent = 'Selected File: ' + fileName;
        });
    </script>


    <script>
        // Function to populate the province dropdown
        function populateProvinceDropdown(elementId) {
            // Select the dropdown element
            const dropdown = document.getElementById(elementId);

            // Array of provinces
            const provinces = [
                "Banteay Meanchey", "Battambang", "Kampong Cham", "Kampong Chhnang", "Kampong Speu",
                "Kampong Thom", "Kampot", "Kandal", "Koh Kong", "Kratié", "Mondulkiri", "Oddar Meanchey","Phnom Penh",
                "Pailin", "Preah Sihanouk", "Preah Vihear", "Pursat", "Ratanakiri", "Siem Reap", "Prey Veng",
                "Stung Treng", "Svay Rieng", "Takéo", "Kep", "Otdar Meanchey", "Pursat"
            ];

            // Loop through provinces array and add each province as an option to the dropdown
            provinces.forEach(province => {
                const option = document.createElement("option");
                option.value = province;
                option.textContent = province;
                dropdown.appendChild(option);
            });
        }

        // Call the function to populate the dropdown for the add marker modal
        populateProvinceDropdown("edit-province");

        // Call the function to populate the dropdown for the edit marker modal
        populateProvinceDropdown("edit-province");
    </script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
    // Initialize DataTables
    $('#marker-table').DataTable({
        "order": [] // Disables initial sorting
    });

    // Fetch marker data and display in table
    fetch('marker-interface.php')
        .then(response => response.json())
        .then(data => {
            var tableBody = document.getElementById('marker-table-body');
            var markers = data.STATION;

            function displayMarkers(markers) {
                tableBody.innerHTML = '';
                markers.forEach(marker => {
                    var row = document.createElement('tr');
                    row.innerHTML = `<td>${marker.id}</td>
                                <td>${marker.latitude}</td>
                                <td>${marker.longitude}</td>
                                <td>${marker.title}</td>
                                <td>${marker.product}</td>
                                <td>${marker.other_product}</td>
                                <td>${marker.description}</td>
                                <td>${marker.service}</td>
                                <td>${marker.payment}</td>
                                <td>${marker.province}</td>
                                <td>${marker.address}</td>
                                <td>${marker.picture ? `<img src="pictures/${marker.picture}" alt="Marker Image" style="max-width:40px; border-radius: 50%;">` : 'No Image'}</td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="deleteMarker(${marker.id})">Delete</button>
                                    <button class="btn btn-primary btn-sm" onclick="editMarker(${marker.id})" data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>
                                </td>`;
                    tableBody.appendChild(row);
                });
            }

            displayMarkers(markers);
        })
        .catch(error => console.error('Error:', error));
});

// Function to delete marker
function deleteMarker(id) {
    if (confirm('Are you sure you want to delete this marker?')) {
        // Perform delete operation
        fetch(`marker-interface.php?id=${id}`, {
            method: 'DELETE'
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log(data);
                // Refresh page to update marker list
                window.location.reload();
            })
            .catch(error => console.error('Error:', error));
    }
}

// Function to populate edit form and show edit modal
function editMarker(id) {
    // Fetch marker data corresponding to the ID
    fetch(`marker-interface.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            // Check if data is not empty and has the expected structure
            if (data && data.STATION.length > 0) {
                // Populate the modal form fields with the marker data
                const marker = data.STATION[0];
                document.getElementById('edit-id').value = marker.id;
                document.getElementById('edit-latitude').value = marker.latitude;
                document.getElementById('edit-longitude').value = marker.longitude;
                document.getElementById('edit-title').value = marker.title;
                document.getElementById('edit-province').value = marker.province;
                document.getElementById('edit-address').value = marker.address;

                // Populate checkbox groups if the arrays are not null
                if (marker.product !== null) {
                    marker.product.forEach(prod => {
                        // Check each checkbox based on its value
                        document.querySelectorAll(`input[name="product[]"]`).forEach(checkbox => {
                            if (checkbox.value === prod) {
                                checkbox.checked = true;
                            }
                        });
                    });
                }
                if (marker.other_product !== null) {
                    marker.other_product.forEach(oprod => {
                        document.querySelectorAll(`input[name="other_product[]"]`).forEach(checkbox => {
                            if (checkbox.value === oprod) {
                                checkbox.checked = true;
                            }
                        });
                    });
                }
                if (marker.description !== null) {
                    marker.description.forEach(desc => {
                        document.querySelectorAll(`input[name="description[]"]`).forEach(checkbox => {
                            if (checkbox.value === desc) {
                                checkbox.checked = true;
                            }
                        });
                    });
                }
                if (marker.service !== null) {
                    marker.service.forEach(serv => {
                        document.querySelectorAll(`input[name="service[]"]`).forEach(checkbox => {
                            if (checkbox.value === serv) {
                                checkbox.checked = true;
                            }
                        });
                    });
                }

                // Set the old picture filename or an empty string if no picture exists
                document.getElementById('old-picture').value = marker.picture || '';

                // Display the filename of the existing picture, if available
                if (marker.picture) {
                    const fileName = marker.picture.split('/').pop(); // Extract filename from the path
                    document.getElementById('edit-file-name').textContent = 'Selected File: ' + fileName;
                } else {
                    document.getElementById('edit-file-name').textContent = 'No Picture Selected';
                }
            } else {
                console.error('No data found for the specified ID.');
            }
        })
        .catch(error => console.error('Error:', error));
}

</script>
<div class="container mt-5">
    <div class="card">
        <div class="card-body">
            <!-- Marker Data Table -->
            <h2 class="card-title mt-5">Marker Data</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                Add Marker
            </button>
            <div class="table-responsive mt-3">
                <table id="marker-table" class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Title</th>
                            <th>Product</th>
                            <th>Other Product</th>
                            <th>Description</th>
                            <th>Service</th>
                            <th>Payment</th>
                            <th>Province</th>
                            <th>Address</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="marker-table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>


</body>

</html>
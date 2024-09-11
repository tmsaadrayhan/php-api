<?php

// Allow from any origin
header("Access-Control-Allow-Origin: *");

// Allow the following methods
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Allow specific headers
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Rest of your PHP code...


include 'db.php';

// Get JSON input and decode it
$input = json_decode(file_get_contents('php://input'), true);

//Get All Doctors
if ($_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['id'])) {
    $sql = "SELECT * FROM doctors";
    $result = $conn->query($sql);

    $doctors = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
    }
    echo json_encode($doctors);
}

//Get doctor by id
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $sql = "SELECT * FROM doctors WHERE id = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["message" => "Doctor not found"]);
    }
}

// Create Doctor
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $input['name'];
    $specialty = $input['specialty'];
    $phone = $input['phone'];
    $email = $input['email'];

    $sql = "INSERT INTO doctors (name, specialty, phone, email) VALUES ('$name', '$specialty', '$phone', '$email')";
    if ($conn->query($sql) === TRUE) {
        echo "New doctor created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Update Doctor
if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $id = $input['id'];
    $name = $input['name'];
    $specialty = $input['specialty'];
    $phone = $input['phone'];
    $email = $input['email'];

    $sql = "UPDATE doctors SET name='$name', specialty='$specialty', phone='$phone', email='$email' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        echo "Doctor updated successfully";
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

// Delete Doctor by ID
if ($_SERVER['REQUEST_METHOD'] == 'DELETE' && isset($_GET['id'])) {
    $id = $conn->real_escape_string($_GET['id']);
    $sql = "DELETE FROM doctors WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["message" => "Doctor deleted successfully"]);
    } else {
        echo json_encode(["message" => "Error deleting doctor"]);
    }
}

$conn->close();
?>

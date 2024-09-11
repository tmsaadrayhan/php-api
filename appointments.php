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

// Get all appointments
if ($_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['years']) && !isset($_GET['year']) && !isset($_GET['month']) && !isset($_GET['date'])) {
    $sql = "SELECT * FROM appointments";
    $result = $conn->query($sql);

    $appointments = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
    echo json_encode($appointments);
}

// Get all distinct years of appointments
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['years'])) {
    // SQL query to get distinct years from the appointment_date field
    $sql = "SELECT DISTINCT YEAR(appointment_date) AS year FROM appointments ORDER BY year ASC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();

        $years = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $years[] = $row['year'];
            }
            echo json_encode($years);  // Return the distinct years as JSON
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "No appointments found."));
        }
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Error preparing SQL statement."));
    }
}

// Get all distinct months of a specific year
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['year']) && !isset($_GET['month'])) {
    $year = $_GET['year'];
    $sql = "SELECT DISTINCT MONTH(appointment_date) AS month FROM appointments WHERE YEAR(appointment_date) = ? ORDER BY month ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $months = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $months[] = $row['month'];
        }
    }
    echo json_encode($months);
}

// Get all distinct dates of a specific month and year
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['year']) && isset($_GET['month'])) {
    $year = $_GET['year'];
    $month = $_GET['month'];

    $sql = "SELECT DISTINCT DATE(appointment_date) AS appointment_date 
            FROM appointments 
            WHERE MONTH(appointment_date) = ? 
            AND YEAR(appointment_date) = ? 
            ORDER BY appointment_date ASC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $month, $year); // Bind month and year
        $stmt->execute();
        $result = $stmt->get_result();

        $days = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $days[] = $row['appointment_date']; // Access 'appointment_date'
            }
        }

        echo json_encode($days);
    } else {
        // Handle SQL preparation error
        http_response_code(500);
        echo json_encode(array("message" => "Error preparing SQL statement."));
    }
}

// Get appointments for a specific date with doctor's name
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['date'])) {
    $date = $_GET['date'];

    // SQL query to get appointments for the specific date
    $sql = "SELECT appointments.id, appointments.patient_name, appointments.appointment_date, 
                   doctors.name AS doctor_name 
            FROM appointments 
            JOIN doctors ON appointments.doctor_id = doctors.id 
            WHERE DATE(appointments.appointment_date) = ? 
            ORDER BY appointments.appointment_date ASC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $date);  // Bind the date
        $stmt->execute();
        $result = $stmt->get_result();

        $appointments = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $appointments[] = $row;
            }
        }

        echo json_encode($appointments);  // Return the appointments in JSON format
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Error preparing SQL statement."));
    }
}

// Create a new appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    // Check if all required fields are present
    if (!empty($data->doctor_id) && !empty($data->patient_name) && !empty($data->appointment_date)) {

        // Prepare the SQL query to insert a new appointment
        $doctor_id = $conn->real_escape_string($data->doctor_id);
        $patient_name = $conn->real_escape_string($data->patient_name);
        $appointment_date = $conn->real_escape_string($data->appointment_date);

        $sql = "INSERT INTO appointments (doctor_id, patient_name, appointment_date) 
            VALUES ('$doctor_id', '$patient_name', '$appointment_date')";

        // Execute the query
        if ($conn->query($sql) === TRUE) {
            // Appointment created successfully
            http_response_code(201); // 201 Created
            echo json_encode(array("message" => "Appointment created successfully."));
        } else {
            // If there is a query error
            http_response_code(500); // Internal Server Error
            echo json_encode(array("message" => "Failed to create appointment.", "error" => $conn->error));
        }
    } else {
        // If required data is missing
        http_response_code(400); // 400 Bad Request
        echo json_encode(array("message" => "Incomplete data. doctor_id, patient_name, and appointment_date are required."));
    }

    $conn->close();
}

// Update an appointment
if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    // Get the raw PUT data
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['id']) && isset($data['doctor_id']) && isset($data['patient_name']) && isset($data['appointment_date'])) {
        $id = $data['id'];
        $doctor_id = $data['doctor_id'];
        $patient_name = $data['patient_name'];
        $appointment_date = $data['appointment_date'];

        $sql = "UPDATE appointments SET doctor_id = ?, patient_name = ?, appointment_date = ? WHERE id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issi", $doctor_id, $patient_name, $appointment_date, $id);
            if ($stmt->execute()) {
                echo json_encode(array("message" => "Appointment updated successfully."));
            } else {
                http_response_code(500);
                echo json_encode(array("message" => "Error updating appointment."));
            }
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Error preparing SQL statement."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid input."));
    }
}

// Delete appointment by ID
if ($_SERVER['REQUEST_METHOD'] == 'DELETE' && isset($_GET['id'])) {
    $id = $_GET['id'];

    // SQL query to delete the appointment by ID
    $sql = "DELETE FROM appointments WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);  // Bind the appointment ID
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("message" => "Appointment deleted successfully."));
        } else {
            http_response_code(500);
            echo json_encode(array("message" => "Failed to delete appointment."));
        }
    } else {
        http_response_code(500);
        echo json_encode(array("message" => "Error preparing SQL statement."));
    }
}

$conn->close();
?>
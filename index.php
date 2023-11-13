<?php
require_once 'config.php';

// Create a connection to the database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check the connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

// Detect mode ( get || set )
if (!isset($_GET['mode'])) {
	die("Mode parameter is required, use 'get' or 'set'.");
}
$mode = strtolower($_GET['mode']); // ignore case, convert to lowercase

// Validation for token and value (Add these checks before using them in queries)
$token = isset($_GET['token']) ? strtolower($_GET['token']) : null;
$value = isset($_GET['value']) ? $_GET['value'] : null;

// Token validation: Allowing only alphanumeric characters
if ($token && !preg_match('/^[a-zA-Z0-9]+$/', $token)) {
	die("Invalid token format.");
}

// GET mode: Retrieve the value for the token
if ($mode === 'get') {
	$sql = "SELECT value FROM ". TABLE_NAME ." WHERE token = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $token);
	$stmt->execute();

	$result = $stmt->get_result();

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		// echo "Retrieved value for token \"$token\": " . $row['value'];
		// header('Content-Type: application/json');
		echo $row['value'];

	} else {
		die("Error: Token \"$token\" not found.");
	}
}
// SET mode: Set the value for the token
else if ($mode === 'set') {
	if (!isset($_GET['token']) || !isset($_GET['value'])) {
		die("Both token and value parameters are required for 'set' mode.");
	}

	$sql = "SELECT * FROM ". TABLE_NAME ." WHERE token = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $token);
	$stmt->execute();

	$result = $stmt->get_result();

	if ($result->num_rows > 0) {
		// If token exists, update the value
		$sql = "UPDATE ". TABLE_NAME ." SET value = ? WHERE token = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss", $value, $token);
		if ($stmt->execute()) {
			// header('Content-Type: application/json');
			echo $value;
		} else {
			echo "Error updating record: " . $conn->error;
		}

	} else {
		// If token doesn't exist, insert a new record
		$sql = "INSERT INTO ". TABLE_NAME ." (token, value) VALUES (?, ?)";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss", $token, $value);
		if ($stmt->execute()) {
			// echo "Stored token \"$token\" with value \"$value\"";
			// header('Content-Type: application/json');
			echo $value;
		} else {
			echo "Error: " . $conn->error;
		}
	}
}
else {
	die("Mode parameter is required, use 'get' or 'set'.");
}

// Close the database connection
$conn->close();

?>

<?php
ob_start(); // Enable output buffering

require_once "config.php";

// Create a connection to the database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check the connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

// Detect mode ( get || send )
if (!isset($_GET['mode'])) {
	die("Mode parameter is required, use 'get' or 'send'.");
}
$mode = strtolower($_GET['mode']); // ignore case, convert to lowercase

// Set correct headers for response
http_response_code(200); // Set the status code to 200 (OK)
header("Access-Control-Allow-Origin: *"); // Allow cross-origin requests
header('Content-Type: text/plain'); // Set the type of response to plain text
// header('Content-Type: application/json'); // Set the type of response to JSON

// Validation for token and value (Add these checks before using them in queries)
$token = isset($_GET['token']) ? strtolower($_GET['token']) : null;
$value = isset($_GET['value']) ? $_GET['value'] : null;

ob_end_clean(); // Clean the output buffer after ob_start(), ensures no extra whitespace etc. is sent

// Token validation: Allowing only alphanumeric characters
if ($token && !preg_match('/^[a-zA-Z0-9]+$/', $token)) {
	die("Invalid token format.");
}

// GET mode: Retrieve the value for the token
if ($mode === 'get') {
	if (!isset($_GET['token'])) {
		die("Token parameter is required for 'get' mode.");
	}
	$token = strtolower($_GET['token']); // ignore case, convert to lowercase

	$sql = "SELECT value FROM ".TABLE_NAME." WHERE token = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $token);
	$stmt->execute();

	// Bind the result variable
	$stmt->bind_result($resultValue);

	// Fetch the result
	if ($stmt->fetch()) {
		$response = [
			'token' => $token,
			'value' => $resultValue
		];

		echo $response["value"];
		echo "\n";

	} else {
		$response = ['error' => 'Token not found'];
		echo $response["error"];
	}
}
// SET mode: Set the value for the token
else if ($mode === 'send') {
	if (!isset($_GET['token']) || !isset($_GET['value'])) {
		die("Both token and value parameters are required for 'send' mode.");
	}

	$token = strtolower($_GET['token']); // ignore case, convert to lowercase

	$sql = "SELECT value FROM ".TABLE_NAME." WHERE token = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $token);
	$stmt->execute();

	// Bind the result variable
	$stmt->bind_result($resultValue);

	// Fetch the result
	$stmt->fetch();

	// Free it for next db request
	$stmt->free_result();

	if ($resultValue !== null) {
		// If token exists, update the value
		$sql = "UPDATE ". TABLE_NAME ." SET value = ? WHERE token = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss", $value, $token);
		if ($stmt->execute()) {
			echo $value;
			echo "\n";

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
			echo $value;
			echo "\n";

		} else {
			echo "Error: " . $conn->error;
		}
	}
} else {
	die("Invalid mode parameter, use 'get' or 'send'.");
}
?>
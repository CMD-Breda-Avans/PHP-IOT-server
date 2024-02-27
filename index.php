<?php
ob_start(); // Enable output buffering ghjghkgh

/* Load settings */
require_once "config.php";

/* Init body */
$body = "";

/* Init database connection */
// Create a connection to the database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check the connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

// Detect mode ( get || send )
if (!isset($_GET["mode"])) {
	die("Mode parameter is required, use 'get' or 'send'.");
}
$mode = strtolower($_GET["mode"]); // ignore case, convert to lowercase

// Validation for token and value (Add these checks before using them in queries)
$token = isset($_GET["token"]) ? strtolower($_GET["token"]) : null;
$value = isset($_GET["value"]) ? $_GET["value"] : null;

// Check if the entered token is in the allowed tokens array
if (!in_array($token, $allowedTokens)) {
	die("Error: Invalid token");
}

// Check for data type
$type = isset($_GET["type"]) ? strtolower($_GET["type"]) : "text"; // ignore case, convert to lowercase
$typeString = "text/plain"; // assume text
if ($type == "json") { // check for json
	$typeString = "application/json";
}

// GET mode: Retrieve the value for the token
if ($mode === "get") {
	if (!isset($_GET["token"])) {
		die("Token parameter is required for 'get' mode.");
	}
	$token = strtolower($_GET["token"]); // ignore case, convert to lowercase

	$sql = "SELECT value FROM ".TABLE_NAME." WHERE token = ?";
	$stmt = $conn->prepare($sql);
	$stmt->bind_param("s", $token);
	$stmt->execute();

	// Bind the result variable
	$stmt->bind_result($resultValue);

	// Fetch the result
	if ($stmt->fetch()) {
		$response = [
			"token" => $token,
			"value" => $resultValue
		];

		$body = $response["value"];
		// echo "\n";

	} else {
		$response = ["error" => "Token not found"];
		$body = $response["error"];
	}
}
// SET mode: Set the value for the token
else if ($mode === "send") {
	if (!isset($_GET["token"]) || !isset($_GET["value"])) {
		die("Both token and value parameters are required for 'send' mode.");
	}

	$token = strtolower($_GET["token"]); // ignore case, convert to lowercase

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
			$body = $value;
			// echo "\n";
		} else {
			die("Error updating record: " . $conn->error);
		}

	} else {
		// If token doesn't exist, insert a new record
		$sql = "INSERT INTO ". TABLE_NAME ." (token, value) VALUES (?, ?)";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("ss", $token, $value);
		if ($stmt->execute()) {
			// echo "Stored token \"$token\" with value \"$value\"";
			$body = $value;
			// echo "\n";

		} else {
			die("Error: " . $conn->error);
		}
	}
} else {
	die("Invalid mode parameter, use 'get' or 'send'.");
}
// Close the database connection
$conn->close();

/* Construct output */

// Set correct headers for response
http_response_code(200); // Set the status code to 200 (OK)
header("Access-Control-Allow-Origin: *"); // Allow cross-origin requests
header("Content-Length: " . strlen($body) ); // Calculate length and add to headers
header("Content-Type: " . $typeString); // Set the type of response to plain text or json

ob_end_flush(); // collects all output in a buffer. reduces extra characters
echo $body;


?>
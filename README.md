# PHP-IOT-server
Used for storing IoT-data in Integrated Smart Systems (ISS) module. Allows you to store and retrieve variables in an SQL database via HTTP requests.

## Requirements

- PHP
- MySQL/MariaDB
- Web server (Apache, Nginx, etc.)

## Setup

1. Clone this repository.
2. Create a database in MySQL/MariaDB to store IoT data.
3. Rename `config.blank.php` to `config.php` file and fill out your database credentials.
4. 📡 ··· 🛰️

## Usage
The server accepts HTTP requests with the following format:

- To store a variable: `https://yourserver.com/set/token/value` (Use mode 'set')
    - Example: `https://yourserver.com/set/abc123/0.001`

- To retrieve a variable: `https://yourserver.com/get/token` (Use mode 'get')
    - Example: `https://yourserver.com/get/abc`

- Never share sensitive or personal information

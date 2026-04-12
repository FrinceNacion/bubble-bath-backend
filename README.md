# Bubble Bath Backend

## Project Overview
This is a simple PHP-based backend API for a "bubble bath" application, focused on user authentication and management. It provides RESTful endpoints for user registration, login, logout, and retrieving user details. The API uses MySQL for data storage, PHP sessions for authentication state, and includes CORS headers for integration with a frontend (likely running on `localhost:5173`). It handles JSON requests/responses, password hashing for security, and basic error handling.

**How it works**:
- Clients send POST requests with JSON data to endpoints like `/register.php` or `/login.php`.
- The API connects to a MySQL database (`bubble_bath_db`) via PDO, performs operations (e.g., inserting users, verifying credentials), and manages sessions for logged-in users.
- Authentication is session-based; endpoints like `/get_user.php` check for active sessions.
- Responses are JSON-formatted, with success/error messages.

**Structure**:
- **index.php**: Basic API status indicator.
- **connect_db.php**: Database connection setup (MySQL, host: 127.0.0.1, user: root, db: bubble_bath_db).
- **register.php**: Handles user registration (checks for duplicates, hashes passwords, inserts into `users` table).
- **login.php**: Verifies credentials, starts sessions on success.
- **logout.php**: Destroys user sessions.
- **get_user.php**: Returns authenticated user data from session.
- **authenticate.php**: Middleware for checking authentication status.

The `users` table (assumed to exist) includes fields like `user_id`, `name`, `email`, `password` (hashed), `role`, and `deleted_at` for soft deletes.

## Setup Instructions
1. **Install and Start XAMPP**: Ensure XAMPP is installed on your macOS system. Open the XAMPP control panel and start Apache and MySQL services.
2. **Database Setup**: Open phpMyAdmin (via XAMPP at `http://localhost/phpmyadmin`) and create a database named `bubble_bath_db`. Create a `users` table with columns: `user_id` (INT, AUTO_INCREMENT, PRIMARY KEY), `name` (VARCHAR), `email` (VARCHAR, UNIQUE), `password` (VARCHAR), `role` (VARCHAR, optional), `deleted_at` (TIMESTAMP, NULL).
3. **Place Files**: The project is already in `/Applications/XAMPP/xamppfiles/htdocs/bubble-bath-backend` (your workspace). No further placement needed.
4. **Access the API**: Visit `http://localhost/bubble-bath-backend/` in a browser or use tools like Postman/cURL to test endpoints (e.g., POST to `http://localhost/bubble-bath-backend/register.php` with JSON: `{"name":"test","email":"test@example.com","password":"pass"}`).
5. **Frontend Integration**: If connecting to a frontend on `localhost:5173`, ensure CORS is configured as in the code.

For issues, check PHP/MySQL errors in XAMPP logs or browser console. The API requires PHP with PDO and session support (standard in XAMPP).
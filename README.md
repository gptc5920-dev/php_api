# Clean API (PHP MVC)

A lightweight PHP MVC-style REST API for authentication and full users CRUD.

## Project Structure

```text
api/
+-- app/
�   +-- Controllers/
�   �   +-- ApiController.php
�   �   +-- AuthController.php
�   �   +-- UserController.php
�   +-- Core/
�   �   +-- Controller.php
�   �   +-- Database.php
�   �   +-- Request.php
�   �   +-- Response.php
�   +-- Models/
�       +-- User.php
+-- bootstrap.php
+-- config.php
+-- index.php
+-- login.php
+-- register.php
+-- users.php
```

## Requirements

- XAMPP / Apache
- PHP 8+
- MySQL (local)

## Quick Start

1. Place project in `C:\xampp\htdocs\api`.
2. Start `Apache` and `MySQL` in XAMPP.
3. Open:
   - `GET http://localhost/api/`

The app auto-creates database/table on first DB access:

- Database: `clean_api`
- Table: `users`

## Configuration

Edit [config.php](./config.php):

- `db.host`
- `db.name`
- `db.user`
- `db.pass`
- `cors.*`
- `auth.allowed_roles`
- `auth.default_role`
- `auth.min_name_length`
- `auth.min_password_length`

## API Endpoints

Base URL: `http://localhost/api`

### API Info

- `GET /`
- Description: API metadata and endpoint list.

### Auth

- `POST /login.php`
- Body (JSON):
  - `email` (required)
  - `password` (required)

- `GET /register.php`
- Description: registration metadata/example.

- `POST /register.php`
- Body (JSON):
  - `name` (required)
  - `email` (required)
  - `password` (required)
  - `role` (optional: `admin|staff`, default `staff`)
  - `confirm_password` (optional)

### Users CRUD

- `GET /users.php`
- Description: list users.

- `GET /users.php?id={id}`
- Description: fetch a single user.

- `POST /users.php`
- Body (JSON):
  - `name` (required)
  - `email` (required)
  - `password` (required)
  - `role` (optional)

- `PUT /users.php?id={id}` or `PATCH /users.php?id={id}`
- Body (JSON, any of):
  - `name`
  - `email`
  - `password`
  - `role`

- `DELETE /users.php?id={id}`
- Description: delete user.

## Response Format

All responses are JSON:

```json
{
  "status": 200,
  "path": "/api/users.php",
  "success": true,
  "message": "...",
  "data": {}
}
```

Error responses use the same shape with `success: false`.

## Example Requests (PowerShell)

### Register

```powershell
$body = @{
  name = "John Doe"
  email = "john@example.com"
  role = "staff"
  password = "secret123"
  confirm_password = "secret123"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost/api/register.php" -Method POST -ContentType "application/json" -Body $body
```

### Login

```powershell
$body = @{ email = "john@example.com"; password = "secret123" } | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost/api/login.php" -Method POST -ContentType "application/json" -Body $body
```

### Create User (CRUD)

```powershell
$body = @{ name = "Jane"; email = "jane@example.com"; password = "secret123"; role = "staff" } | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost/api/users.php" -Method POST -ContentType "application/json" -Body $body
```

### Update User

```powershell
$body = @{ name = "Jane Updated"; role = "admin" } | ConvertTo-Json
Invoke-RestMethod -Uri "http://localhost/api/users.php?id=1" -Method PUT -ContentType "application/json" -Body $body
```

### Delete User

```powershell
Invoke-RestMethod -Uri "http://localhost/api/users.php?id=1" -Method DELETE
```



## Postman API Testing



### 1. Create Environment

In Postman, create an environment (for example `Local API`) with:

- `base_url` = `http://localhost/api`
- `user_id` = `1` (update this after creating a user)

### 2. Common Request Setup

- Method: choose based on endpoint
- URL format: `{{base_url}}/users.php` (or other endpoint)
- Header: `Content-Type: application/json`
- Body: `raw` -> `JSON`

### 3. Test Flow (Recommended)

1. Register
- `POST {{base_url}}/register.php`
- Body:

```json
{
  "name": "John Postman",
  "email": "john.postman@example.com",
  "password": "secret123",
  "confirm_password": "secret123",
  "role": "staff"
}
```

2. Login
- `POST {{base_url}}/login.php`
- Body:

```json
{
  "email": "john.postman@example.com",
  "password": "secret123"
}
```

3. Create User
- `POST {{base_url}}/users.php`
- Body:

```json
{
  "name": "Jane Postman",
  "email": "jane.postman@example.com",
  "password": "secret123",
  "role": "staff"
}
```

4. Get All Users
- `GET {{base_url}}/users.php`


5. Get Single User
- `GET {{base_url}}/users.php?id={{user_id}}`

6. Update User
- `PUT {{base_url}}/users.php?id={{user_id}}`
- Body:

```json
{
  "name": "Jane Updated",
  "role": "admin"
}
```

7. Delete User
- `DELETE {{base_url}}/users.php?id={{user_id}}`

### 4. Optional Postman Tests

Add this in the Tests tab to quickly validate responses:

```javascript
pm.test("Status code is 2xx", function () {
  pm.expect(pm.response.code).to.be.within(200, 299);
});

const json = pm.response.json();
pm.test("Has API response shape", function () {
  pm.expect(json).to.have.property("status");
  pm.expect(json).to.have.property("success");
  pm.expect(json).to.have.property("message");
});
```

## Notes

- CORS headers are applied globally from `bootstrap.php`.
- Invalid JSON payloads return `400`.


## Front-End

The front-end is API-focused and lives in `front-end/` with these pages:

- `front-end/index.html` (home)
- `front-end/register.html` (register form)
- `front-end/login.html` (login form)
- `front-end/dashboard.html` (users CRUD)

Assets:

- `front-end/css/styles.css`
- `front-end/js/app.js`


Open in browser:

- `http://localhost/api/front-end/index.html`

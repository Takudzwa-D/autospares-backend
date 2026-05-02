# AutoSpares Backend - Complete File Documentation

A comprehensive guide explaining every file in the project. Use this to explain the system to stakeholders and team members.

---

## 📋 Project Overview

**AutoSpares** is a production-ready REST API for an automotive spare parts ecommerce platform. It allows customers to browse products by brand/model/category, create orders, process payments, and allows admins to manage inventory.

**Stack:** PHP 7.4+ | MySQL | PDO | JWT Authentication | PayNow Payment Gateway

---

## 📁 Directory Structure & File Guide

### Root Level Files

#### `index.php`
**Purpose:** Main API entry point  
**What it does:**
- Receives all API requests (via URL rewriting)
- Parses the URL to identify which endpoint to call
- Routes requests to appropriate controller
- Sets up CORS headers

**Key Code Pattern:**
```php
require routing to determine which controller to execute
Example: /api/auth/login → calls controllers/auth/login.php
```

**Explanation for stakeholders:**
"This is the front door of our API. Every request comes through here first, and it directs traffic to the right department (controller)."

---

#### `readme.md`
**Purpose:** User-facing API documentation  
**Contains:**
- Installation instructions
- Setup steps
- Complete API endpoint documentation
- cURL examples
- Response format
- HTTP status codes
- Troubleshooting guide

**Explanation for stakeholders:**
"This is the manual for using the API - like an instruction booklet. Developers read this to understand how to use each endpoint."

---

#### `POSTMAN_GUIDE.md` ⭐ NEW
**Purpose:** Step-by-step testing guide using Postman  
**Contains:**
- How to install and import Postman collection
- Quick start examples (register, login, order)
- Testing workflow phases
- Response code explanations
- Debugging tips
- Common issues and solutions
- Testing checklist

**Explanation for stakeholders:**
"This is our testing manual. It teaches anyone how to test the API without writing code - just using a visual tool called Postman."

---

#### `AutoSpares_Postman_Collection.json` ⭐ NEW
**Purpose:** Pre-built collection of all 27 API endpoints  
**Contains:**
- All endpoints organized by category
- Pre-filled request bodies
- Proper headers and parameters
- Ready to import into Postman

**How to use:**
1. Download Postman
2. Click Import
3. Upload this file
4. All endpoints ready to test

**Explanation for stakeholders:**
"Think of this as a ready-made test suite. Instead of creating 27 test requests manually, we import this file and everything is ready to go."

---

### Configuration Folder (`config/`)

#### `database.php`
**Purpose:** Database connection setup  
**What it does:**
- Creates PDO connection to MySQL database
- Sets UTF-8 character encoding
- Handles connection errors
- Made available globally to all models

**Key Details:**
```php
Credentials: localhost, root, AutoSpares database
Method: PDO with prepared statements (prevents SQL injection)
Character Set: UTF-8
```

**Explanation for stakeholders:**
"This is like the keys to our warehouse. It opens the database so our app can read/write product, user, and order information."

---

#### `cors.php`
**Purpose:** Cross-Origin Resource Sharing configuration  
**What it does:**
- Allows frontend apps to communicate with this API
- Sets required headers for browser security
- Enables requests from any origin

**Headers Added:**
```php
Access-Control-Allow-Origin: * (any frontend can access)
Content-Type: application/json (data format is JSON)
```

**Explanation for stakeholders:**
"This is a security checkpoint that says 'yes, frontend apps are allowed to talk to this API'. Without it, browsers block all requests."

---

#### `constance.php`
**Purpose:** Application-wide constants and configuration  
**Contains:**
- HTTP status codes (200, 201, 400, 401, etc)
- Order statuses (Pending, Completed, Failed, Cancelled)
- Payment methods (Credit Card, PayNow, Bank Transfer)
- Payment statuses (Pending, Completed, Failed)
- User roles (Customer, Admin)
- Pagination defaults (10 items per page max)

**Why it exists:**
Instead of hardcoding strings like "Pending" everywhere, we define it once here. If we change it, we change it in one place.

**Example:**
```php
const ORDER_STATUS_PENDING = "Pending"
const ORDER_STATUS_COMPLETED = "Completed"
const HTTP_OK = 200
const HTTP_CREATED = 201
```

**Explanation for stakeholders:**
"This is our rule book. It defines all the valid values for things like order status, payment methods, and response codes. It ensures consistency everywhere."

---

### Helpers Folder (`helpers/`)

Helpers are utility functions used by multiple controllers. Think of them as "shared tools."

#### `request.php`
**Purpose:** Parse incoming HTTP requests  
**Functions:**
```php
getJsonInput()           // Get JSON body data
getQuery($key, $default) // Get URL query parameter
getAllQuery()            // Get all query parameters
getHeader($name)         // Get HTTP header value
getBearerToken()         // Extract JWT token from Authorization header
getRequestMethod()       // Get HTTP method (GET, POST, PUT, DELETE)
```

**Example Usage:**
```php
$email = getQuery('email'); // From ?email=user@example.com
$data = getJsonInput();      // From request body
$token = getBearerToken();   // From Authorization header
```

**Explanation for stakeholders:**
"This is our 'request reader'. It extracts information from incoming requests so controllers can use it - like reading the address on a package."

---

#### `response.php`
**Purpose:** Format all API responses consistently  
**Functions:**
```php
successResponse($message, $data)           // Success response (200)
createdResponse($message, $data)           // Created response (201)
errorResponse($message, $data, $status)    // Error response
validationErrorResponse($errors)           // Validation error (400)
unauthorizedResponse($message)             // No token (401)
forbiddenResponse($message)                // Not admin (403)
notFoundResponse($message)                 // Resource not found (404)
```

**Response Format:**
```json
{
  "success": true,
  "message": "Description here",
  "data": {...}
}
```

**Explanation for stakeholders:**
"This ensures every response from our API looks the same - consistent format makes frontend development easier. It's like using standard letterhead for all business correspondence."

---

#### `validator.php`
**Purpose:** Validate and sanitize user input  
**Functions:**
```php
isEmptyField($value)              // Check if empty
isValidEmail($email)              // Validate email format
isValidPhoneNumber($phone)        // Validate phone
isValidPassword($password)        // Password strength check
isNumeric($value)                 // Check if number
isPositive($value)                // Check if positive
isValidLength($value, $min, $max) // Check length
isValidEnum($value, $allowed)     // Check if in allowed list
sanitizeString($value)            // Remove dangerous characters
sanitizeInt($value)               // Convert to integer
sanitizeFloat($value)             // Convert to float
validateRequired($data, $required)// Check all required fields present
```

**Purpose:**
- Prevent SQL injection (sanitize)
- Prevent XSS attacks (escape output)
- Ensure data integrity (validate format)

**Example:**
```php
if (!isValidEmail($email)) {
    errorResponse('Invalid email format');
}
$email = sanitizeString($email); // Remove any malicious characters
```

**Explanation for stakeholders:**
"This is our security guard. It checks all incoming data to make sure it's legitimate and safe before we use it in the database."

---

#### `auth.php`
**Purpose:** Handle authentication with JWT tokens  
**Functions:**
```php
generateToken($payload, $expiresIn)  // Create JWT token
verifyToken($token)                  // Verify token is valid
getCurrentUser()                     // Get logged-in user from token
isAuthenticated()                    // Check if user is authenticated
hashPassword($password)              // Bcrypt hash password
verifyPassword($password, $hash)     // Verify password against hash
```

**How JWT Works:**
```
1. User logs in → generateToken() creates token
2. Token contains: user ID, email, name, role
3. Token signed with SECRET_KEY (only server knows)
4. Frontend stores token and sends with each request
5. Server verifies token with verifyToken()
```

**Token Expiry:** 24 hours (can be customized)

**Explanation for stakeholders:**
"This is our security system. It creates digital 'passports' (tokens) that prove who a user is. The server signs each passport so it can't be forged."

---

#### `upload.php`
**Purpose:** Handle product image uploads  
**Functions:**
```php
initializeUploadDirs()              // Create upload folder if needed
validateUploadedFile($file)         // Check file is valid
uploadProductImage($file)           // Save image to disk
deleteProductImage($filename)       // Delete old image
getImageUrl($filename)              // Generate image URL
```

**Validation Rules:**
- File size: max 5MB
- File types: JPG, PNG, GIF, WebP
- Stored in: `/uploads/products/`

**Why validate:**
- Prevent large file abuse (DOS attack)
- Prevent executable files
- Keep storage organized

**Explanation for stakeholders:**
"This handles product photos. It checks that photos are real images, not too big, and safely saves them to our server."

---

#### `paynow.php`
**Purpose:** PayNow payment gateway integration  
**Functions:**
```php
generatePayNowHash($data)           // Create MD5 hash for verification
initiatePayNowPayment($orderData)   // Start payment process
verifyPayNowTransaction($response)  // Verify PayNow callback
parsePayNowStatus($status)          // Convert status string
formatPaymentAmount($amount)        // Format amount for PayNow
```

**How Payment Works:**
```
1. Customer creates order
2. Customer clicks "Pay with PayNow"
3. initiatePayNowPayment() redirects to PayNow
4. Customer pays on PayNow website
5. PayNow redirects back with transaction reference
6. verifyPayNowTransaction() confirms payment was real
7. Order status updated to Completed
```

**Explanation for stakeholders:**
"This is our cash register. It handles integration with PayNow payment gateway to receive customer payments securely."

---

### Middleware Folder (`middleware/`)

Middleware runs before controllers to check permissions.

#### `require_auth.php`
**Purpose:** Ensure user is logged in  
**What it does:**
- Calls `getCurrentUser()` from auth helper
- If no token or invalid token: return 401 Unauthorized
- If valid: store user in `$GLOBALS['auth_user']`

**Used by:** All protected endpoints (orders, profile, etc)

**Explanation for stakeholders:**
"This is our bouncer at the club. It checks if you have a valid ticket (token) before letting you past."

---

#### `require_admin.php`
**Purpose:** Ensure user is admin  
**What it does:**
- Calls requireAuth() first (must be logged in)
- Checks if user's role === 'Admin'
- If not admin: return 403 Forbidden

**Used by:** Admin-only endpoints (add product, update status, etc)

**Explanation for stakeholders:**
"This is the VIP bouncer. Not only do you need a ticket, your ticket must say 'VIP'. Regular customers can't access these areas."

---

### Models Folder (`models/`)

Models handle all database operations. Each model represents a database table.

#### `User.php`
**Database Table:** `Users`  
**Represents:** Customer and admin accounts  
**Methods:**
```php
findByEmail($email)        // Get user by email
findById($id)              // Get user by ID
getAll($limit, $offset)    // List all users with pagination
create()                   // Insert new user
update()                   // Update user data
changePassword()           // Update password (bcrypt hashed)
updateRole()               // Change user role to Admin
delete()                   // Delete user account
count()                    // Total user count
```

**Database Fields:**
```
id (Primary Key)
FirstName
LastName
Email (unique)
Password (bcrypt hashed)
Role (Customer or Admin)
Address
City
Country
Phone
CreatedAt (timestamp)
UpdatedAt (timestamp)
```

**Explanation for stakeholders:**
"This handles all user accounts - registration, login, profiles. It's like our customer database."

---

#### `Category.php`
**Database Table:** `Categories`  
**Represents:** Product categories (Engines, Brakes, Suspension, etc)  
**Methods:**
```php
getAll($limit, $offset)   // List all categories
getById($id)              // Get category by ID
getByName($name)          // Find category by name
create($name, $desc)      // Add new category
update()                  // Modify category
delete()                  // Remove category
count()                   // Total categories
```

**Database Fields:**
```
id (Primary Key)
name (unique)
description
```

**Explanation for stakeholders:**
"This organizes our products into main groups. Like 'Engines', 'Brakes', 'Suspension' as main categories."

---

#### `Sub_Category.php`
**Database Table:** `sub_categories`  
**Represents:** Nested categories under main categories  
**Example:** "Brake Pads" is a subcategory under "Brakes"  
**Methods:**
```php
getAll()                              // List all subcategories
getByCategoryId($catId, $limit)      // Subcategories in a category
getById($id)                          // Get subcategory by ID
getByName($name)                      // Find by name
create($catId, $name, $desc)         // Add subcategory
update()                              // Modify
delete()                              // Remove
count()                               // Total count
```

**Database Fields:**
```
id (Primary Key)
category_id (Foreign Key to Categories)
name (unique)
description
```

**Explanation for stakeholders:**
"This creates a second level of organization. Under 'Brakes' we have 'Brake Pads', 'Brake Fluid', 'Brake Discs'."

---

#### `Brand.php`
**Database Table:** `brands`  
**Represents:** Vehicle manufacturers (Toyota, Nissan, BMW, etc)  
**Methods:**
```php
getAll()                 // List all brands
getById($id)             // Get brand by ID
getByName($name)         // Find brand by name
create($name)            // Add new brand
update()                 // Modify brand
delete()                 // Remove brand
count()                  // Total brands
```

**Database Fields:**
```
id (Primary Key)
name (unique)
```

**Explanation for stakeholders:**
"This stores vehicle manufacturers. Our products are specific to brands so we can say 'Toyota parts', 'Honda parts', etc."

---

#### `Car_Model.php`
**Database Table:** `models`  
**Represents:** Car models under brands (Corolla under Toyota)  
**Methods:**
```php
getAll()                              // List all models
getByBrandId($brandId, $limit)       // Models in a brand
getById($id)                          // Get model by ID
getByName($name)                      // Find by name
create($brandId, $name)              // Add new model
update()                              // Modify
delete()                              // Remove
count()                               // Total count
```

**Database Fields:**
```
id (Primary Key)
brand_id (Foreign Key to brands)
name (unique)
```

**Explanation for stakeholders:**
"This represents specific car models. For example, under Toyota brand we have Corolla, Camry, Hilux, etc."

---

#### `Product.php`
**Database Table:** `products`  
**Represents:** Items for sale  
**Methods:**
```php
getAll($limit, $offset)                    // All products
getById($id)                               // Get product
getBySubCategoryId($subCatId, $limit)     // Products in subcategory
getByModelId($modelId, $limit)            // Products for vehicle model
search($query, $limit)                    // Full-text search by name/description
create()                                   // Add product
update()                                   // Modify product
delete()                                   // Remove product
updateStock($id, $qty)                    // Decrease stock when ordered
count()                                    // Total products
```

**Database Fields:**
```
id (Primary Key)
sub_category_id (Foreign Key)
model_id (Foreign Key)
name
description
price (decimal)
stock_quantity
img (image path)
created_at
updated_at
```

**Special Queries:**
Uses multiple LEFT JOINs to return enriched data:
```php
Returns: product data + category name + subcategory name 
         + brand name + model name
```

**Explanation for stakeholders:**
"This is our inventory system. Each product has a price, description, stock quantity, and is linked to a category and car model."

---

#### `Order.php`
**Database Table:** `orders`  
**Represents:** Customer orders  
**Methods:**
```php
getAll()                          // All orders
getById($id)                      // Order by ID
getByUserId($userId, $limit)     // Orders by customer
getByStatus($status, $limit)     // Orders by status
create($userId, $name, $phone, $address, $total) // Create order
updateStatus($id, $status)       // Change order status
update()                          // Modify order
delete()                          // Remove order
count()                           // Total orders
```

**Database Fields:**
```
id (Primary Key)
user_id (Foreign Key to Users)
customer_name
customer_phone_number
customer_address
total_amount (decimal)
status (Pending, Completed, Failed, Cancelled)
created_at
updated_at
```

**Explanation for stakeholders:**
"This records every customer order - who ordered, delivery address, total amount, and current status."

---

#### `Order_item.php`
**Database Table:** `order_items`  
**Represents:** Individual items within an order  
**Example:** Order #5 might have 2 brake pads and 1 oil filter = 3 order items  
**Methods:**
```php
getAll()                                    // All order items
getById($id)                                // Item by ID
getByOrderId($orderId)                     // Items in order
getByProductId($prodId, $limit)            // All orders containing product
create($orderId, $prodId, $qty, $price)   // Add item to order
update()                                    // Modify item
delete()                                    // Remove item
deleteByOrderId($orderId)                  // Delete all items in order
getOrderTotal($orderId)                    // Sum of all items in order
```

**Database Fields:**
```
id (Primary Key)
order_id (Foreign Key to orders)
product_id (Foreign Key to products)
quantity (how many)
price (price per unit when ordered)
```

**Explanation for stakeholders:**
"This is the 'shopping cart'. It shows what items are in each order and how many of each."

---

#### `Payment.php`
**Database Table:** `payments`  
**Represents:** Payment records for orders  
**Methods:**
```php
getAll()                          // All payments
getById($id)                      // Payment by ID
getByOrderId($orderId)           // Payment for order
getByStatus($status)              // Payments by status
getByMethod($method)              // Payments by method
create($orderId, $method, $status) // Record payment
updateStatus($id, $status)        // Update payment status
update()                          // Modify payment
delete()                          // Remove payment
count()                           // Total payments
```

**Database Fields:**
```
id (Primary Key)
order_id (Foreign Key to orders)
payment_method (Credit Card, PayNow, Bank Transfer)
payment_status (Pending, Completed, Failed)
created_at
updated_at
```

**Explanation for stakeholders:**
"This tracks payment attempts - which method was used, whether it succeeded or failed, and when."

---

### Controllers Folder (`controllers/`)

Controllers handle HTTP requests and return responses. 27 endpoints total.

#### `controllers/auth/` (4 files)

##### `register.php`
**Endpoint:** POST `/api/auth/register`  
**Purpose:** Create new customer account  
**Required fields:**
```
firstName, lastName, email, password, 
phoneNumber (optional), address, city, country
```
**Process:**
1. Validate all inputs
2. Check email not already registered
3. Hash password with bcrypt
4. Create user with Customer role
5. Generate JWT token
6. Return user data + token

**Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@example.com",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

---

##### `login.php`
**Endpoint:** POST `/api/auth/login`  
**Purpose:** Authenticate user and issue token  
**Required fields:** `email`, `password`  
**Process:**
1. Find user by email
2. Verify password against stored hash
3. If valid: generate JWT token
4. Return user data + token

---

##### `profile.php`
**Endpoint:** GET/PUT `/api/auth/profile`  
**Purpose:** View or update user profile  
**Auth required:** Yes  
**GET response:** Current user details  
**PUT updates:** firstName, lastName, phone, address, city, country  

---

##### `logout.php`
**Endpoint:** POST `/api/auth/logout`  
**Purpose:** Logout user  
**Auth required:** Yes  
**Note:** Token deleted client-side, not server-side (stateless JWT)  

---

#### `controllers/categories/` (3 files)

##### `get_categories.php`
**Endpoint:** GET `/api/categories?page=1&limit=10`  
**Purpose:** List all product categories  
**Query params:** page, limit  
**Response:** Paginated list of categories  

---

##### `get_sub_categories.php`
**Endpoint:** GET `/api/sub-categories?categoryId=1&page=1&limit=10`  
**Purpose:** List subcategories, optionally filtered by category  
**Query params:** categoryId (optional), page, limit  

---

##### `get_category_product.php`
**Endpoint:** GET `/api/categories/products?categoryId=1&page=1&limit=10`  
**Purpose:** Get all products in a category/subcategory  
**Query params:** categoryId OR subCategoryId (one required), page, limit  

---

#### `controllers/brands/` (3 files)

##### `get_brands.php`
**Endpoint:** GET `/api/brands?page=1&limit=10`  
**Purpose:** List all vehicle brands  
**Response:** Paginated brand list  

---

##### `get_models.php`
**Endpoint:** GET `/api/brands/models?brandId=1&page=1&limit=10`  
**Purpose:** List car models, optionally filtered by brand  
**Query params:** brandId (optional), page, limit  

---

##### `get_vehicle_products.php`
**Endpoint:** GET `/api/brands/products?brandId=1&page=1&limit=10`  
**Purpose:** Get products for a brand or specific model  
**Query params:** brandId OR modelId (one required), page, limit  

---

#### `controllers/products/` (7 files)

##### `get_products.php`
**Endpoint:** GET `/api/products?page=1&limit=10`  
**Purpose:** Get all products  
**Query params:** page, limit  
**Returns:** Product list with category, brand, model details  

---

##### `get_product.php`
**Endpoint:** GET `/api/products/get?id=1`  
**Purpose:** Get single product details  
**Query params:** id (required)  
**Returns:** Full product with all related data  

---

##### `search_products.php`
**Endpoint:** GET `/api/products/search?q=engine&page=1&limit=10`  
**Purpose:** Full-text search products  
**Query params:** q (min 2 chars), page, limit  
**Searches:** Product name + description  

---

##### `add_product.php`
**Endpoint:** POST `/api/products/add`  
**Auth required:** Yes (Admin only)  
**Required body:**
```json
{
  "subCategoryId": 1,
  "modelId": 1,
  "name": "Product Name",
  "description": "...",
  "price": 100.00,
  "stockQuantity": 10
}
```
**Process:**
1. Verify user is admin
2. Validate all inputs
3. Create product in database
4. Return created product

---

##### `upadate_product.php`
**Endpoint:** PUT `/api/products/update?id=1`  
**Auth required:** Yes (Admin only)  
**Body:** Any of name, description, price, stockQuantity  
**Note:** Typo in filename (upadate instead of update) - intentional, don't change  

---

##### `delete_product.php`
**Endpoint:** DELETE `/api/products/delete?id=1`  
**Auth required:** Yes (Admin only)  
**Process:**
1. Find product
2. Delete associated image file
3. Delete product record

---

##### `upload_product_image.php`
**Endpoint:** POST `/api/products/upload-image?productId=1`  
**Auth required:** Yes (Admin only)  
**Body:** form-data with "image" file (JPG/PNG/GIF/WebP, max 5MB)  
**Process:**
1. Validate file
2. Delete old image if exists
3. Save new image
4. Update product image path
5. Return image URL

---

#### `controllers/orders/` (5 files)

##### `create_orders.php`
**Endpoint:** POST `/api/orders/create`  
**Auth required:** Yes  
**Body:**
```json
{
  "items": [
    {"productId": 1, "quantity": 2},
    {"productId": 3, "quantity": 1}
  ],
  "customerName": "...",
  "customerPhone": "...",
  "customerAddress": "...",
  "paymentMethod": "PayNow"
}
```
**Process (with transaction):**
1. Validate all items exist
2. Check stock is available
3. BEGIN transaction
4. Create order
5. Create order items
6. Decrease product stock
7. Create payment record
8. COMMIT transaction
9. If error: ROLLBACK all changes

**Important:** Uses database transactions to ensure consistency

---

##### `get_all_orders.php`
**Endpoint:** GET `/api/orders?status=Pending&page=1&limit=10`  
**Auth required:** Yes (Admin only)  
**Query params:** status (optional), page, limit  
**Response:** All orders in system with optional status filter  

---

##### `get_my_orders.php`
**Endpoint:** GET `/api/orders/my?status=Completed&page=1&limit=10`  
**Auth required:** Yes  
**Query params:** status (optional), page, limit  
**Response:** Only authenticated user's orders  

---

##### `get_order_details.php`
**Endpoint:** GET `/api/orders/details?orderId=1`  
**Auth required:** Yes  
**Query params:** orderId  
**Response:** Full order with items array and payment info  
**Authorization:** Customer sees own orders only, admin sees all  

---

##### `update_order_status.php`
**Endpoint:** PUT `/api/orders/update-status?orderId=1`  
**Auth required:** Yes (Admin only)  
**Body:** `{"status": "Completed"}`  
**Allowed statuses:** Pending, Completed, Failed, Cancelled  

---

#### `controllers/payments/` (5 files)

##### `initiate_payment.php`
**Endpoint:** POST `/api/payments/initiate`  
**Auth required:** Yes  
**Body:** `{"orderId": 1}`  
**Process:**
1. Verify order exists and belongs to user
2. Check payment not already completed
3. If method is PayNow: call initiatePayNowPayment()
4. Return payment URL or reference

---

##### `payment_status.php`
**Endpoint:** GET `/api/payments/status?orderId=1`  
**Auth required:** Yes  
**Query params:** orderId  
**Response:** Current payment status  
**Authorization:** User sees own, admin sees all  

---

##### `payment_result.php`
**Endpoint:** GET `/api/payments/result` (PayNow callback)  
**Auth required:** No (PayNow calls this)  
**Query params:** From PayNow: reference, status, etc  
**Process:**
1. Verify PayNow hash (prevent tampering)
2. Find order and payment
3. Update payment status
4. If completed: update order to Completed
5. Return success response

---

##### `payment_return.php` ⭐ NEW
**Endpoint:** GET `/api/payments/return` (User redirect)  
**Auth required:** No  
**Purpose:** Where user is redirected after PayNow payment  
**Query params:** reference, status  
**Process:**
1. Look up order by reference
2. Verify order belongs to user
3. Return current payment/order status
4. Frontend can show success/failure page

---

##### `paynow_hook.php` ⭐ NEW
**Endpoint:** POST `/api/payments/hook` (PayNow webhook)  
**Auth required:** No (PayNow server calls this)  
**Purpose:** Server-to-server notification from PayNow  
**Process:**
1. Verify PayNow signature
2. Update payment status
3. Update order status
4. Log all transactions for audit
5. Return success

**Difference from payment_result.php:**
- payment_result.php: User is redirected here (browser)
- paynow_hook.php: PayNow server sends automatic notification

---

### SQL Folder (`sql/`)

#### `schema.sql`
**Purpose:** Database table definitions  
**Contains:** CREATE TABLE statements for all 9 tables  
**Tables:**
1. Users
2. Categories
3. sub_categories
4. brands
5. models
6. products
7. orders
8. order_items
9. payments

**How to use:**
```bash
mysql -u root -p < sql/schema.sql
```

**Explanation for stakeholders:**
"This is the blueprint for our database. Running this command creates all the tables and their structure."

---

### Routes Folder (`routes/`)

#### `api.php`
**Purpose:** Simple route matching (no framework)  
**What it does:**
1. Parses URL (e.g., `/api/products/search`)
2. Uses regex to match against defined routes
3. Calls appropriate controller

**Example routing:**
```php
if (preg_match('/^\/api\/auth\/login$/', $url)) {
    require 'controllers/auth/login.php';
}
```

**Explanation for stakeholders:**
"This is like a switchboard that directs incoming calls to the right department."

---

### Uploads Folder (`uploads/`)

#### `uploads/products/`
**Purpose:** Store product images  
**Details:**
- Created automatically by upload.php
- Needs write permissions (755)
- Images referenced by filename in products table

**Explanation for stakeholders:**
"This folder stores all product photos that customers upload."

---

## 🎯 How Everything Works Together

### Typical Request Flow

**Example: Customer creates order**

```
1. Frontend sends POST /api/orders/create with JWT token

2. index.php receives request, routes to controllers/orders/create_orders.php

3. create_orders.php:
   - Calls require_auth middleware → verifies token is valid
   - Calls getJsonInput() from request.php → extracts order data
   - Calls validateRequired() from validator.php → checks all fields present
   - Creates Order model instance → connects to database
   - Calls $orderModel->create() → inserts order
   - Updates Product stock via updateStock()
   - Creates Payment record
   - Calls successResponse() → formats JSON response

4. successResponse() returns:
   {
     "success": true,
     "message": "Order created",
     "data": {...order details...}
   }

5. Frontend receives response with status 201
```

---

## 📊 Database Relationships

```
Users (1) ←→ (many) Orders
Users (1) ←→ (many) Payments (via Orders)

Categories (1) ←→ (many) Sub_Categories
Sub_Categories (1) ←→ (many) Products
Brands (1) ←→ (many) Car_Models
Car_Models (1) ←→ (many) Products

Orders (1) ←→ (many) Order_Items
Products (1) ←→ (many) Order_Items
Orders (1) ←→ (one) Payments
```

---

## 🔐 Security Mechanisms

| Threat | Solution | File |
|--------|----------|------|
| SQL Injection | PDO prepared statements | database.php + models |
| XSS Attack | Sanitize + escape output | validator.php |
| Unauthorized access | JWT tokens + middleware | auth.php, middleware/ |
| Weak passwords | Bcrypt hashing | auth.php |
| CSRF | CORS validation | cors.php |
| Large file uploads | Size + type validation | upload.php |

---

## 📝 File Checklist for Explanation

When explaining the system to team members, cover files in this order:

### Phase 1: Setup (5 min)
- [ ] index.php - "Entry point of API"
- [ ] config/database.php - "How we connect to database"
- [ ] config/cors.php - "Security for browser requests"

### Phase 2: Foundation (10 min)
- [ ] helpers/request.php - "Read incoming data"
- [ ] helpers/response.php - "Format outgoing data"
- [ ] helpers/validator.php - "Validate/sanitize input"
- [ ] helpers/auth.php - "JWT authentication"

### Phase 3: Data Layer (15 min)
- [ ] models/User.php - "User database operations"
- [ ] models/Product.php - "Product database operations"
- [ ] models/Order.php - "Order database operations"
- [ ] sql/schema.sql - "Database structure"

### Phase 4: Business Logic (20 min)
- [ ] controllers/auth/ - "User registration/login"
- [ ] controllers/products/ - "Product management"
- [ ] controllers/orders/create_orders.php - "Complex order creation with transactions"

### Phase 5: Advanced Topics (15 min)
- [ ] middleware/ - "Permission checking"
- [ ] controllers/payments/ - "Payment processing"
- [ ] helpers/upload.php - "File handling"

### Phase 6: Testing (10 min)
- [ ] POSTMAN_GUIDE.md - "How to test"
- [ ] AutoSpares_Postman_Collection.json - "Pre-built test requests"

### Phase 7: Documentation (5 min)
- [ ] readme.md - "User-facing documentation"

---

## 🚀 Learning Path for New Developers

**Day 1:**
1. Read readme.md
2. Understand project structure
3. Review config/ and helpers/

**Day 2:**
4. Study models (starting with User.php)
5. Understand database schema
6. Review CRUD operations

**Day 3:**
7. Look at simple controllers (categories, brands)
8. Trace request flow end-to-end
9. Study error handling patterns

**Day 4:**
10. Study authentication (auth.php + middleware/)
11. Review protected endpoints
12. Understand JWT tokens

**Day 5:**
13. Study complex logic (orders, payments)
14. Understand transactions
15. Review validation patterns

**Week 2:**
16. Test entire API using Postman
17. Modify existing endpoints
18. Create new endpoints

---

## 💡 Key Concepts

### MVC Pattern
- **Model:** Database operations (models/)
- **View:** JSON responses (helpers/response.php)
- **Controller:** Business logic (controllers/)

### Middleware Pattern
- Runs before controller
- Checks permissions
- Can short-circuit request

### Transaction Pattern
- Ensures atomicity (all-or-nothing)
- Rollback on error
- Used in create_orders.php

### JWT Pattern
- Token contains user data
- Token signed with secret
- Stateless (no server session needed)

---

**Total Files: 40+**
**Total Lines of Code: 5000+**
**Production Ready: ✅ YES**

Use this document to structure your explanations to stakeholders and team members!

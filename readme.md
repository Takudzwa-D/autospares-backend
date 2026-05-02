# AutoSpares eCommerce Backend API

A production-ready REST API for an automotive parts ecommerce platform built with PHP, MySQL, and PDO.

## Overview

AutoSpares is a comprehensive backend API for selling automotive spare parts, organized by vehicle brand and model. It includes user authentication, product management, order processing, and payment gateway integration (PayNow).

## Database Structure

### Core Entities

```
Categories          Main groups (Engines, Spare Parts, Accessories)
    ├── Sub_Categories    Smaller groups (Brake Pads, Suspension)
    
Brands              Vehicle manufacturers (Toyota, Nissan)
    ├── Models       Brand-specific models (Corolla, Demio)
    
Products            Individual items for sale
    └── Links to: Sub_Category + Model + Image

Users               Customer & Admin accounts

Orders              Customer orders with status tracking
    ├── Order_Items  Line items in orders
    
Payments            Payment records & status
```

### Database Setup

1. Import the schema:
```bash
mysql -u root < sql/schema.sql
```

2. Create a `.env` file (optional, for production):
```env
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=AutoSpares
APP_ENV=production
JWT_SECRET=your-secret-key-here
PAYNOW_INTEGRATION_KEY=your-paynow-key
PAYNOW_ENCRYPTION_KEY=your-paynow-encryption-key
```

## Installation & Setup

### Requirements

- PHP 7.4+
- MySQL 5.7+
- PDO extension
- Web server (Apache/Nginx)

### Installation Steps

1. **Clone the repository**
   ```bash
   cd /var/www/html
   git clone <repository> ecommerce_backend
   ```

2. **Create database**
   ```bash
   mysql -u root -p < ecommerce_backend/sql/schema.sql
   ```

3. **Create uploads directory**
   ```bash
   mkdir -p ecommerce_backend/uploads/products
   chmod 755 ecommerce_backend/uploads
   chmod 755 ecommerce_backend/uploads/products
   ```

4. **Configure database connection**
   Edit `config/database.php` with your database credentials

5. **Test the API**
   ```bash
   curl http://localhost/ecommerce_backend/index.php
   ```

## API Endpoints

### Authentication

#### Register
- **POST** `/api/auth/register`
- **Body:**
```json
{
  "firstName": "John",
  "lastName": "Doe",
  "email": "john@example.com",
  "password": "password123",
  "phoneNumber": "0712345678",
  "address": "123 Main St",
  "city": "Harare",
  "country": "Zimbabwe"
}
```
- **Response:** Returns user data + JWT token

#### Login
- **POST** `/api/auth/login`
- **Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```
- **Response:** Returns user data + JWT token

#### Get Profile
- **GET** `/api/auth/profile`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** Returns authenticated user data

#### Update Profile
- **PUT** `/api/auth/profile`
- **Headers:** `Authorization: Bearer {token}`
- **Body:** Any of `firstName`, `lastName`, `phoneNumber`, `address`, `city`, `country`

#### Logout
- **POST** `/api/auth/logout`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** Logout successful message

### Categories

#### Get All Categories
- **GET** `/api/categories?page=1&limit=10`
- **Response:** List of categories with pagination

#### Get Sub-Categories
- **GET** `/api/sub-categories?categoryId=1&page=1&limit=10`
- **Optional query params:** `categoryId` to filter by parent category

#### Get Category Products
- **GET** `/api/categories/products?categoryId=1&page=1&limit=10`
- **Query params:** `categoryId` or `subCategoryId` (required)

### Brands & Models

#### Get All Brands
- **GET** `/api/brands?page=1&limit=10`

#### Get Models by Brand
- **GET** `/api/brands/models?brandId=1&page=1&limit=10`
- **Query params:** `brandId` (optional, get all if not provided)

#### Get Products by Brand/Model
- **GET** `/api/brands/products?brandId=1&page=1&limit=10`
- **Query params:** `brandId` or `modelId` (one required)

### Products

#### Get All Products
- **GET** `/api/products?page=1&limit=10`

#### Get Single Product
- **GET** `/api/products/get?id=1`

#### Search Products
- **GET** `/api/products/search?q=engine&page=1&limit=10`
- **Query params:** `q` (search query, min 2 chars)

#### Add Product (Admin Only)
- **POST** `/api/products/add`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Body:**
```json
{
  "subCategoryId": 2,
  "modelId": 3,
  "name": "Toyota Corolla 1NZ Engine",
  "description": "Used engine in good condition",
  "price": 450.00,
  "stockQuantity": 5
}
```

#### Update Product (Admin Only)
- **PUT** `/api/products/update?id=1`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Body:** Any of `name`, `description`, `price`, `stockQuantity`

#### Delete Product (Admin Only)
- **DELETE** `/api/products/delete?id=1`
- **Headers:** `Authorization: Bearer {admin_token}`

#### Upload Product Image (Admin Only)
- **POST** `/api/products/upload-image?productId=1`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Form Data:** `image` (multipart file)
- **Allowed types:** JPG, PNG, GIF, WebP (max 5MB)

### Orders

#### Get All Orders (Admin Only)
- **GET** `/api/orders?status=Pending&page=1&limit=10`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Query params:** `status` (optional: Pending, Completed, Failed, Cancelled)

#### Get My Orders
- **GET** `/api/orders/my?status=Completed&page=1&limit=10`
- **Headers:** `Authorization: Bearer {token}`
- **Query params:** `status` (optional)

#### Get Order Details
- **GET** `/api/orders/details?orderId=1`
- **Headers:** `Authorization: Bearer {token}`
- **Response:** Order info + items + payment details

#### Create Order
- **POST** `/api/orders/create`
- **Headers:** `Authorization: Bearer {token}`
- **Body:**
```json
{
  "items": [
    {
      "productId": 1,
      "quantity": 2
    },
    {
      "productId": 3,
      "quantity": 1
    }
  ],
  "customerName": "John Doe",
  "customerPhone": "0712345678",
  "customerAddress": "123 Main St, Harare",
  "paymentMethod": "PayNow"
}
```
- **Response:** Order created with order ID + items + total amount
- **Auto-updates:** Product stock quantities reduced

#### Update Order Status (Admin Only)
- **PUT** `/api/orders/update-status?orderId=1`
- **Headers:** `Authorization: Bearer {admin_token}`
- **Body:**
```json
{
  "status": "Completed"
}
```
- **Allowed statuses:** Pending, Completed, Failed, Cancelled

### Payments

#### Initiate Payment
- **POST** `/api/payments/initiate`
- **Headers:** `Authorization: Bearer {token}`
- **Body:**
```json
{
  "orderId": 1
}
```
- **Response:** Payment URL (for PayNow) or reference

#### Get Payment Status
- **GET** `/api/payments/status?orderId=1`
- **Headers:** `Authorization: Bearer {token}`

#### Payment Result (PayNow Callback)
- **GET** `/api/payments/result?reference=...&status=...`
- **Automatic callback from PayNow**

#### PayNow Webhook Hook
- **POST** `/api/payments/hook`
- **Automatic webhook from PayNow**
- **Updates:** Payment status + Order status

## Response Format

All API responses follow this standardized format:

### Success Response (200-201)
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    ...response data...
  }
}
```

### Error Response (400-500)
```json
{
  "success": false,
  "message": "Error description",
  "data": null
}
```

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created |
| 400 | Bad Request - Invalid input |
| 401 | Unauthorized - Missing/invalid token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource not found |
| 409 | Conflict - Resource already exists |
| 500 | Internal Server Error |

## Authentication

The API uses JWT (JSON Web Tokens) for authentication.

### How to Use:

1. **Register or Login** to get a token
2. **Include token** in Authorization header:
   ```
   Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
   ```
3. **Token expires** after 24 hours
4. **Re-login** to get a new token

### User Roles

- **Customer**: Can view products, create orders, manage own orders
- **Admin**: Can manage products, categories, view all orders, update order status

## File Structure

```
ecommerce_backend/
├── index.php                 # API entry point
├── config/
│   ├── database.php         # Database connection
│   ├── cors.php             # CORS headers
│   └── constance.php        # Application constants
├── routes/
│   └── api.php              # Route definitions
├── controllers/
│   ├── auth/                # Authentication endpoints
│   ├── categories/          # Category endpoints
│   ├── brands/              # Brand/Model endpoints
│   ├── products/            # Product endpoints
│   ├── orders/              # Order endpoints
│   └── payments/            # Payment endpoints
├── models/
│   ├── User.php
│   ├── Category.php
│   ├── Sub_Category.php
│   ├── Brand.php
│   ├── Car_Model.php
│   ├── Product.php
│   ├── Order.php
│   ├── Order_item.php
│   └── Payment.php
├── helpers/
│   ├── request.php          # Request utilities
│   ├── response.php         # Response formatting
│   ├── validator.php        # Input validation
│   ├── auth.php             # JWT & authentication
│   ├── upload.php           # File upload handling
│   └── paynow.php           # PayNow integration
├── middleware/
│   ├── require_auth.php     # Authentication middleware
│   └── require_admin.php    # Admin authorization
├── sql/
│   └── schema.sql           # Database schema
└── uploads/
    └── products/            # Product images
```

## Key Features

### ✅ Implemented

- **User Authentication**: Registration, login, profile management with JWT
- **Product Management**: Full CRUD operations with image upload
- **Category System**: Hierarchical categories and sub-categories
- **Brand & Models**: Vehicle brands with associated models
- **Shopping Cart & Orders**: Create orders with multiple items
- **Order Management**: Track order status, view order details
- **Payment Integration**: PayNow gateway integration with webhooks
- **Admin Dashboard**: Admin-only endpoints for management
- **Input Validation**: Comprehensive validation and sanitization
- **Error Handling**: Structured error responses with HTTP status codes
- **Pagination**: All list endpoints support pagination
- **Search**: Product search by name/description
- **Stock Management**: Automatic stock reduction on order creation
- **Transactions**: Database transactions for order creation

### Security Features

- **SQL Injection Prevention**: PDO prepared statements
- **Password Hashing**: bcrypt password hashing
- **JWT Authentication**: Secure token-based authentication
- **Authorization**: Role-based access control (Admin/Customer)
- **Input Sanitization**: All user inputs sanitized
- **CORS**: Cross-origin resource sharing configured
- **File Upload Validation**: MIME type and size validation

## Testing

### Manual Testing with cURL

#### Register
```bash
curl -X POST http://localhost/ecommerce_backend/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "password": "password123",
    "phoneNumber": "0712345678"
  }'
```

#### Login
```bash
curl -X POST http://localhost/ecommerce_backend/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

#### Get Products
```bash
curl -X GET http://localhost/ecommerce_backend/api/products?page=1&limit=10
```

#### Create Order (requires token)
```bash
curl -X POST http://localhost/ecommerce_backend/api/orders/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "items": [{"productId": 1, "quantity": 2}],
    "customerName": "John Doe",
    "customerPhone": "0712345678",
    "customerAddress": "123 Main St",
    "paymentMethod": "PayNow"
  }'
```

## Configuration

### Environment Variables (Optional)

Create a `.env` file in the project root:

```env
# Database
DB_HOST=localhost
DB_USER=root
DB_PASS=password
DB_NAME=AutoSpares

# Application
APP_ENV=production
APP_URL=http://localhost/ecommerce_backend
FRONTEND_URL=http://localhost:3000

# JWT
JWT_SECRET=your-super-secret-key-change-in-production

# PayNow
PAYNOW_INTEGRATION_KEY=your-integration-key
PAYNOW_ENCRYPTION_KEY=your-encryption-key
```

### Production Deployment

1. **Set permissions:**
   ```bash
   chmod 755 ecommerce_backend
   chmod 755 ecommerce_backend/uploads
   chmod 755 ecommerce_backend/uploads/products
   ```

2. **Enable HTTPS** on your web server

3. **Set strong JWT secret** in configuration

4. **Configure PayNow** credentials for production

5. **Set up error logging** for production debugging

6. **Enable WAF** (Web Application Firewall) for protection

## Common Issues & Solutions

### Issue: CORS errors
**Solution:** Check that `config/cors.php` is included in all controllers

### Issue: Product images not uploading
**Solution:** Ensure `uploads/products` directory has write permissions (755)

### Issue: Payment webhook not working
**Solution:** Ensure your server is publicly accessible and PayNow webhook URL is configured correctly

### Issue: Database connection errors
**Solution:** Verify database credentials in `config/database.php`

## Future Enhancements

- [ ] Email notifications for order status
- [ ] SMS alerts via Twilio
- [ ] Reviews and ratings system
- [ ] Wishlist/favorite products
- [ ] Discount codes and coupons
- [ ] Inventory alerts
- [ ] Advanced analytics
- [ ] Multiple payment gateways
- [ ] Shipping integration
- [ ] Refund management

## License

This project is provided as-is for educational and commercial use.

## Support

For issues, questions, or contributions, please create an issue in the repository.

---

**Last Updated:** April 24, 2026
**Version:** 1.0.0
**Status:** Production Ready ✅

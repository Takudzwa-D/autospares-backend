# Testing AutoSpares API with Postman

Complete guide for testing the AutoSpares eCommerce backend API using Postman.

## Installation

1. **Download Postman**
   - Go to https://www.postman.com/downloads/
   - Install for your OS (Windows, Mac, Linux)

2. **Import the Collection**
   - Open Postman
   - Click "Collections" on the left sidebar
   - Click "Import"
   - Upload `AutoSpares_Postman_Collection.json`
   - All 27 endpoints are now ready to test!

## Quick Start

### 1. Register a New User

**Step 1:** Open Postman and go to Collections → AutoSpares → Authentication → Register User

**Step 2:** The request body is already filled:
```json
{
  "firstName": "John",
  "lastName": "Doe",
  "email": "john@example.com",
  "password": "password123",
  "phoneNumber": "0712345678",
  "address": "123 Main Street",
  "city": "Harare",
  "country": "Zimbabwe"
}
```

**Step 3:** Click "Send" button

**Expected Response (201 Created):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "role": "Customer",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

**Save the token!** Copy it for next steps.

---

### 2. Login

**Step 1:** Go to Collections → AutoSpares → Authentication → Login

**Step 2:** The request body is pre-filled:
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Step 3:** Click "Send"

**Expected Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "id": 1,
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "role": "Customer",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

---

### 3. Use Token in Authenticated Requests

**Every protected endpoint needs your token in the Authorization header.**

**How to add token to all requests:**

**Method 1: Add to Individual Request**
1. Open any protected endpoint (e.g., Get Profile)
2. Click the "Headers" tab
3. You'll see Authorization header already set to `Bearer YOUR_TOKEN_HERE`
4. Replace `YOUR_TOKEN_HERE` with your actual token from login

**Method 2: Set Global Variable (Recommended)**
1. Click the gear icon ⚙️ in top right
2. Go to "Manage Environments"
3. Click "Create" to make new environment
4. Name it "AutoSpares"
5. Add variable:
   - Key: `token`
   - Value: `YOUR_ACTUAL_TOKEN_FROM_LOGIN`
6. Click Save
7. Select this environment from dropdown in top right
8. Now use `{{token}}` in all Authorization headers - already done in collection!

---

## Testing Workflow

### Phase 1: Public Endpoints (No Auth Required)

These work without authentication:

#### 1. Get All Categories
- **Endpoint:** GET `/api/categories?page=1&limit=10`
- **Expected:** List of product categories
- **Response Time:** ~100ms

#### 2. Get All Brands
- **Endpoint:** GET `/api/brands?page=1&limit=10`
- **Expected:** List of vehicle brands (Toyota, Nissan, etc)

#### 3. Get All Products
- **Endpoint:** GET `/api/products?page=1&limit=10`
- **Expected:** List of all products with pagination

#### 4. Search Products
- **Endpoint:** GET `/api/products/search?q=engine`
- **Expected:** Filtered products matching "engine"

---

### Phase 2: Authentication (Auth Required)

#### 1. Get Profile
- **Path:** Authentication → Get Profile
- **Auth:** Your token in Authorization header
- **Expected:** Your user details

#### 2. Update Profile
- **Path:** Authentication → Update Profile
- **Auth:** Your token
- **Body:** Update firstName, lastName, phone, address, etc
- **Expected:** Updated user data

#### 3. Logout
- **Path:** Authentication → Logout
- **Auth:** Your token
- **Expected:** Logout message

---

### Phase 3: Shopping (Auth Required)

#### 1. Get Single Product
- **Endpoint:** GET `/api/products/get?id=1`
- **Expected:** Full product details with image, category, brand, model

#### 2. Browse by Brand
- **Endpoint:** GET `/api/brands/products?brandId=1`
- **Expected:** All products for that brand

#### 3. Browse by Category
- **Endpoint:** GET `/api/categories/products?categoryId=1`
- **Expected:** Products in that category

---

### Phase 4: Create Order (Auth Required)

#### 1. Create Order
- **Path:** Orders → Create Order
- **Auth:** Your token
- **Body:**
```json
{
  "items": [
    {
      "productId": 1,
      "quantity": 2
    }
  ],
  "customerName": "John Doe",
  "customerPhone": "0712345678",
  "customerAddress": "123 Main Street, Harare",
  "paymentMethod": "PayNow"
}
```
- **Expected (201):** Order created with ID and total amount
- **Note:** Stock automatically decreases

#### 2. Get My Orders
- **Path:** Orders → Get My Orders
- **Auth:** Your token
- **Expected:** List of your orders

#### 3. Get Order Details
- **Path:** Orders → Get Order Details
- **Query:** `orderId=1` (use ID from previous response)
- **Auth:** Your token
- **Expected:** Full order with items and payment info

---

### Phase 5: Payments (Auth Required)

#### 1. Initiate Payment
- **Path:** Payments → Initiate Payment
- **Auth:** Your token
- **Body:**
```json
{
  "orderId": 1
}
```
- **Expected:** PayNow URL or payment reference

#### 2. Get Payment Status
- **Path:** Payments → Get Payment Status
- **Query:** `orderId=1`
- **Auth:** Your token
- **Expected:** Current payment status

---

### Phase 6: Admin Endpoints (Admin Token Required)

**Note:** You need an Admin account to test these.

#### Create Admin Account (Direct Database)
```sql
INSERT INTO Users (FirstName, LastName, Email, Password, Role)
VALUES ('Admin', 'User', 'admin@example.com', 'hashed_password', 'Admin');
```

#### Admin: Add Product
- **Path:** Products → Add Product (Admin)
- **Auth:** Admin token
- **Body:**
```json
{
  "subCategoryId": 1,
  "modelId": 1,
  "name": "Engine Oil Filter",
  "description": "Original Toyota oil filter",
  "price": 25.00,
  "stockQuantity": 50
}
```
- **Expected (201):** Product created

#### Admin: Upload Product Image
- **Path:** Products → Upload Product Image (Admin)
- **Auth:** Admin token
- **Query:** `productId=1`
- **Body:** 
  - Switch to "form-data"
  - Add key "image" as File
  - Select an image file (JPG/PNG/GIF/WebP, max 5MB)
- **Expected:** Image uploaded and path saved

#### Admin: Update Order Status
- **Path:** Orders → Update Order Status (Admin)
- **Auth:** Admin token
- **Query:** `orderId=1`
- **Body:**
```json
{
  "status": "Completed"
}
```
- **Expected:** Order status updated

#### Admin: Get All Orders
- **Path:** Orders → Get All Orders (Admin)
- **Auth:** Admin token
- **Query:** `status=Pending` (optional filter)
- **Expected:** All orders in system

---

## Response Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | OK | GET successful |
| 201 | Created | POST/PUT successful |
| 400 | Bad Request | Missing required fields |
| 401 | Unauthorized | Missing/invalid token |
| 403 | Forbidden | Not admin (insufficient permissions) |
| 404 | Not Found | Product/Order ID doesn't exist |
| 409 | Conflict | Email already registered |
| 500 | Server Error | Database error |

---

## Debugging Tips

### 1. Check Response Status
Look at the status code badge next to "Send":
- 🟢 Green (2xx) = Success
- 🟡 Yellow (4xx) = Client error
- 🔴 Red (5xx) = Server error

### 2. View Response Body
Click "Pretty" tab to format JSON nicely

### 3. Check Headers
See what headers were sent and received

### 4. Enable Console Logging
- Postman menu → View → Show Postman Console
- Errors and details shown here

### 5. Verify Token Format
- Go to jwt.io
- Paste your token to verify it's valid
- Check expiry time

---

## Common Issues & Solutions

### Issue: "401 Unauthorized"
**Problem:** Missing or invalid token
**Solution:** 
1. Login again to get fresh token
2. Copy token to {{token}} environment variable
3. Check Authorization header has `Bearer {{token}}`

### Issue: "400 Bad Request"
**Problem:** Missing required fields
**Solution:**
1. Check request body has all required fields
2. Verify field names match documentation
3. Check data types (email must be string, quantity must be number)

### Issue: "409 Conflict"
**Problem:** Email already registered
**Solution:**
1. Use different email for registration
2. Or login with existing email

### Issue: "404 Not Found"
**Problem:** Resource doesn't exist
**Solution:**
1. Check ID is valid (product/order exists)
2. Use Get All endpoints to find correct ID
3. Verify ID is in the database

### Issue: Image upload fails
**Problem:** File format or size issue
**Solution:**
1. Use JPG, PNG, GIF, or WebP format
2. Keep file under 5MB
3. In Postman, select "form-data" body type
4. Set key as "image" and type as "File"

---

## Testing Checklist

Use this checklist to systematically test all features:

### Authentication
- [ ] Register new user
- [ ] Login with credentials
- [ ] Get user profile
- [ ] Update profile
- [ ] Logout

### Browsing
- [ ] Get all categories
- [ ] Get subcategories by category
- [ ] Get all brands
- [ ] Get models by brand
- [ ] Get all products
- [ ] Get single product
- [ ] Search products

### Shopping
- [ ] Create order with multiple items
- [ ] Get my orders
- [ ] Get order details

### Payments
- [ ] Initiate payment
- [ ] Check payment status

### Admin (if admin account available)
- [ ] Add new product
- [ ] Update product
- [ ] Upload product image
- [ ] Delete product
- [ ] Get all orders
- [ ] Update order status

---

## Performance Testing

### Load Test Simple Endpoint

1. **Get All Products** (no auth)
2. Send 10 requests one after another
3. Check average response time
4. Expected: 50-200ms per request

### Concurrent Requests

1. Open multiple product endpoints
2. Send all at same time (Ctrl+Enter in Postman)
3. Check if all succeed
4. Expected: All succeed, no timeouts

---

## Exporting Test Results

### Export as Report
1. Run all requests
2. Click "..." menu in Collection
3. "Run collection" 
4. After tests complete, click "Export results"
5. Choose format (JSON/CSV)

---

## Next Steps

Once all tests pass:

1. Test on staging environment
2. Load test with concurrent users
3. Test error scenarios (wrong password, no funds, etc)
4. Document any differences from expected behavior
5. Deploy to production when satisfied

---

## Postman Features to Explore

- **Tests Tab:** Write automated tests in JavaScript
- **Pre-request Script:** Setup data before request
- **Environments:** Switch between dev/staging/production
- **Collections Runner:** Run multiple requests in sequence
- **Monitoring:** Schedule regular API tests
- **Documentation:** Auto-generate API docs from collection

---

**Happy Testing!** 🚀

For issues or questions, check the README.md file for full API documentation.

# Core Tailor Database Structure

## Overview
This document describes the streamlined database structure focused on the essential business flow tables for a tailor business management system. The database has been optimized to include only the core tables needed for the main business operations.

## Core Business Flow Tables

### 1. **Categories** - Product Organization
- **Purpose**: Organize products into logical groups
- **Key Fields**: `id`, `name`, `description`, `created_at`
- **Essential for**: Product catalog management

### 2. **Units** - Measurement Standards
- **Purpose**: Define standard units for product measurements
- **Key Fields**: `id`, `name`, `short_name`, `created_at`
- **Essential for**: Consistent product measurements (meters, yards, pieces, etc.)

### 3. **Suppliers** - Vendor Management
- **Purpose**: Store supplier/vendor information and track balances
- **Key Fields**: 
  - `id`, `name`, `contact_person`, `phone`, `email`, `address`
  - `opening_balance`, `current_balance`, `status`, `created_at`
- **Essential for**: Purchase management and supplier relationship tracking

### 4. **Products** - Product Catalog
- **Purpose**: Central product database with pricing and categorization
- **Key Fields**:
  - `id`, `name`, `code`, `category_id`, `unit_id`
  - `cost_price`, `selling_price`, `min_stock`, `description`, `status`
- **Relationships**: Links to `categories` and `units`
- **Essential for**: Product management and inventory control

### 5. **Purchases** - Purchase Orders
- **Purpose**: Track purchase orders from suppliers
- **Key Fields**:
  - `id`, `purchase_no`, `supplier_id`, `purchase_date`
  - `total_amount`, `discount`, `tax`, `grand_total`
  - `paid_amount`, `due_amount`, `payment_method`, `status`
- **Relationships**: Links to `suppliers`
- **Essential for**: Purchase order management and supplier transactions

### 6. **Purchase Items** - Purchase Order Details
- **Purpose**: Individual items within purchase orders
- **Key Fields**: `id`, `purchase_id`, `product_id`, `quantity`, `unit_price`, `total_price`
- **Relationships**: Links to `purchases` and `products`
- **Essential for**: Detailed purchase tracking and cost analysis

### 7. **Stock Items** - Inventory Management
- **Purpose**: Track current stock levels for each product
- **Key Fields**: `id`, `product_id`, `opening_stock`, `current_stock`, `last_updated`
- **Relationships**: Links to `products`
- **Essential for**: Real-time inventory tracking and stock management

### 8. **Purchase Return** - Return Management
- **Purpose**: Handle product returns to suppliers
- **Key Fields**:
  - `id`, `return_no`, `purchase_id`, `supplier_id`, `return_date`
  - `total_amount`, `notes`, `status`
- **Relationships**: Links to `purchases` and `suppliers`
- **Essential for**: Return processing and supplier relationship management

### 9. **Supplier Ledger** - Financial Tracking
- **Purpose**: Track all financial transactions with suppliers
- **Key Fields**:
  - `id`, `supplier_id`, `type`, `reference_id`, `reference_type`
  - `debit`, `credit`, `balance`, `date`, `notes`
- **Types**: purchase, payment, return, adjustment
- **Relationships**: Links to `suppliers`
- **Essential for**: Supplier account reconciliation and financial reporting

### 10. **Supplier Payment** - Payment Tracking
- **Purpose**: Record payments made to suppliers
- **Key Fields**: `id`, `supplier_id`, `amount`, `payment_method`, `reference`, `payment_date`, `notes`
- **Relationships**: Links to `suppliers`
- **Essential for**: Payment tracking and supplier relationship management

## Database Relationships

```
Categories (1) ←→ (Many) Products
Units (1) ←→ (Many) Products
Suppliers (1) ←→ (Many) Purchases
Suppliers (1) ←→ (Many) Purchase Returns
Suppliers (1) ←→ (Many) Supplier Ledger
Suppliers (1) ←→ (Many) Supplier Payments
Products (1) ←→ (Many) Purchase Items
Products (1) ←→ (1) Stock Items
Purchases (1) ←→ (Many) Purchase Items
Purchases (1) ←→ (Many) Purchase Returns
```

## Key Features

### ✅ **Included (Core Business Flow)**
- Product management and categorization
- Supplier management and relationships
- Purchase order processing
- Inventory tracking
- Return management
- Financial ledger tracking
- Payment processing

### ❌ **Removed (Non-Essential)**
- Customer management (can be added later if needed)
- Sales management (can be added later if needed)
- User management (can be added later if needed)
- Complex accounting features
- Backup and system tables
- Cash management (can be added later if needed)

## Benefits of This Structure

1. **Focused Functionality**: Only essential tables for core business operations
2. **Clean Relationships**: Clear foreign key constraints and data integrity
3. **Scalable**: Easy to add additional features later
4. **Maintainable**: Simple structure that's easy to understand and modify
5. **Performance**: Optimized for the most common business operations

## Implementation Steps

1. **Import the SQL**: Use `core_tailor_database.sql` to create the database
2. **Verify Structure**: Check that all tables and relationships are created correctly
3. **Test Core Functions**: Ensure basic operations work (add products, create purchases, etc.)
4. **Add Additional Features**: Gradually add customer management, sales, etc. as needed

## File: `core_tailor_database.sql`
This file contains the complete SQL structure for the streamlined database. It includes:
- All table definitions with proper constraints
- Essential sample data (categories and units)
- Proper indexing and foreign key relationships
- Auto-increment settings for all primary keys

## Next Steps
1. Import this database structure into your MySQL/MariaDB server
2. Update your PHP application to work with the new table structure
3. Test the core business flow operations
4. Add additional features as your business requirements grow

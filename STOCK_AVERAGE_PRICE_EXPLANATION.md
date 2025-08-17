# Stock Average Price Calculation System

## Overview
This document explains how the tailor shop application calculates and displays average purchase prices and average sale prices for products, including the new weighted average system that accounts for different quantities purchased at different prices.

## The Problem
When you purchase the same product at different prices in different batches, you need to understand:
1. **Simple Average**: The average of all purchase prices (equal weight to each price)
2. **Weighted Average**: The average that considers quantities purchased at each price

## Example Scenario
Let's say you purchase "Royal Tag" fabric:
- **Batch 1**: 23 units at 400 PKR per unit
- **Batch 2**: 15 units at 650 PKR per unit

## Current System Analysis

### Database Structure
Your system uses a `stock_items` table where each purchase creates a separate stock item:
```sql
-- Batch 1: 23 units at 400 PKR
INSERT INTO stock_items (product_id, quantity, purchase_price, sale_price, status) 
VALUES (21, 23, 400.00, 500.00, 'available');

-- Batch 2: 15 units at 650 PKR  
INSERT INTO stock_items (product_id, quantity, purchase_price, sale_price, status)
VALUES (21, 15, 650.00, 750.00, 'available');
```

### Price Calculations

#### 1. Simple Average (Old Method)
```sql
AVG(si.purchase_price) = (400 + 650) / 2 = 525.00 PKR
```
**Problem**: This gives equal weight to both prices, ignoring that you bought more units at 400 PKR.

#### 2. Weighted Average (New Method)
```sql
SUM(si.quantity * si.purchase_price) / SUM(si.quantity)
= (23 × 400 + 15 × 650) / (23 + 15)
= (9,200 + 9,750) / 38
= 18,950 / 38
= 498.68 PKR
```
**Benefit**: This reflects the true average cost considering quantities.

## Implementation Details

### Updated SQL Query in `stock.php`
```sql
SELECT 
    p.id,
    p.product_name,
    -- ... other fields ...
    COALESCE(AVG(si.purchase_price), 0) as avg_purchase_price,
    COALESCE(AVG(si.sale_price), 0) as avg_sale_price,
    COALESCE(SUM(si.quantity * si.purchase_price) / SUM(si.quantity), 0) as weighted_avg_purchase_price,
    COALESCE(SUM(si.quantity * si.sale_price) / SUM(si.quantity), 0) as weighted_avg_sale_price
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN stock_items si ON p.id = si.product_id
GROUP BY p.id, p.product_name, p.product_code, p.product_unit, p.alert_quantity, p.description, p.color, c.category, p.status, p.created_at
```

### New Table Columns
The stock table now shows:
1. **Avg Purchase Price**: Simple average of all purchase prices
2. **Weighted Avg Purchase**: Quantity-weighted average purchase price
3. **Avg Sale Price**: Simple average of all sale prices  
4. **Weighted Avg Sale**: Quantity-weighted average sale price

## Business Benefits

### 1. **Accurate Cost Analysis**
- Weighted average gives you the true average cost per unit
- Helps in setting competitive sale prices
- Better profit margin calculations

### 2. **Inventory Valuation**
- More accurate stock valuation for accounting
- Better financial reporting
- Improved decision making for reordering

### 3. **Pricing Strategy**
- Understand your true cost basis
- Set appropriate markup percentages
- Competitive pricing decisions

## How It Works in Practice

### Purchase Flow
1. **Add Purchase** (`add_purchase.php`)
   - Creates purchase record
   - Adds stock items with individual prices
   - Each batch maintains its own price

2. **Stock Management** (`stock.php`)
   - Shows both simple and weighted averages
   - Weighted average automatically updates when quantities change

3. **Sales Flow** (`add_sale.php`)
   - Consumes stock from available items
   - Maintains price history for each stock item

## Example Output

For "Royal Tag" fabric with the example above:

| Metric | Value | Description |
|--------|-------|-------------|
| **Total Stock** | 38 units | 23 + 15 units |
| **Simple Avg Purchase** | 525.00 PKR | (400 + 650) / 2 |
| **Weighted Avg Purchase** | 498.68 PKR | (23×400 + 15×650) / 38 |
| **Simple Avg Sale** | 625.00 PKR | (500 + 750) / 2 |
| **Weighted Avg Sale** | 598.68 PKR | (23×500 + 15×750) / 38 |

## Technical Notes

### Database Performance
- The weighted average calculation is done in SQL for efficiency
- No additional database queries needed
- Results are cached in the main stock query

### Data Integrity
- Each stock item maintains its original purchase price
- Historical accuracy is preserved
- No data loss when calculating averages

### Extensibility
- Easy to add more pricing metrics
- Can be extended to include date-based weighting
- Supports multiple currencies if needed

## Future Enhancements

### 1. **FIFO/LIFO Calculations**
- First-In-First-Out inventory valuation
- Last-In-First-Out inventory valuation
- More sophisticated inventory costing methods

### 2. **Date-Based Weighting**
- Recent purchases weighted more heavily
- Seasonal price adjustments
- Market trend analysis

### 3. **Supplier-Based Analysis**
- Average prices by supplier
- Supplier performance metrics
- Cost comparison reports

## Conclusion

The new weighted average system provides:
- **Accurate cost calculations** based on actual quantities purchased
- **Better business insights** for pricing and inventory decisions
- **Maintained data integrity** with historical price tracking
- **Improved financial reporting** capabilities

This system now properly handles your scenario where you purchase the same product at different prices (23 units at 400 PKR and 15 units at 650 PKR) and gives you the true weighted average cost of 498.68 PKR instead of the simple average of 525.00 PKR.

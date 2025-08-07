-- =====================================================
-- UPDATE EXPENSES TABLE FOR PAYMENT METHOD
-- =====================================================

-- Add payment_method column to expenses table
ALTER TABLE expenses ADD COLUMN payment_method VARCHAR(50) AFTER description;

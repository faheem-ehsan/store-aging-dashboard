USE store_aging;

-- Run this once when upgrading an existing installation.
-- Adds the SNW / Dispatch Store fields used by Add Batch and CSV import.
ALTER TABLE stock_batches
    MODIFY COLUMN last_receipt_date DATE NULL,
    ADD COLUMN stock_value DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER current_qty,
    ADD COLUMN receiving_store VARCHAR(150) NULL AFTER uom;

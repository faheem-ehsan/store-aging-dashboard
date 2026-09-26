-- Qarshi Dispatch Store System
-- Change EX / Expiry grade color from Red to Black.
-- Safe to run once or multiple times.

UPDATE grade_rules
SET color_class = 'black'
WHERE grade_code = 'EX';

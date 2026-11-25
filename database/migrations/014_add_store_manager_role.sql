-- Add Store Manager as a strict global operational role.
-- Store Manager: all operational divisions, no Administration access.

ALTER TABLE users
    MODIFY role ENUM('super_admin','store_manager','division_admin','user') NOT NULL DEFAULT 'user';

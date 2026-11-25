-- Granular Division Admin operational permissions
-- Existing Division Admin accounts retain Add/Edit/Delete access.
-- Read-only users are explicitly set to no write permissions.

ALTER TABLE users
    ADD COLUMN can_add TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
    ADD COLUMN can_edit TINYINT(1) NOT NULL DEFAULT 1 AFTER can_add,
    ADD COLUMN can_delete TINYINT(1) NOT NULL DEFAULT 1 AFTER can_edit;

UPDATE users
SET can_add=0, can_edit=0, can_delete=0
WHERE role='user';

UPDATE users
SET can_add=1, can_edit=1, can_delete=1
WHERE role IN ('super_admin','store_manager','division_admin');

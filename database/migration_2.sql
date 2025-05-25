ALTER TABLE expenses ADD COLUMN deleted_at TEXT DEFAULT NULL;
CREATE INDEX idx_expenses_deleted_at ON expenses(deleted_at);
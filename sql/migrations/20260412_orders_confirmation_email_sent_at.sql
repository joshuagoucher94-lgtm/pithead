-- Run once on existing databases (new installs get this from schema.sql).
ALTER TABLE orders
  ADD COLUMN confirmation_email_sent_at TIMESTAMP NULL DEFAULT NULL
  AFTER stripe_customer_id;

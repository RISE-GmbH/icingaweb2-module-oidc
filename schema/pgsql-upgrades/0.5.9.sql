ALTER TABLE tbl_provider ADD COLUMN enforce_scheme_https boolenum DEFAULT 'n' NOT NULL;

INSERT INTO tbl_schema (version, "timestamp", success, reason)
VALUES ('0.5.9', CURRENT_TIMESTAMP, 'y', NULL);

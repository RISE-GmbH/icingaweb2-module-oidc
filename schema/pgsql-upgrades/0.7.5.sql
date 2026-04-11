ALTER TABLE tbl_provider ADD COLUMN pkce boolenum DEFAULT 'n' NOT NULL;

INSERT INTO tbl_schema (version, "timestamp", success, reason)
VALUES ('0.7.5', CURRENT_TIMESTAMP, 'y', NULL);

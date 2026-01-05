ALTER TABLE tbl_provider ADD COLUMN azure_groups boolenum DEFAULT 'n' NOT NULL;
ALTER TABLE tbl_provider ADD COLUMN custom_username character varying(255) NOT NULL;

INSERT INTO tbl_schema (version, "timestamp", success, reason)
VALUES ('0.6.0', CURRENT_TIMESTAMP, 'y', NULL);

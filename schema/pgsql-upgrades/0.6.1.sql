ALTER TABLE tbl_provider ADD COLUMN group_name_prefix character varying(255) DEFAULT NULL;

INSERT INTO tbl_schema (version, "timestamp", success, reason)
VALUES ('0.6.1', CURRENT_TIMESTAMP, 'y', NULL);
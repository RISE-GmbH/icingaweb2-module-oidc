ALTER TABLE tbl_provider ADD COLUMN azure_groups enum ('y', 'n') DEFAULT 'n' NOT NULL;
ALTER TABLE tbl_provider ADD COLUMN custom_username varchar(255) DEFAULT NULL;

INSERT INTO tbl_schema (version, timestamp, success, reason)
VALUES ('0.6.0', UNIX_TIMESTAMP() * 1000, 'y', NULL);

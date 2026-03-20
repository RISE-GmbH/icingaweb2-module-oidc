ALTER TABLE tbl_provider ADD COLUMN group_name_prefix varchar(255) DEFAULT NULL;

INSERT INTO tbl_schema (version, timestamp, success, reason)
VALUES ('0.6.1', UNIX_TIMESTAMP() * 1000, 'y', NULL);
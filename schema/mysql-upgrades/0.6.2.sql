ALTER TABLE tbl_provider ADD COLUMN group_mapping_strategy varchar(16) NOT NULL DEFAULT 'shared';

INSERT INTO tbl_schema (version, timestamp, success, reason)
VALUES ('0.6.2', UNIX_TIMESTAMP() * 1000, 'y', NULL);
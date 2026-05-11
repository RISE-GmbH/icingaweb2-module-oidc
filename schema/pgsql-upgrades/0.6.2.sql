ALTER TABLE tbl_provider
ALTER COLUMN logo TYPE varchar(255),
    ALTER COLUMN logo DROP NOT NULL;

INSERT INTO tbl_schema (version, "timestamp", success, reason)
VALUES ('0.6.2', CURRENT_TIMESTAMP, 'y', NULL);

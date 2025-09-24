ALTER TABLE tbl_provider ADD COLUMN enforce_scheme_https enum ('y', 'n') DEFAULT 'n' NOT NULL;

INSERT INTO tbl_schema (version, timestamp, success, reason)
VALUES ('0.5.9', UNIX_TIMESTAMP() * 1000, 'y', NULL);

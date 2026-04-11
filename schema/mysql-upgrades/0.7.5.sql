ALTER TABLE tbl_provider ADD COLUMN pkce enum ('y', 'n') DEFAULT 'n' NOT NULL;

INSERT INTO tbl_schema (version, timestamp, success, reason)
VALUES ('0.7.5', UNIX_TIMESTAMP() * 1000, 'y', NULL);

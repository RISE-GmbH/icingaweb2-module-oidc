ALTER TABLE tbl_provider ADD COLUMN syncgroups LONGTEXT DEFAULT NULL;
ALTER TABLE tbl_provider ADD COLUMN defaultgroup TEXT DEFAULT NULL;
ALTER TABLE tbl_provider ADD COLUMN usernameblacklist TEXT DEFAULT NULL;

INSERT INTO tbl_schema (version, timestamp, success, reason)
VALUES ('0.5.6', UNIX_TIMESTAMP() * 1000, 'y', NULL);

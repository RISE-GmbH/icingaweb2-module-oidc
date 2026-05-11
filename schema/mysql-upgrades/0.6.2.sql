ALTER TABLE `tbl_provider`
    MODIFY `logo` varchar(255);

INSERT INTO tbl_schema (version, timestamp, success, reason)
VALUES ('0.6.2', UNIX_TIMESTAMP() * 1000, 'y', NULL);

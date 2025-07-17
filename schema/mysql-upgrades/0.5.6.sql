CREATE TABLE IF NOT EXISTS tbl_schema (
    id int unsigned NOT NULL AUTO_INCREMENT,
    version varchar(64) NOT NULL,
    timestamp bigint unsigned NOT NULL,
    success enum ('n', 'y') DEFAULT NULL,
    reason text DEFAULT NULL,

    PRIMARY KEY (id),
    CONSTRAINT idx_tbl_schema_version UNIQUE (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

ALTER TABLE tbl_provider ADD COLUMN syncgroups LONGTEXT DEFAULT NULL;
ALTER TABLE tbl_provider ADD COLUMN defaultgroup TEXT DEFAULT NULL;
ALTER TABLE tbl_provider ADD COLUMN usernameblacklist TEXT DEFAULT NULL;

INSERT INTO tbl_schema (version, timestamp, success, reason)
VALUES ('0.5.6', UNIX_TIMESTAMP() * 1000, 'y', NULL);

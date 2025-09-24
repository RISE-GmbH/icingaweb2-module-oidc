DROP TABLE IF EXISTS tbl_group_membership;
DROP TABLE IF EXISTS tbl_group;
DROP TABLE IF EXISTS tbl_user;
DROP TABLE IF EXISTS tbl_provider;
DROP TABLE IF EXISTS tbl_schema;

CREATE TABLE tbl_provider (
    id int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name  varchar(255) NOT NULL,
    url  varchar(255) NOT NULL,
    secret  varchar(255) NOT NULL,
    appname  varchar(255) NOT NULL,
    logo  varchar(255) NOT NULL,
    syncgroups LONGTEXT DEFAULT NULL,
    defaultgroup TEXT DEFAULT NULL,
    required_groups TEXT DEFAULT NULL,
    nooidcgroups enum ('y', 'n') DEFAULT 'n' NOT NULL,
    usernameblacklist TEXT DEFAULT NULL,
    buttoncolor  varchar(255) NOT NULL,
    textcolor  varchar(255) NOT NULL,
    caption  varchar(255) NOT NULL,
    enforce_scheme_https enum ('y', 'n') DEFAULT 'n' NOT NULL,
    enabled        enum ('y', 'n')  DEFAULT 'n' NOT NULL,
    ctime bigint unsigned DEFAULT NULL,
    mtime bigint unsigned DEFAULT NULL,
    UNIQUE uq_oidc_provider_name (name) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tbl_user (
    id int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name  varchar(255) NOT NULL,
    email  varchar(255) DEFAULT NULL,
    provider_id  int(10) unsigned NOT NULL,
    mapped_local_user  varchar(255) DEFAULT NULL,
    mapped_backend  varchar(255) DEFAULT NULL,
    active int(10) unsigned NOT NULL,
    lastlogin bigint unsigned DEFAULT NULL,
    ctime bigint unsigned DEFAULT NULL,
    mtime bigint unsigned DEFAULT NULL,
    UNIQUE uq_oidc_user_name (name) USING BTREE,
    FOREIGN KEY (provider_id)
        REFERENCES tbl_provider (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tbl_group (
    id int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name  varchar(255) NOT NULL,
    provider_id  int(10) unsigned NOT NULL,
    parent int(10) DEFAULT NULL,
    ctime bigint unsigned DEFAULT NULL,
    mtime bigint unsigned DEFAULT NULL,
    UNIQUE uq_oidc_group_name (name) USING BTREE,
    FOREIGN KEY (provider_id)
        REFERENCES tbl_provider (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tbl_group_membership (
    id int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    group_id int(10) unsigned NOT NULL,
    provider_id  int(10) unsigned NOT NULL,
    username  varchar(255) NOT NULL,
    ctime bigint unsigned DEFAULT NULL,
    mtime bigint unsigned DEFAULT NULL,
    FOREIGN KEY (provider_id)
        REFERENCES tbl_provider (id)
        ON DELETE CASCADE,
    FOREIGN KEY (group_id)
        REFERENCES tbl_group (id)
        ON DELETE CASCADE,
    FOREIGN KEY (username)
        REFERENCES tbl_user (name)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tbl_schema (
    id int unsigned NOT NULL AUTO_INCREMENT,
    version varchar(64) NOT NULL,
    timestamp bigint unsigned NOT NULL,
    success enum ('n', 'y') DEFAULT NULL,
    reason text DEFAULT NULL,

    PRIMARY KEY (id),
    CONSTRAINT idx_tbl_schema_version UNIQUE (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;


INSERT INTO tbl_schema (version, timestamp, success, reason)
VALUES ('0.5.8', UNIX_TIMESTAMP() * 1000, 'y', NULL);

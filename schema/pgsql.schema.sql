DROP TABLE IF EXISTS tbl_group_membership;
DROP TABLE IF EXISTS tbl_group;
DROP TABLE IF EXISTS tbl_user;
DROP TABLE IF EXISTS tbl_provider;
DROP TABLE IF EXISTS tbl_schema;

CREATE TYPE boolenum AS ENUM ('n', 'y');

CREATE TABLE tbl_provider (
    id SERIAL,
    name character varying(255) NOT NULL,
    url character varying(255) NOT NULL,
    secret character varying(255) NOT NULL,
    appname character varying(255) NOT NULL,
    logo character varying(255) DEFAULT NULL,
    syncgroups TEXT DEFAULT NULL,
    defaultgroup TEXT DEFAULT NULL,
    required_groups TEXT DEFAULT NULL,
    nooidcgroups boolenum DEFAULT 'n' NOT NULL,
    pkce  boolenum DEFAULT 'n' NOT NULL,
    usernameblacklist TEXT DEFAULT NULL,
    buttoncolor character varying(255) NOT NULL,
    textcolor character varying(255) NOT NULL,
    caption character varying(255) NOT NULL,
    azure_groups boolenum DEFAULT 'n' NOT NULL,
    custom_username character varying(255) DEFAULT NULL,
    username_prefix VARCHAR(255) DEFAULT '',
    groupname_prefix VARCHAR(255) DEFAULT '',
    enforce_scheme_https boolenum DEFAULT 'n' NOT NULL,
    enabled boolenum DEFAULT 'n' NOT NULL,
    ctime bigint DEFAULT NULL,
    mtime bigint DEFAULT NULL
);

ALTER TABLE ONLY "tbl_provider"
    ADD CONSTRAINT pk_tbl_provider
    PRIMARY KEY ("id");

CREATE UNIQUE INDEX idx_tbl_provider
    ON "tbl_provider"
    USING btree (
    lower((name)::text)
    );

CREATE TABLE tbl_user (
    id SERIAL,
    name character varying(255) NOT NULL,
    email character varying(255) DEFAULT NULL,
    provider_id int NOT NULL,
    mapped_local_user character varying(255) DEFAULT NULL,
    mapped_backend character varying(255) DEFAULT NULL,
    active smallint NOT NULL,
    lastlogin bigint DEFAULT NULL,
    ctime bigint DEFAULT NULL,
    mtime bigint DEFAULT NULL,
    FOREIGN KEY (provider_id)
      REFERENCES tbl_provider (id)
      ON DELETE CASCADE
);

ALTER TABLE ONLY "tbl_user"
    ADD CONSTRAINT pk_tbl_user
    PRIMARY KEY ("id");

CREATE UNIQUE INDEX uq_oidc_user_provider_name
    ON tbl_user(provider_id, lower((name)::text));

CREATE TABLE tbl_group (
    id SERIAL,
    name character varying(255) NOT NULL,
    provider_id int NOT NULL,
    parent int DEFAULT NULL,
    ctime bigint DEFAULT NULL,
    mtime bigint DEFAULT NULL,
    FOREIGN KEY (provider_id)
       REFERENCES tbl_provider (id)
       ON DELETE CASCADE
);

ALTER TABLE ONLY "tbl_group"
    ADD CONSTRAINT pk_tbl_group
    PRIMARY KEY ("id");

CREATE UNIQUE INDEX uq_oidc_group_name
    ON tbl_group(provider_id, lower((name)::text));

CREATE TABLE tbl_group_membership (
    id SERIAL,
    group_id int NOT NULL,
    provider_id int NOT NULL,
    user_id int NOT NULL,
    username character varying(255) NOT NULL,
    ctime bigint DEFAULT NULL,
    mtime bigint DEFAULT NULL,
    FOREIGN KEY (provider_id)
      REFERENCES tbl_provider (id)
      ON DELETE CASCADE,
    FOREIGN KEY (group_id)
      REFERENCES tbl_group (id)
      ON DELETE CASCADE,
    FOREIGN KEY (user_id)
      REFERENCES tbl_user (id)
      ON DELETE CASCADE
);

ALTER TABLE ONLY "tbl_group_membership"
    ADD CONSTRAINT pk_tbl_group_membership
    PRIMARY KEY ("id");

CREATE TABLE tbl_schema (
    id SERIAL,
    version character varying(64) NOT NULL,
    "timestamp" timestamp NOT NULL,
    success boolenum DEFAULT NULL,
    reason text DEFAULT NULL,

    PRIMARY KEY (id),
    CONSTRAINT idx_tbl_schema_version UNIQUE (version)
);

INSERT INTO tbl_schema (version, "timestamp", success, reason)
VALUES ('0.6.3', CURRENT_TIMESTAMP, 'y', NULL);


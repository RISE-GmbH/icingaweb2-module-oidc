ALTER TABLE tbl_provider ADD COLUMN nooidcgroups boolenum DEFAULT 'n' NOT NULL;

INSERT INTO tbl_schema (version, "timestamp", success, reason)
VALUES ('0.5.8', CURRENT_TIMESTAMP, 'y', NULL);

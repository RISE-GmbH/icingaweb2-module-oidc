-- 1. Add user_id column
ALTER TABLE tbl_group_membership
    ADD COLUMN user_id INTEGER;

-- 2. Backfill user_id
UPDATE tbl_group_membership gm
SET user_id = u.id
    FROM tbl_user u
WHERE u.name = gm.username
  AND u.provider_id = gm.provider_id;

-- 3. Make user_id NOT NULL
ALTER TABLE tbl_group_membership
    ALTER COLUMN user_id SET NOT NULL;

-- 4. Drop old foreign keys
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT conname
        FROM pg_constraint
        WHERE conrelid = 'tbl_group_membership'::regclass
                  AND contype = 'f'
    LOOP
        EXECUTE format(
            'ALTER TABLE %I DROP CONSTRAINT %I',
            'tbl_group_membership',
            r.conname
        );
    END LOOP;
END $$;

-- 5. Add new foreign keys
ALTER TABLE tbl_group_membership
    ADD CONSTRAINT fk_group_membership_provider
        FOREIGN KEY (provider_id)
            REFERENCES tbl_provider(id)
            ON DELETE CASCADE,
    ADD CONSTRAINT fk_group_membership_group
        FOREIGN KEY (group_id)
        REFERENCES tbl_group(id)
        ON DELETE CASCADE,
    ADD CONSTRAINT fk_group_membership_user
        FOREIGN KEY (user_id)
        REFERENCES tbl_user(id)
        ON DELETE CASCADE;

-- 6. Change unique constraint/index on tbl_user
ALTER TABLE tbl_user
    DROP CONSTRAINT IF EXISTS uq_tbl_user_name;

DROP INDEX IF EXISTS idx_tbl_user;

CREATE UNIQUE INDEX uq_oidc_user_provider_name
    ON tbl_user(provider_id, lower((name)::text));

-- 7. Change unique index on tbl_group
DROP INDEX IF EXISTS idx_tbl_group;

CREATE UNIQUE INDEX uq_oidc_group_name
    ON tbl_group(provider_id, lower((name)::text));

-- 8. Add columns to tbl_provider
ALTER TABLE tbl_provider
    ADD COLUMN username_prefix VARCHAR(255) DEFAULT '',
    ADD COLUMN groupname_prefix VARCHAR(255) DEFAULT '';

-- 9. Insert schema version
INSERT INTO tbl_schema (version, "timestamp", success, reason)
VALUES ('0.6.1', CURRENT_TIMESTAMP, 'y', NULL);
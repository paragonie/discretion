CREATE TABLE IF NOT EXISTS discretion_users (
    userid    BIGSERIAL PRIMARY KEY,
    username  TEXT,
    pwhash    TEXT,
    twofactor TEXT,
    email     TEXT,
    fullname  TEXT,
    chronicle TEXT,
    created   TIMESTAMP,
    modified  TIMESTAMP
);
CREATE TABLE IF NOT EXISTS discretion_contacts (
    contactid      BIGSERIAL PRIMARY KEY,
    userid         BIGINT REFERENCES discretion_users(userid),
    name           TEXT,
    email          TEXT,
    gpgfingerprint TEXT, -- GnuPG Public Key Fingerprint
    created        TIMESTAMP,
    modified       TIMESTAMP
);

CREATE TABLE IF NOT EXISTS discretion_themes (
    themeid BIGSERIAL PRIMARY KEY,
    name    TEXT,
    public  BOOLEAN DEFAULT FALSE,
    userid  BIGINT NULL REFERENCES discretion_users(userid),
    config  JSONB
);

CREATE TABLE IF NOT EXISTS discretion_forms (
    formid BIGSERIAL PRIMARY KEY,
    userid BIGINT REFERENCES discretion_users(userid),
    themeid BIGINT REFERENCES discretion_themes(themeid),
    publicid TEXT,
    config JSONB,
    created  TIMESTAMP,
    modified TIMESTAMP
);

INSERT INTO discretion_themes (name, config) VALUES (
    'Default Theme',
    '{"path": "default"}'
);

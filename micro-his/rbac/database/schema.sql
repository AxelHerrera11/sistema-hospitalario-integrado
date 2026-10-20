CREATE TABLE role_permissions (role_code TEXT NOT NULL, permission TEXT NOT NULL, PRIMARY KEY (role_code, permission));
CREATE TABLE memberships (user_id TEXT NOT NULL, hospital_id TEXT NOT NULL, role_code TEXT NOT NULL, PRIMARY KEY (user_id, hospital_id, role_code));
CREATE TABLE audit_log (event TEXT NOT NULL, actor_id TEXT NOT NULL, hospital_id TEXT NOT NULL, detail TEXT NOT NULL, trace_id TEXT);

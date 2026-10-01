CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "departments"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "code" varchar not null,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "departments_name_unique" on "departments"("name");
CREATE UNIQUE INDEX "departments_code_unique" on "departments"("code");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" varchar not null,
  "queue" varchar not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE INDEX "failed_jobs_connection_queue_failed_at_index" on "failed_jobs"(
  "connection",
  "queue",
  "failed_at"
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "categories"(
  "id" integer primary key autoincrement not null,
  "parent_id" integer,
  "name" varchar not null,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("parent_id") references "categories"("id") on delete set null
);
CREATE UNIQUE INDEX "categories_parent_id_name_unique" on "categories"(
  "parent_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "priorities"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "name" varchar not null,
  "description" text,
  "sla_hours" integer not null,
  "level" integer not null default '0',
  "is_requester_selectable" tinyint(1) not null default '1',
  "is_active" tinyint(1) not null default '1',
  "is_system" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "priorities_key_unique" on "priorities"("key");
CREATE TABLE IF NOT EXISTS "ticket_statuses"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "name" varchar not null,
  "color" varchar not null,
  "sort_order" integer not null default '0',
  "type" varchar not null,
  "pauses_sla" tinyint(1) not null default '0',
  "is_system" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "ticket_statuses_key_unique" on "ticket_statuses"("key");
CREATE TABLE IF NOT EXISTS "tickets"(
  "id" integer primary key autoincrement not null,
  "ticket_number" varchar not null,
  "requester_id" integer not null,
  "created_by" integer not null,
  "department_id" integer not null,
  "category_id" integer not null,
  "priority_id" integer not null,
  "status_id" integer not null,
  "assigned_to" integer,
  "subject" varchar not null,
  "description" text not null,
  "location" varchar,
  "device_type" varchar,
  "asset_number" varchar,
  "contact_number" varchar,
  "due_at" datetime,
  "sla_paused_at" datetime,
  "first_response_at" datetime,
  "resolved_at" datetime,
  "closed_at" datetime,
  "reopen_count" integer not null default '0',
  "overdue_notified_at" datetime,
  "deleted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("requester_id") references "users"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete cascade,
  foreign key("department_id") references "departments"("id") on delete cascade,
  foreign key("category_id") references "categories"("id") on delete cascade,
  foreign key("priority_id") references "priorities"("id") on delete cascade,
  foreign key("status_id") references "ticket_statuses"("id") on delete cascade,
  foreign key("assigned_to") references "users"("id") on delete set null
);
CREATE INDEX "tickets_ticket_number_index" on "tickets"("ticket_number");
CREATE INDEX "tickets_status_id_index" on "tickets"("status_id");
CREATE INDEX "tickets_assigned_to_status_id_index" on "tickets"(
  "assigned_to",
  "status_id"
);
CREATE INDEX "tickets_requester_id_index" on "tickets"("requester_id");
CREATE INDEX "tickets_department_id_index" on "tickets"("department_id");
CREATE INDEX "tickets_category_id_index" on "tickets"("category_id");
CREATE INDEX "tickets_priority_id_index" on "tickets"("priority_id");
CREATE INDEX "tickets_created_at_index" on "tickets"("created_at");
CREATE INDEX "tickets_due_at_index" on "tickets"("due_at");
CREATE UNIQUE INDEX "tickets_ticket_number_unique" on "tickets"(
  "ticket_number"
);
CREATE TABLE IF NOT EXISTS "ticket_messages"(
  "id" integer primary key autoincrement not null,
  "ticket_id" integer not null,
  "user_id" integer not null,
  "type" varchar not null,
  "body" text not null,
  "deleted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("ticket_id") references "tickets"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "ticket_messages_ticket_id_created_at_index" on "ticket_messages"(
  "ticket_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "ticket_attachments"(
  "id" integer primary key autoincrement not null,
  "ticket_id" integer not null,
  "message_id" integer,
  "uploaded_by" integer not null,
  "original_name" varchar not null,
  "stored_name" varchar not null,
  "disk" varchar not null,
  "path" varchar not null,
  "mime_type" varchar not null,
  "size" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("ticket_id") references "tickets"("id") on delete cascade,
  foreign key("message_id") references "ticket_messages"("id") on delete set null,
  foreign key("uploaded_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "ticket_activities"(
  "id" integer primary key autoincrement not null,
  "ticket_id" integer not null,
  "user_id" integer,
  "type" varchar not null,
  "description" varchar not null,
  "old_value" varchar,
  "new_value" text,
  "is_internal" tinyint(1) not null default '0',
  "created_at" datetime not null,
  foreign key("ticket_id") references "tickets"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "ticket_activities_ticket_id_created_at_index" on "ticket_activities"(
  "ticket_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "ticket_sequences"(
  "year" integer not null,
  "last_number" integer not null default '0',
  primary key("year")
);
CREATE TABLE IF NOT EXISTS "audit_logs"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "action" varchar not null,
  "auditable_type" varchar,
  "auditable_id" integer,
  "description" text,
  "old_values" text,
  "new_values" text,
  "ip_address" varchar,
  "user_agent" text,
  "created_at" datetime not null,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "audit_logs_user_id_created_at_index" on "audit_logs"(
  "user_id",
  "created_at"
);
CREATE INDEX "audit_logs_auditable_type_auditable_id_index" on "audit_logs"(
  "auditable_type",
  "auditable_id"
);
CREATE INDEX "audit_logs_created_at_index" on "audit_logs"("created_at");
CREATE TABLE IF NOT EXISTS "settings"(
  "key" varchar not null,
  "value" text,
  "type" varchar not null default 'string',
  "created_at" datetime,
  "updated_at" datetime,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "verification_requests"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "id_type" varchar not null,
  "id_number" varchar not null,
  "id_image_path" varchar,
  "status" varchar not null default 'pending',
  "submitted_at" datetime not null,
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "decision_note" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE INDEX "verification_requests_user_id_status_index" on "verification_requests"(
  "user_id",
  "status"
);
CREATE INDEX "verification_requests_status_index" on "verification_requests"(
  "status"
);
CREATE INDEX "verification_requests_id_number_index" on "verification_requests"(
  "id_number"
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "employee_id" varchar,
  "first_name" varchar not null,
  "middle_name" varchar,
  "last_name" varchar not null,
  "email" varchar not null,
  "contact_number" varchar,
  "department_id" integer,
  "position" varchar,
  "role" varchar not null,
  "profile_photo_path" varchar,
  "is_active" tinyint(1) not null default('1'),
  "must_change_password" tinyint(1) not null default('0'),
  "last_login_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "account_status" varchar not null default 'approved',
  "deleted_at" datetime,
  foreign key("department_id") references departments("id") on delete set null on update no action
);
CREATE INDEX "users_account_status_index" on "users"("account_status");
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE UNIQUE INDEX "users_employee_id_unique" on "users"("employee_id");
CREATE UNIQUE INDEX "priorities_level_unique" on "priorities"("level");
CREATE UNIQUE INDEX "ticket_statuses_sort_order_unique" on "ticket_statuses"(
  "sort_order"
);

INSERT INTO migrations VALUES(1,'0000_01_01_000000_create_departments_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(4,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(5,'2026_09_19_000000_create_categories_table',1);
INSERT INTO migrations VALUES(6,'2026_09_19_000001_create_priorities_table',1);
INSERT INTO migrations VALUES(7,'2026_09_19_000002_create_ticket_statuses_table',1);
INSERT INTO migrations VALUES(8,'2026_09_19_000003_create_tickets_table',1);
INSERT INTO migrations VALUES(9,'2026_09_19_000004_create_ticket_messages_table',1);
INSERT INTO migrations VALUES(10,'2026_09_19_000005_create_ticket_attachments_table',1);
INSERT INTO migrations VALUES(11,'2026_09_19_000006_create_ticket_activities_table',1);
INSERT INTO migrations VALUES(12,'2026_09_19_000007_create_ticket_sequences_table',1);
INSERT INTO migrations VALUES(13,'2026_09_19_000008_create_audit_logs_table',1);
INSERT INTO migrations VALUES(14,'2026_09_19_000009_create_settings_table',1);
INSERT INTO migrations VALUES(15,'2026_09_19_000010_create_notifications_table',1);
INSERT INTO migrations VALUES(16,'2026_09_20_205153_add_account_status_to_users_table',1);
INSERT INTO migrations VALUES(17,'2026_09_20_205154_create_verification_requests_table',1);
INSERT INTO migrations VALUES(18,'2026_09_20_214344_enforce_account_status_not_null_on_users_table',1);
INSERT INTO migrations VALUES(19,'2026_09_26_064700_add_unique_level_to_priorities_table',1);
INSERT INTO migrations VALUES(20,'2026_09_26_095613_add_unique_sort_order_to_ticket_statuses_table',1);
INSERT INTO migrations VALUES(21,'2026_09_26_095936_separate_pending_status_badge_colors',1);
INSERT INTO migrations VALUES(22,'2026_09_28_000437_add_deleted_at_to_users_table',1);

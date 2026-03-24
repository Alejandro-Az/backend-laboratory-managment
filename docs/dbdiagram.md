# Diagrama de base de datos — dbdiagram.io

Usar https://dbdiagram.io/d para tener una vista mejor del diagrama

```
Table users {
  id integer [pk, increment]
  name varchar(255)
  email varchar(255) [unique]
  password varchar(255)
  remember_token varchar(100)
  email_verified_at timestamp
  created_at timestamp
  updated_at timestamp
}

Table user_preferences {
  id integer [pk, increment]
  user_id integer [not null, ref: > users.id]
  notify_urgent_sample_alerts boolean [default: false]
  notify_sample_completion boolean [default: false]
  notify_daily_activity_digest boolean [default: false]
  notify_project_updates boolean [default: false]
  created_at timestamp
  updated_at timestamp
}

Table clients {
  id integer [pk, increment]
  name varchar(255) [unique, not null]
  contact_email varchar(255)
  contact_phone varchar(50)
  location varchar(255)
  created_by integer [ref: > users.id]
  updated_by integer [ref: > users.id]
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp
}

Table projects {
  id integer [pk, increment]
  client_id integer [not null, ref: > clients.id]
  name varchar(255) [not null]
  status varchar(20) [note: 'active | completed | on_hold | archived']
  started_at date
  ended_at date
  description text
  created_by integer [ref: > users.id]
  updated_by integer [ref: > users.id]
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp
}

Table samples {
  id integer [pk, increment]
  project_id integer [not null, ref: > projects.id]
  code varchar(255) [unique, not null, note: 'unique incluyendo soft-deleted']
  status varchar(20) [not null, note: 'pending | in_progress | completed | cancelled']
  priority varchar(20) [not null, note: 'standard | urgent']
  received_at date [not null]
  analysis_started_at timestamp
  completed_at timestamp
  notes text
  rejection_count integer [default: 0, not null, note: 'se incrementa en cada retorno de in_progress a pending']
  created_by integer [ref: > users.id]
  updated_by integer [ref: > users.id]
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp
}

Table sample_results {
  id integer [pk, increment]
  sample_id integer [not null, ref: > samples.id]
  analyst_id integer [not null, ref: > users.id]
  result_summary text [not null]
  result_data json
  created_at timestamp
  updated_at timestamp
}

Table sample_events {
  id integer [pk, increment]
  sample_id integer [not null, ref: > samples.id]
  user_id integer [not null, ref: > users.id]
  event_type varchar(50) [not null, note: 'created | updated | status_changed | priority_changed | analysis_started | result_added | completed | deleted | restored']
  description text
  old_status varchar(20)
  new_status varchar(20)
  old_priority varchar(20)
  new_priority varchar(20)
  metadata json
  created_at timestamp
}
```

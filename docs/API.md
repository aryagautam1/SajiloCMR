# Sajilo CRM API reference

Base URL: `http://127.0.0.1:8000/api`

All API routes accept and return JSON. Send `Accept: application/json` on every request.

## Authentication

The API supports two authentication methods:

1. **Bearer token** â€” obtain a token from `POST /api/auth/login` and send `Authorization: Bearer <token>`.
2. **Session cookie** â€” the web UI uses Laravel Sanctum stateful requests with the session cookie and CSRF token.

### Sign in

`POST /api/auth/login`

```json
{
  "email": "arya22013071@iimscollege.edu.np",
  "password": "Sajilo@2026",
  "device_name": "Postman"
}
```

`device_name` is optional and labels the token.

**200 response**

```json
{
  "success": true,
  "message": "Logged in successfully.",
  "data": {
    "token": "1|J8w...",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Arya Gautam",
      "email": "arya22013071@iimscollege.edu.np",
      "role": "admin",
      "role_label": "Administrator",
      "phone": "+977 9801000201",
      "job_title": "Frontend Developer & Backend Support (intern)",
      "initials": "AS",
      "email_verified_at": null,
      "last_login_at": "2026-11-19T11:50:00+05:45",
      "last_login_human": "Just now",
      "assigned_projects_count": null,
      "created_at": "2026-11-19T05:26:39+00:00",
      "created_at_human": "5 hours ago"
    }
  }
}
```

Invalid credentials return HTTP 422:

```json
{
  "success": false,
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

Login attempts are throttled. Five failures within a minute produce HTTP 429 with a `retry_after` value.

### Current user

`GET /api/auth/user`

Requires authentication. Returns the same user object shown above inside `data`.

### Sign out

`POST /api/auth/logout`

Revokes the current Bearer token and signs out the web session if one is present.

```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

---

## Response envelope

Success:

```json
{
  "success": true,
  "message": "Client updated successfully.",
  "data": {}
}
```

Validation error (HTTP 422):

```json
{
  "message": "The name field is required. (and 2 more errors)",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email has already been taken."],
    "status": ["The selected status is invalid."]
  }
}
```

Common status codes:

| Code | Meaning |
| --- | --- |
| 200 | Success |
| 201 | Resource created |
| 401 | Missing/invalid token |
| 403 | Authenticated but not allowed |
| 404 | Resource not found |
| 409 | Business-rule conflict (for example, deleting a client with projects) |
| 422 | Validation failed |
| 429 | Login rate limit exceeded |

Paginated collection envelope:

```json
{
  "success": true,
  "data": [],
  "links": {
    "first": "http://127.0.0.1:8000/api/clients?page=1",
    "last": "http://127.0.0.1:8000/api/clients?page=3",
    "prev": null,
    "next": "http://127.0.0.1:8000/api/clients?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "links": [],
    "path": "http://127.0.0.1:8000/api/clients",
    "per_page": 10,
    "to": 10,
    "total": 24
  }
}
```

---

## Dashboard

### GET `/api/dashboard/statistics`

Returns KPI cards, status counts and recent activity. Every value is queried from the database.

Response `data`:

```json
{
  "cards": {
    "total_clients": 14,
    "active_clients": 8,
    "inactive_clients": 3,
    "prospect_clients": 3,
    "total_projects": 20,
    "ongoing_projects": 8,
    "completed_projects": 3,
    "pending_projects": 6,
    "on_hold_projects": 2,
    "cancelled_projects": 1,
    "overdue_projects": 0,
    "total_budget": 6466000,
    "active_budget": 6055000,
    "team_members": 5
  },
  "client_status_counts": { "active": 8, "inactive": 3, "prospect": 3 },
  "project_status_counts": {
    "planning": 3,
    "not_started": 3,
    "in_progress": 8,
    "on_hold": 2,
    "completed": 3,
    "cancelled": 1
  },
  "recent_projects": [],
  "recent_clients": [],
  "progress_overview": []
}
```

### GET `/api/dashboard/charts`

Returns the chart series used by the dashboard: `project_status`, `client_status`, `priority` and `monthly_activity` (12 months of `labels`, `created`, `completed`).

---

## Reports

### GET `/api/reports/summary`

Returns the full report page payload:

- `kpis` â€” total clients/projects, active/completed/overdue projects, completion rate, average progress, total/active/completed budget
- `project_status`, `client_status`, `priority` â€” chart series
- `budget_by_status` â€” count and budget per status
- `monthly_activity` â€” 12-month created/completed series
- `team_workload` â€” projects and portfolio budget per team member
- `top_clients` â€” up to eight clients ordered by project count
- `recent_completions` â€” latest completed projects

Example `kpis`:

```json
{
  "total_clients": 14,
  "total_projects": 20,
  "active_projects": 16,
  "completed_projects": 3,
  "overdue_projects": 0,
  "completion_rate": 15,
  "average_progress": 48,
  "total_budget": 6466000,
  "active_budget": 6055000,
  "completed_budget": 411000
}
```

---

## Clients

### GET `/api/clients`

Query parameters:

| Parameter | Type | Notes |
| --- | --- | --- |
| `q` | string | Searches name, company, email and phone |
| `status` | enum | `active`, `inactive`, `prospect` |
| `sort` | string | `name`, `company_name`, `email`, `status`, `projects_count`, `created_at` |
| `direction` | enum | `asc` or `desc` |
| `per_page` | integer | 1â€“100, default 10 |
| `page` | integer | Page number |

Example: `GET /api/clients?q=everest&status=active&sort=company_name&direction=asc&per_page=5`

Client object:

```json
{
  "id": 5,
  "name": "Tenzing Sherpa",
  "company_name": "Everest Trails Adventures",
  "email": "tenzing@everesttrails.example",
  "phone": "+977 9801234505",
  "address": "Lazimpat, Kathmandu",
  "status": "active",
  "status_label": "Active",
  "notes": "Adventure travel operator with three seasonal peaks.",
  "logo_url": "http://127.0.0.1:8000/uploads/clients/example.png",
  "initials": "ET",
  "projects_count": 2,
  "created_at": "2026-05-22T05:26:39+00:00",
  "created_at_human": "6 months ago",
  "updated_at": "2026-05-22T05:26:39+00:00"
}
```

### POST `/api/clients`

Accepts JSON or `multipart/form-data` (for `logo`).

Required: `name`, `company_name`, `email` (unique), `phone`, `status`.

Optional: `address`, `notes`, `logo` (JPG/PNG/WebP, max 2 MB).

```json
{
  "name": "Sneha Gurung",
  "company_name": "Kathmandu Codeworks",
  "email": "sneha@ktmcodeworks.example",
  "phone": "+977 9801234502",
  "address": "Lalitpur, Bagmati",
  "status": "active",
  "notes": "Long-term retainer client."
}
```

Returns HTTP 201 with the created client and `message: "Client created successfully."`.

### GET `/api/clients/{id}`

Returns the client with `projects_count`, a `projects` array (project summaries) and `creator`.

### PUT/PATCH `/api/clients/{id}`

Same fields as create. `email` is unique except for the current client. Send `remove_logo=1` to delete the existing logo.

### DELETE `/api/clients/{id}`

Admin only. Clients with linked projects return HTTP 409:

```json
{
  "success": false,
  "message": "This client has projects linked to it. Reassign or delete those projects first."
}
```

---

## Projects

### GET `/api/projects`

Query parameters:

| Parameter | Type | Notes |
| --- | --- | --- |
| `q` | string | Searches project name and client company/contact |
| `status` | enum | `planning`, `not_started`, `in_progress`, `on_hold`, `completed`, `cancelled` |
| `priority` | enum | `low`, `medium`, `high`, `urgent` |
| `client_id` | integer | Filter by client |
| `assigned_to` | integer | Filter by team member |
| `overdue` | boolean | `1` returns past-due, non-completed/cancelled projects |
| `sort` | string | `project_name`, `status`, `priority`, `progress`, `expected_completion_date`, `created_at` |
| `direction` | enum | `asc` or `desc` |
| `per_page` | integer | 1â€“100, default 10 |
| `page` | integer | Page number |

Project object:

```json
{
  "id": 1,
  "code": "PRJ-0001",
  "project_name": "Corporate Website Redesign",
  "description": "A complete redesign of the corporate website...",
  "client_id": 2,
  "status": "in_progress",
  "status_label": "In Progress",
  "priority": "high",
  "priority_label": "High",
  "start_date": "2026-06-22",
  "expected_completion_date": "2026-12-19",
  "budget": 185000,
  "budget_formatted": "NPR 185,000.00",
  "progress": 65,
  "is_overdue": false,
  "completed_at": null,
  "client": {
    "id": 2,
    "name": "Sneha Gurung",
    "company_name": "Kathmandu Codeworks",
    "email": "sneha@ktmcodeworks.example",
    "phone": "+977 9801234502",
    "status": "active",
    "status_label": "Active",
    "logo_url": null,
    "initials": "KC"
  },
  "assigned_to": {
    "id": 2,
    "name": "Prakriti Adhikari",
    "email": "prakriti@sajilocrm.test",
    "role": "staff",
    "role_label": "Staff",
    "initials": "PA"
  },
  "created_at": "2026-06-02T05:26:39+00:00",
  "created_at_human": "5 months ago",
  "updated_at": "2026-06-02T05:26:39+00:00"
}
```

### POST `/api/projects`

Required: `client_id`, `project_name`, `status`, `priority`.

Optional: `assigned_to`, `description`, `start_date`, `expected_completion_date` (must be on/after the start date), `budget`, `progress` (0â€“100).

```json
{
  "client_id": 2,
  "assigned_to": 2,
  "project_name": "Corporate Website Redesign",
  "description": "Responsive marketing site with a lightweight CMS.",
  "status": "in_progress",
  "priority": "high",
  "start_date": "2026-06-22",
  "expected_completion_date": "2026-12-19",
  "budget": 185000,
  "progress": 65
}
```

Staff-created projects ignore `assigned_to` and are assigned to the signed-in staff member. Setting `status` to `completed` forces progress to 100 and records `completed_at`.

### PUT/PATCH `/api/projects/{id}`

Admin can update any project and reassign it. Staff can update only projects assigned to them and cannot change the assignee.

Reopening a completed project (any status other than `completed`) clears `completed_at`.

### DELETE `/api/projects/{id}`

Admin only.

---

## Team members (admin only)

All `/api/users` routes return HTTP 403 for staff accounts.

### GET `/api/users`

Query parameters: `q` (name, email, job title), `role` (`admin`/`staff`), `sort` (`name`, `email`, `role`, `created_at`), `direction`, `per_page`, `page`.

User object includes `role`, `role_label`, `phone`, `job_title`, `initials`, `last_login_at`, `last_login_human` and `assigned_projects_count`.

### POST `/api/users`

```json
{
  "name": "Rohan Karki",
  "email": "rohan@sajilocrm.test",
  "role": "staff",
  "phone": "+977 9801000203",
  "job_title": "Frontend Developer",
  "password": "Sajilo@2026",
  "password_confirmation": "Sajilo@2026"
}
```

Passwords require at least 8 characters with letters and numbers.

### PUT/PATCH `/api/users/{id}`

All fields are required except `phone`, `job_title` and `password`. Leaving `password` blank keeps the current password.

An administrator cannot remove their own administrator role. They also cannot delete their own account.

### DELETE `/api/users/{id}`

Removes the account. Projects assigned to that user become unassigned.

---

## Settings

### PUT `/api/settings/profile`

```json
{
  "name": "Arya Gautam",
  "email": "arya22013071@iimscollege.edu.np",
  "phone": "+977 9801000201",
  "job_title": "Frontend Developer & Backend Support (intern)"
}
```

### PUT `/api/settings/password`

```json
{
  "current_password": "Sajilo@2026",
  "password": "NewPassword456",
  "password_confirmation": "NewPassword456"
}
```

On success, all other API tokens for the account are revoked; the token used for the request stays valid.

---

## Errors and policies

- API authentication uses Sanctum. Missing or invalid tokens return 401.
- Staff cannot delete clients or projects, cannot manage team members, and cannot update another member's project.
- `DELETE /api/clients/{id}` returns 409 when the client has projects.
- Not-found API records return:

```json
{
  "success": false,
  "message": "The requested resource was not found."
}
```

- Authorization failures return:

```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

## Postman

Import `docs/Sajilo-CRM.postman_collection.json`. The collection defines:

- `baseUrl` â€” default `http://127.0.0.1:8000`
- `token` â€” filled automatically by the **Auth â†’ Login** request's test script
- `clientId` and `projectId` â€” seeded defaults used by detail/update requests

Run **Auth â†’ Login** first, then any other request.

# Todo Automation

Todo Automation is a Laravel application for keeping track of tasks and automating a daily review. Its Model Context Protocol (MCP) server exposes todo operations so a compatible MCP client can list, create, update, and delete tasks. A daily review command gathers incomplete tasks due that day, asks Google Gemini to write a short summary, and emails the summary along with an alert for tasks due within the next two hours.

## Features

- Create, list, update, and delete todos with a title, description, completion status, and optional due date and time.
- Filter todos by completion status and due date.
- Use the `daily_summary` MCP prompt to prepare a summary for a selected date.
- Run `app:morning-task-review` to email a Gemini-generated summary and urgent task alerts. If Gemini is unavailable, the email falls back to a plain task list.
- Interpret task dates and times using the configured task timezone (defaults to `Africa/Kigali`). Due dates are stored in UTC.

## Requirements

- PHP 8.1 or later with the extensions required by Laravel 10 and Composer
- Node.js and npm
- MySQL (or another Laravel-supported database configured in `.env`)
- A Google Gemini API key for AI summaries (optional; the review command can fall back to a plain list)
- A configured mail transport and recipient for sending review emails

## Setup

1. Install PHP and JavaScript dependencies:

   ```sh
   composer install
   npm install
   ```

2. Create your local environment file and application key:

   ```sh
   cp .env.example .env
   php artisan key:generate
   ```

3. Update `.env` with your database connection, mail settings, task timezone, and (optionally) Gemini API key. For the default MySQL configuration, create the database named in `DB_DATABASE` and set `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD`. Add the destination for review emails:

   ```dotenv
   EMAIL_ADDRESS=you@example.com
   TASK_TIMEZONE=Africa/Kigali
   GEMINI_API_KEY=your-gemini-api-key
   ```

   Set `EMAIL_ADDRESS` to an inbox you control. Configure `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, and `MAIL_FROM_ADDRESS` for your mail provider. For local development, a mail catcher such as Mailpit can be used.

4. Create the database tables:

   ```sh
   php artisan migrate
   ```

## Open the MCP server

Use Laravel MCP's inspector to connect to the registered `todo` server and manage tasks:

```sh
php artisan mcp:inspector toodo
```

## MCP server

The project registers a local MCP server named `todo` and a web MCP endpoint at `/mcp/todo`. The server provides these tools:

| Tool | Purpose |
| --- | --- |
| `list_todos` | List tasks, optionally filtered by completion status or due date |
| `create_todo` | Create a task; provide times in 24-hour `HH:MM` format |
| `update_todo` | Update task fields, including due date and time |
| `delete_todo` | Delete a task by ID |

The `daily_summary` prompt accepts a date in `YYYY-MM-DD` format and returns a prompt containing incomplete tasks due that day.

## Daily email review

Run the review manually with:

```sh
php artisan app:morning-task-review
```

The command checks incomplete tasks due today in `TASK_TIMEZONE`, generates a summary with Gemini when available, and sends the email to `EMAIL_ADDRESS`. It also includes a separate urgent alert for tasks due within two hours. To automate it, schedule this command in your host's cron or other scheduler at your preferred morning time; the project does not currently register a Laravel scheduler entry for it.

## Tests

Run the Laravel test suite with:

```sh
php artisan test
```

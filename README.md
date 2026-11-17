# Todo Automation

Todo Automation is a Laravel application for keeping track of tasks and automating daily reviews and due-soon reminders. Its Model Context Protocol (MCP) server exposes todo operations so a compatible MCP client can list, create, update, and delete tasks. Scheduled background commands gather incomplete tasks due that day for a Google Gemini summary and check every 15 minutes for tasks due within the next two hours, sending notifications asynchronously via Laravel's queue system.

## Features

- Create, list, update, and delete todos with a title, description, completion status, and optional due date and time via the web UI or MCP tools.
- Filter todos by completion status and due date.
- Use the `daily_summary` MCP prompt to prepare a summary for a selected date.
- Run `app:morning-task-review` daily at 07:00 to email a Gemini-generated summary of today's tasks. If Gemini is unavailable, the email falls back to a plain task list.
- Run `app:due-soon-task-alert` every 15 minutes to send an alert for incomplete tasks due within the next two hours (with caching to prevent duplicate alerts).
- Process scheduled task reviews and alert emails asynchronously using Laravel queues.
- Interpret task dates and times using the configured task timezone (defaults to `Africa/Kigali`). Due dates are stored in UTC.

## Requirements

- PHP 8.1 or later with the extensions required by Laravel 10 and Composer
- Node.js and npm
- MySQL (or another Laravel-supported database configured in `.env`)
- A queue driver configured in `.env` (such as `database` or `redis`)
- A Google Gemini API key for AI summaries (optional; the review command falls back to a plain list)
- A configured mail transport and recipient for sending review and alert emails

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

3. Update `.env` with your database connection, queue driver, mail settings, task timezone, and (optionally) Gemini API key. For the default MySQL configuration, create the database named in `DB_DATABASE` and set `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD`:

   ```dotenv
   QUEUE_CONNECTION=database
   EMAIL_ADDRESS=you@example.com
   TASK_TIMEZONE=Africa/Kigali
   GEMINI_API_KEY=your-gemini-api-key
   ```

   Set `EMAIL_ADDRESS` to an inbox you control. Configure `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, and `MAIL_FROM_ADDRESS` for your mail provider. For local development, a mail catcher such as Mailpit can be used.

4. Create the database tables (including the `jobs` table if using the `database` queue driver):

   ```sh
   php artisan migrate
   ```

## Open the MCP server

Use Laravel MCP's inspector to connect to the registered `todo` server and manage tasks:

```sh
php artisan mcp:inspector todo
```

## MCP server

The project registers a local MCP server named `todo` and a web MCP endpoint at `/mcp/todo`. The server provides these tools:

| Tool | Purpose |
| --- | --- |
| `list_todos` | List tasks, optionally filtered by completion status or due date (`YYYY-MM-DD`) |
| `create_todo` | Create a task; provide due dates and times in `DD-MM-YYYY HH:MM` format (e.g., `30-11-2026 09:15`) |
| `update_todo` | Update task fields, including due date and time (`DD-MM-YYYY HH:MM`) |
| `delete_todo` | Delete a task by ID |

The `daily_summary` prompt accepts a date in `YYYY-MM-DD` format and returns a prompt containing incomplete tasks due that day.

## Automated emails, scheduler & queues

The application uses two console commands registered in `app/Console/Kernel.php` and dispatches emails through `TaskAlertMail`:

- **`app:morning-task-review`**: Runs daily at `07:00` (`TASK_TIMEZONE`) to check incomplete tasks due today, generate an AI summary with Gemini when available, and email `EMAIL_ADDRESS`.
- **`app:due-soon-task-alert`**: Runs every 15 minutes to check for incomplete tasks due within the next two hours and sends an alert email to `EMAIL_ADDRESS`.

### Running the queue worker and scheduler

Because scheduled tasks and alert emails are processed asynchronously via queues, run both a queue worker and the Laravel scheduler:

1. Start the queue worker to process background jobs and queued `TaskAlertMail` deliveries:

   ```sh
   php artisan queue:work
   ```

2. Start the scheduler locally during development:

   ```sh
   php artisan schedule:work
   ```

   In production, add a single cron entry on your server to run the scheduler every minute:

   ```sh
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

### Manual testing

You can trigger either command manually at any time:

```sh
php artisan app:morning-task-review
php artisan app:due-soon-task-alert
```

## Tests

Run the Laravel test suite with:

```sh
php artisan test
```

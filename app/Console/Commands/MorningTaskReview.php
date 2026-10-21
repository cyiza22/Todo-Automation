<?php

namespace App\Console\Commands;

use App\Mail\TaskAlertMail;
use App\Models\Todo;
use Carbon\Carbon;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MorningTaskReview extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:morning-task-review';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Fetch pending tasks from database (simulating what the MCP tool would hand over)
        $tasks = Todo::where('completed', false)
            ->whereDate('due_date', Carbon::today())
            ->get();

        if ($tasks->isEmpty()) {
            $this->info('No tasks due today.');
            return;
        }

        // 2. Format tasks into a context payload for the free AI model
        $taskContext = $tasks->map(function ($task) {
            return "- Task: {$task->title} | Due: {$task->due_date}";
        })->implode("\n");

        // 3. Draft prompt for the AI to summarize
        $prompt = "You are an automated task supervisor assistant. Review the following tasks due today:\n\n"
                . $taskContext . "\n\n"
                . "Provide a brief, encouraging morning summary paragraph of what needs to be done.";

        // 4. Call the free tier model
        $response = Gemini::gemini15Flash()->generateContent($prompt);
        $summaryText = $response->text();

        $this->info("Morning AI Summary generated successfully.");

        // 5. Check for critical tasks due within the next 2 hours
        $now = Carbon::now();
        $twoHoursFromNow = Carbon::now()->addHours(2);

        $criticalTasks = $tasks->filter(function ($task) use ($now, $twoHoursFromNow) {
            if (!$task->due_date) return false;
            $dueTime = Carbon::parse($task->due_date);
            return $dueTime->between($now, $twoHoursFromNow);
        });

        // 6. Automate Email Alert if critical items exist
        if ($criticalTasks->isNotEmpty()) {
            $emailContent = "### Urgent Task Alert\n\nYou have tasks due within the next 2 hours:\n\n";
            foreach ($criticalTasks as $task) {
                $emailContent .= "- **{$task->title}** (Due: " . Carbon::parse($task->due_date)->format('H:i') . ")\n";
            }
            
            // Send the email automatically
            Mail::to('your-email@example.com')->send(new TaskAlertMail($summaryText, $emailContent));
            $this->info("Urgent reminder email sent!");
        }
    
    }
}

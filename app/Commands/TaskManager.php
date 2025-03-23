<?php

namespace App\Commands;
#!/usr/bin/env php

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class TaskManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task-cli {action} {id?} {description?}'; // Keep as it is



    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Task Tracker CLI: Add, update, delete, and list tasks.';

    protected $filePath = 'tasks.json';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $id = $this->argument('id');
        $description = $this->argument('description');
    
        switch ($action) {
            case 'add':
                if (!$description) {
                    $description = $this->ask('Enter task description:');
                }
                $this->addTask($description);
                break;
            case 'update':
                if (!$id) {
                    $this->error("Task ID is required.");
                    return;
                }
                if (!$description) {
                    $description = $this->ask('Enter new task description:');
                }
                $this->updateTask($id, $description);
                break;
            case 'delete':
                $this->deleteTask($id);
                break;
            case 'mark-in-progress':
                $this->updateStatus($id, 'in-progress');
                break;
            case 'mark-done':
                $this->updateStatus($id, 'done');
                break;
            case 'list':
                $this->listTasks($id);
                break;
            default:
                $this->error("Invalid action. Use: add, update, delete, mark-in-progress, mark-done, list");
        }
    }
    
    

    protected function getTasks() {
        return File::exists($this->filePath) ? json_decode(File::get($this->filePath), true) : [];
    }


    protected function saveTask($task) {
        File::put($this->filePath, json_encode($task, JSON_PRETTY_PRINT));
    }

    protected function addTask($description) {
        if(!$description) {
            $this->error('Task description is required.');
            return;
        }

        $tasks = $this->getTasks();
        $taskId = count($tasks) + 1;
        $now = Carbon::now()->toDateTimeString();

        $tasks[] = [
            'id' => $taskId,
            'description' => $description,
            'status' => 'todo',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->saveTask($tasks);
        $this->info("Task added successfully (ID: $taskId)");
    }

    protected function updateTask($id, $description) {

        if (!$id || !$description) {
            $this->error("Both task ID & description are required");
            return;
        }

        $tasks = $this->getTasks();

        $found = false;

        foreach ($tasks as &$task) {
            if ($task['id'] == $id) {
                $task['description'] = $description;
                $task['updated_at'] = Carbon::now()->toDateTimeString();
                $found = true;
                $this->info("Task Updated: (ID: $id) -> $description");
                break;
            }
        }

        if (!$found) {
            $this->error("Task not found");
            return;
        }

        $this->saveTask($tasks);
    }

    protected function updateStatus($id, $status) {
        if (!$id) {
            $this->error("Task ID is required.");
            return;
        }

        $tasks = $this->getTasks();
        $found = false;

        foreach ($tasks as &$task) {
            if ($task['id'] == $id) {
                $task['status'] = $status;
                $task['updated_at'] = Carbon::now()->toDateTimeString();
                $found = true;
                $this->info("Task (ID: $id) marked as $status");
                break;
            }
        }

        if (!$found) {
            $this->error("Task not found.");
            return;
        }

        $this->saveTask($tasks);
    }

    protected function listTasks($status = null)
    {
        $tasks = $this->getTasks();
        
        if (empty($tasks)) {
            $this->info("No tasks found.");
            return;
        }
    
        if ($status && in_array($status, ['todo', 'in-progress', 'done'])) {
            $tasks = array_filter($tasks, fn($task) => $task['status'] == $status);
        }
    
        // Format tasks into a table-friendly structure
        $formattedTasks = array_map(function ($task) {
            return [
                'ID' => $task['id'],
                'Description' => $task['description'],
                'Status' => ucfirst($task['status']),
                'Created_at' => $task['created_at'] ?? 'N/A',
                'Updated_at' => $task['updated_at'] ?? 'N/A',
            ];
        }, $tasks);
    
        // Display tasks in a table format
        $this->table(['ID', 'Description', 'Status', 'Created_at', 'Updated_at'], $formattedTasks);
    }
    
    protected function deleteTask($id) {
        if (!$id) {
            $this->error("Task ID is required.");
            return;
        }

        $tasks = $this->getTasks();
        $filteredTasks = array_filter($tasks, fn($task) => $task['id'] != $id);

        if (count($tasks) === count($filteredTasks)) {
            $this->error("Task not found.");
            return;
        }

        $this->saveTask(array_values($filteredTasks));
        $this->info("Task deleted successfully (ID: $id)");

    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}

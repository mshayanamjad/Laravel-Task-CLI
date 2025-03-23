# Adding a new task

php artisan task-cli add "Buy groceries"

# Output: Task added successfully (ID: 1)

# Updating and deleting tasks

php artisan task-cli update 1 "Buy groceries and cook dinner"
php artisan task-cli delete 1

# Marking a task as in progress or done

php artisan task-cli mark-in-progress 1
php artisan task-cli mark-done 1

# Listing all tasks

php artisan task-cli list

# Listing tasks by status

php artisan task-cli list done
php artisan task-cli list todo
php artisan task-cli list in-progress

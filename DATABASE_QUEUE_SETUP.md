# Database Queue Setup Guide (Simplified)

## Step 1: Create Queue Database Tables

Run these commands in your project root:

```bash
# Create queue tables migration
php artisan queue:table

# Create failed jobs table
php artisan queue:failed-table

# Run migrations
php artisan migrate
```

This will create two tables:
- `jobs` - Stores pending jobs
- `failed_jobs` - Stores failed jobs for debugging

## Step 2: Update .env Configuration

Update your `.env` file:

```env
# Change from sync to database
QUEUE_CONNECTION=database

# Mail configuration (example with Gmail)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD="your-app-specific-password"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="Tender Portal"
```

## Step 3: Install Supervisor on Your Server

### For Ubuntu/Debian:
```bash
sudo apt update
sudo apt install supervisor
```

### For CentOS/RHEL:
```bash
sudo yum install epel-release
sudo yum install supervisor
sudo systemctl enable supervisord
```

## Step 4: Create Supervisor Configuration

Create file `/etc/supervisor/conf.d/tender-portal-worker.conf`:

```ini
[program:tender-portal-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/tender_portal/artisan queue:work database --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/tender_portal/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=10
stopwaitsecs=3600
```

**Important**: Update these paths in the config:
- Change `/var/www/tender_portal` to your actual project path
- Change `user=www-data` to your web server user (might be `nginx`, `apache`, etc.)
- For database queue, 2 workers (`numprocs=2`) is usually sufficient

## Step 5: Start Queue Workers

```bash
# Create log file
sudo touch /var/www/tender_portal/storage/logs/worker.log
sudo chown www-data:www-data /var/www/tender_portal/storage/logs/worker.log

# Reload Supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start tender-portal-worker:*

# Check status
sudo supervisorctl status
```

## Step 6: Test Email Sending

1. Create a test command to verify email sending:

```bash
php artisan tinker
```

Then in tinker:
```php
// Test email sending directly
$tender = \App\Models\Tender::latest()->first();
\App\Jobs\SendTenderNotificationJob::dispatch($tender);
exit
```

2. Check if job was processed:
```bash
# View worker logs
tail -f /var/www/tender_portal/storage/logs/worker.log

# Check jobs table (should be empty if processed)
php artisan tinker
>>> \DB::table('jobs')->count();

# Check failed jobs
php artisan queue:failed
```

## Step 7: Common Commands

### Managing Workers:
```bash
# Restart workers (after code changes)
sudo supervisorctl restart tender-portal-worker:*

# Stop workers
sudo supervisorctl stop tender-portal-worker:*

# Start workers
sudo supervisorctl start tender-portal-worker:*
```

### Managing Jobs:
```bash
# List failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry 5

# Delete all failed jobs
php artisan queue:flush

# Delete specific failed job
php artisan queue:forget 5
```

### Monitoring:
```bash
# Watch worker logs
tail -f storage/logs/worker.log

# Check jobs in queue
php artisan tinker
>>> \DB::table('jobs')->count();
>>> \DB::table('jobs')->get();

# Check Laravel logs for errors
tail -f storage/logs/laravel.log
```

## Step 8: Troubleshooting

### Issue: Jobs not being processed

1. Check if workers are running:
```bash
sudo supervisorctl status
```

2. Check if jobs are in the database:
```bash
php artisan tinker
>>> \DB::table('jobs')->get();
```

3. Check worker logs for errors:
```bash
tail -100 storage/logs/worker.log
```

### Issue: Emails not sending

1. Test mail configuration:
```bash
php artisan tinker
>>> Mail::raw('Test email', function($message) {
...     $message->to('test@example.com')->subject('Test');
... });
```

2. Check Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

### Issue: Permission errors

```bash
# Fix storage permissions
sudo chown -R www-data:www-data /var/www/tender_portal/storage
sudo chmod -R 775 /var/www/tender_portal/storage
```

## Quick Setup Script

Save this as `setup-database-queue.sh` and run on your server:

```bash
#!/bin/bash

# Quick Database Queue Setup Script

echo "Setting up Database Queue for Tender Portal..."

# Get project path
read -p "Enter your project path (e.g., /var/www/tender_portal): " PROJECT_PATH
cd $PROJECT_PATH

# Run migrations
echo "Creating queue tables..."
php artisan queue:table
php artisan queue:failed-table
php artisan migrate --force

# Install Supervisor
echo "Installing Supervisor..."
sudo apt update && sudo apt install -y supervisor

# Create Supervisor config
echo "Creating Supervisor configuration..."
sudo tee /etc/supervisor/conf.d/tender-portal-worker.conf > /dev/null <<EOF
[program:tender-portal-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${PROJECT_PATH}/artisan queue:work database --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${PROJECT_PATH}/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=10
stopwaitsecs=3600
EOF

# Set permissions
echo "Setting permissions..."
sudo touch ${PROJECT_PATH}/storage/logs/worker.log
sudo chown -R www-data:www-data ${PROJECT_PATH}/storage
sudo chmod -R 775 ${PROJECT_PATH}/storage

# Start workers
echo "Starting queue workers..."
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start tender-portal-worker:*

# Show status
sudo supervisorctl status

echo "Done! Remember to update your .env file:"
echo "QUEUE_CONNECTION=database"
```

## Production Checklist

- [ ] Run `php artisan queue:table` and `php artisan migrate`
- [ ] Update `.env`: `QUEUE_CONNECTION=database`
- [ ] Configure mail settings in `.env`
- [ ] Install Supervisor
- [ ] Create Supervisor configuration file
- [ ] Start queue workers
- [ ] Test email sending
- [ ] Set up log rotation
- [ ] Monitor failed jobs regularly

## Performance Tips for Database Queue

1. **Keep jobs table clean**: Processed jobs are automatically deleted
2. **Monitor table size**:
   ```sql
   SELECT COUNT(*) FROM jobs;
   SELECT COUNT(*) FROM failed_jobs;
   ```
3. **Add index for better performance** (if high volume):
   ```php
   // In a new migration
   Schema::table('jobs', function ($table) {
       $table->index(['queue', 'reserved_at']);
   });
   ```
4. **Clear old failed jobs periodically**:
   ```bash
   # Add to cron
   php artisan queue:flush
   ```

That's it! Database queue is simpler than Redis and works great for most applications.
# Queue and Supervisor Setup Guide for Tender Portal

## Prerequisites
- Ubuntu/Debian server (for other OS, adapt commands accordingly)
- Laravel application deployed on the server
- Database configured for queue jobs

## Step 1: Configure Laravel Queue

### 1.1 Update .env file
```bash
# Change from sync to database (or redis for better performance)
QUEUE_CONNECTION=database

# If using Redis (recommended for production)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 1.2 Create queue database table (if using database driver)
```bash
php artisan queue:table
php artisan migrate
```

### 1.3 Install Redis (if using Redis - Recommended)
```bash
sudo apt update
sudo apt install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Install PHP Redis extension
sudo apt install php-redis

# Install Laravel Redis package
composer require predis/predis
```

## Step 2: Install Supervisor

### 2.1 Install Supervisor on Ubuntu/Debian
```bash
sudo apt update
sudo apt install supervisor
```

### 2.2 Install Supervisor on CentOS/RHEL
```bash
sudo yum install epel-release
sudo yum install supervisor
sudo systemctl enable supervisord
```

## Step 3: Configure Supervisor

### 3.1 Copy the configuration file
```bash
# Copy the provided supervisor configuration to the appropriate directory
sudo cp supervisor-tender-portal.conf /etc/supervisor/conf.d/tender-portal-worker.conf
```

### 3.2 Update the configuration file
Edit `/etc/supervisor/conf.d/tender-portal-worker.conf` and update these values:

```ini
[program:tender-portal-worker]
process_name=%(program_name)s_%(process_num)02d

# Update this path to match your server's project location
command=php /var/www/tender_portal/artisan queue:work --sleep=3 --tries=3 --max-time=3600

autostart=true
autorestart=true
stopasgroup=true
killasgroup=true

# Update user to match your web server user (www-data, nginx, apache, etc.)
user=www-data

# Number of worker processes (adjust based on server capacity)
numprocs=4

redirect_stderr=true

# Update log path if needed
stdout_logfile=/var/www/tender_portal/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=10
stopwaitsecs=3600
```

### Configuration Options Explained:
- **numprocs=4**: Runs 4 worker processes (adjust based on server capacity and email volume)
- **--sleep=3**: Worker sleeps for 3 seconds when queue is empty
- **--tries=3**: Retry failed jobs 3 times
- **--max-time=3600**: Restart worker every hour to prevent memory leaks
- **autostart=true**: Start workers automatically when supervisor starts
- **autorestart=true**: Restart workers if they crash

## Step 4: Start Supervisor

### 4.1 Reload Supervisor configuration
```bash
sudo supervisorctl reread
sudo supervisorctl update
```

### 4.2 Start the workers
```bash
sudo supervisorctl start tender-portal-worker:*
```

### 4.3 Check status
```bash
sudo supervisorctl status
```

You should see output like:
```
tender-portal-worker:tender-portal-worker_00   RUNNING   pid 12345, uptime 0:00:10
tender-portal-worker:tender-portal-worker_01   RUNNING   pid 12346, uptime 0:00:10
tender-portal-worker:tender-portal-worker_02   RUNNING   pid 12347, uptime 0:00:10
tender-portal-worker:tender-portal-worker_03   RUNNING   pid 12348, uptime 0:00:10
```

## Step 5: Common Supervisor Commands

```bash
# View all processes
sudo supervisorctl status

# Start all tender portal workers
sudo supervisorctl start tender-portal-worker:*

# Stop all tender portal workers
sudo supervisorctl stop tender-portal-worker:*

# Restart all tender portal workers
sudo supervisorctl restart tender-portal-worker:*

# Start specific worker
sudo supervisorctl start tender-portal-worker:tender-portal-worker_00

# View logs
sudo tail -f /var/www/tender_portal/storage/logs/worker.log

# Reload configuration after changes
sudo supervisorctl reread
sudo supervisorctl update
```

## Step 6: Monitor Queue Performance

### 6.1 Check failed jobs
```bash
php artisan queue:failed
```

### 6.2 Retry failed jobs
```bash
# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry 5
```

### 6.3 Clear failed jobs
```bash
php artisan queue:flush
```

### 6.4 Monitor in real-time
```bash
php artisan queue:listen
```

## Step 7: Laravel Horizon (Optional - For Redis Only)

For better queue monitoring with Redis, consider installing Laravel Horizon:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

Create Horizon supervisor configuration:
```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/tender_portal/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/tender_portal/storage/logs/horizon.log
stopwaitsecs=3600
```

## Step 8: Troubleshooting

### Common Issues and Solutions:

#### 1. Workers not processing jobs
- Check queue connection in .env
- Verify database/redis connection
- Check worker logs: `tail -f /var/www/tender_portal/storage/logs/worker.log`

#### 2. Permission errors
```bash
# Fix storage permissions
sudo chown -R www-data:www-data /var/www/tender_portal/storage
sudo chmod -R 775 /var/www/tender_portal/storage
```

#### 3. Workers consuming too much memory
- Reduce `--max-time` to restart workers more frequently
- Reduce number of workers (numprocs)
- Add memory limit: `--memory=512`

#### 4. Jobs failing
```bash
# Check failed jobs table
php artisan queue:failed

# Check Laravel logs
tail -f /var/www/tender_portal/storage/logs/laravel.log
```

## Step 9: Production Checklist

- [ ] Queue driver changed from `sync` to `database` or `redis`
- [ ] Queue tables migrated (if using database)
- [ ] Redis installed and configured (if using redis)
- [ ] Supervisor installed
- [ ] Supervisor configuration file created and updated with correct paths
- [ ] Correct user permissions set
- [ ] Workers started and running
- [ ] Log rotation configured
- [ ] Monitoring setup (logs, failed jobs, etc.)
- [ ] Email configuration tested in production
- [ ] Firewall rules updated if needed

## Step 10: Testing Email Notifications

After setup, test the email system:

1. Create a test tender in the application
2. Check worker logs for processing:
   ```bash
   tail -f /var/www/tender_portal/storage/logs/worker.log
   ```
3. Verify emails are being sent
4. Check failed jobs if emails aren't sending:
   ```bash
   php artisan queue:failed
   ```

## Additional Performance Tips

1. **Use Redis for better performance**: Database queues are good for small applications, but Redis is faster for high-volume email sending.

2. **Optimize worker count**: Start with 2-4 workers and adjust based on:
   - Server CPU and memory
   - Email volume
   - Average job processing time

3. **Set up monitoring**: Use tools like:
   - Laravel Telescope
   - Laravel Horizon (for Redis)
   - External monitoring services (New Relic, Datadog)

4. **Configure email throttling** to avoid hitting provider limits:
   ```php
   // In your job
   Redis::throttle('emails')->allow(30)->every(60)->then(function () {
       // Send email
   });
   ```

## Support

For issues specific to your server environment, consult:
- Laravel Queue Documentation: https://laravel.com/docs/queues
- Supervisor Documentation: http://supervisord.org/
- Your hosting provider's documentation
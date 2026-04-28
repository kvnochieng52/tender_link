#!/bin/bash

# Deploy script for tender notification system
# Run this on your Ubuntu server

echo "Setting up Tender Notification System..."

# Navigate to project directory
cd /app/tenderplug

# Run migrations
echo "Running migrations..."
php artisan migrate

# Clear and cache configurations
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up cron job if not exists
echo "Setting up cron job..."
(crontab -l 2>/dev/null | grep -q "schedule:run") || (crontab -l 2>/dev/null; echo "* * * * * cd /app/tenderplug && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /app/tenderplug/storage
chmod -R 775 /app/tenderplug/storage
chmod -R 775 /app/tenderplug/bootstrap/cache

# Install supervisor config if supervisor is installed
if command -v supervisorctl &> /dev/null; then
    echo "Setting up Supervisor for queue workers..."
    cp /app/tenderplug/supervisor-config.conf /etc/supervisor/conf.d/tenderplug-worker.conf
    supervisorctl reread
    supervisorctl update
    supervisorctl start tenderplug-worker:*
fi

# Test the scheduler
echo "Testing scheduler..."
php artisan schedule:list

echo "Setup complete! Notifications will be sent at 8:00 AM and 4:00 PM EAT daily."
echo "To test manually, run: php artisan tenders:notify"
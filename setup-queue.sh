#!/bin/bash

# Queue Setup Script for Tender Portal
# Run this script on your production server with sudo privileges

echo "================================"
echo "Tender Portal Queue Setup Script"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get project path
read -p "Enter the full path to your tender portal project (e.g., /var/www/tender_portal): " PROJECT_PATH

# Validate path exists
if [ ! -d "$PROJECT_PATH" ]; then
    echo -e "${RED}Error: Project path does not exist!${NC}"
    exit 1
fi

# Get web server user
read -p "Enter your web server user (www-data, nginx, apache, etc.) [default: www-data]: " WEB_USER
WEB_USER=${WEB_USER:-www-data}

# Check if user exists
if ! id "$WEB_USER" &>/dev/null; then
    echo -e "${RED}Error: User $WEB_USER does not exist!${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 1: Installing Supervisor...${NC}"
echo "================================"

# Detect OS and install supervisor
if [ -f /etc/debian_version ]; then
    # Debian/Ubuntu
    sudo apt-get update
    sudo apt-get install -y supervisor
elif [ -f /etc/redhat-release ]; then
    # CentOS/RHEL
    sudo yum install -y epel-release
    sudo yum install -y supervisor
    sudo systemctl enable supervisord
else
    echo -e "${RED}Unsupported OS. Please install Supervisor manually.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Supervisor installed${NC}"

echo ""
echo -e "${YELLOW}Step 2: Setting up Queue Configuration...${NC}"
echo "================================"

# Ask for queue driver preference
echo "Select your queue driver:"
echo "1) Database (Simple setup, good for small-medium apps)"
echo "2) Redis (Better performance, recommended for production)"
read -p "Enter your choice (1 or 2): " QUEUE_CHOICE

if [ "$QUEUE_CHOICE" = "2" ]; then
    echo -e "${YELLOW}Installing Redis...${NC}"

    if [ -f /etc/debian_version ]; then
        sudo apt-get install -y redis-server php-redis
        sudo systemctl enable redis-server
        sudo systemctl start redis-server
    elif [ -f /etc/redhat-release ]; then
        sudo yum install -y redis php-redis
        sudo systemctl enable redis
        sudo systemctl start redis
    fi

    echo -e "${GREEN}✓ Redis installed and started${NC}"

    # Install predis package
    cd "$PROJECT_PATH"
    composer require predis/predis --no-interaction

    QUEUE_CONNECTION="redis"
else
    # Setup database queue
    cd "$PROJECT_PATH"
    php artisan queue:table
    php artisan migrate --force

    QUEUE_CONNECTION="database"
fi

echo -e "${GREEN}✓ Queue driver configured: $QUEUE_CONNECTION${NC}"

echo ""
echo -e "${YELLOW}Step 3: Creating Supervisor Configuration...${NC}"
echo "================================"

# Ask for number of workers
read -p "How many queue workers do you want to run? [default: 4]: " NUM_WORKERS
NUM_WORKERS=${NUM_WORKERS:-4}

# Create supervisor config
SUPERVISOR_CONFIG="/etc/supervisor/conf.d/tender-portal-worker.conf"

sudo tee "$SUPERVISOR_CONFIG" > /dev/null <<EOF
[program:tender-portal-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${PROJECT_PATH}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${WEB_USER}
numprocs=${NUM_WORKERS}
redirect_stderr=true
stdout_logfile=${PROJECT_PATH}/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=10
stopwaitsecs=3600
EOF

echo -e "${GREEN}✓ Supervisor configuration created${NC}"

echo ""
echo -e "${YELLOW}Step 4: Setting Permissions...${NC}"
echo "================================"

# Set proper permissions
sudo chown -R ${WEB_USER}:${WEB_USER} ${PROJECT_PATH}/storage
sudo chmod -R 775 ${PROJECT_PATH}/storage

# Create worker log file if it doesn't exist
sudo touch ${PROJECT_PATH}/storage/logs/worker.log
sudo chown ${WEB_USER}:${WEB_USER} ${PROJECT_PATH}/storage/logs/worker.log

echo -e "${GREEN}✓ Permissions set${NC}"

echo ""
echo -e "${YELLOW}Step 5: Starting Queue Workers...${NC}"
echo "================================"

# Reload and start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start tender-portal-worker:*

echo -e "${GREEN}✓ Queue workers started${NC}"

echo ""
echo -e "${YELLOW}Step 6: Verifying Setup...${NC}"
echo "================================"

# Check status
sudo supervisorctl status tender-portal-worker:*

echo ""
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}✓ Queue setup completed successfully!${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo "Important next steps:"
echo "1. Update your .env file:"
echo "   QUEUE_CONNECTION=${QUEUE_CONNECTION}"
echo ""
echo "2. Test email sending by creating a new tender"
echo ""
echo "3. Monitor workers with:"
echo "   sudo supervisorctl status"
echo "   tail -f ${PROJECT_PATH}/storage/logs/worker.log"
echo ""
echo "4. Useful commands:"
echo "   - Restart workers: sudo supervisorctl restart tender-portal-worker:*"
echo "   - Stop workers: sudo supervisorctl stop tender-portal-worker:*"
echo "   - Check failed jobs: php artisan queue:failed"
echo "   - Retry failed jobs: php artisan queue:retry all"
echo ""
echo -e "${YELLOW}Remember to update your .env file with QUEUE_CONNECTION=${QUEUE_CONNECTION}${NC}"
#!/bin/bash
# This script sets up a CRON job to run cron.php every 24 hours
PHP_PATH=$(which php)
CRON_FILE="$(cd "$(dirname "$0")" && pwd)/cron.php"
LOG_FILE="$(cd "$(dirname "$0")" && pwd)/cron.log"

# Create the CRON job command
CRON_JOB="0 3 * * * $PHP_PATH $CRON_FILE >> $LOG_FILE 2>&1"

# Remove any previous job for this file
(crontab -l | grep -v "$CRON_FILE"; echo "$CRON_JOB") | crontab -

echo "CRON job added: $CRON_JOB" 
#!/bin/bash
set -e

echo "[go] Loading cron jobs..."
cron

echo "[go] Starting cron daemon..."
service cron start

echo "[go] Tailing cron logs..."
touch /var/log/cron.log
tail -f /var/log/cron.log

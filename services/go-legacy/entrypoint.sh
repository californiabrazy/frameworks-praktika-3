#!/bin/bash
set -e

echo "[go] Starting cron daemon..."
service cron start

echo "[go] Tailing cron logs..."
tail -f /var/log/syslog

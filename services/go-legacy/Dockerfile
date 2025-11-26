FROM golang:1.24.0 AS builder
WORKDIR /app

# Копируем только Go-зависимости
COPY go.mod go.sum ./
RUN go mod download

# Копируем только исходники Go, а не весь проект
COPY main.go ./
COPY telemetry-cron ./
COPY entrypoint.sh ./

# Сборка бинарника
RUN CGO_ENABLED=0 GOOS=linux go build -o legacycsv main.go


# -------------------------------
# RUNTIME
# -------------------------------
FROM debian:12-slim

# Установка cron
RUN apt-get update && apt-get install -y cron && rm -rf /var/lib/apt/lists/*

WORKDIR /opt/legacy

# Бинарник
COPY --from=builder /app/legacycsv /usr/local/bin/legacycsv

# cron job
COPY --from=builder /app/telemetry-cron /etc/cron.d/telemetry-cron
RUN chmod 0644 /etc/cron.d/telemetry-cron

# entrypoint
COPY --from=builder /app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Папка для CSV
RUN mkdir -p /data/csv

ENTRYPOINT ["/entrypoint.sh"]

FROM golang:1.24.0 as builder
WORKDIR /app
COPY go.mod go.sum ./
RUN go mod download
COPY main.go ./
RUN CGO_ENABLED=0 GOOS=linux go build -o legacycsv main.go

FROM debian:12-slim
WORKDIR /opt/legacy
COPY --from=builder /app/legacycsv /usr/local/bin/legacycsv
RUN mkdir -p /data/csv
ENTRYPOINT ["/usr/local/bin/legacycsv"]

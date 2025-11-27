package main

import (
	"context"
	"database/sql"
	"encoding/csv"
	"fmt"
	"log"
	"math/rand"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"

	_ "github.com/lib/pq"
	"github.com/xuri/excelize/v2"
)

// Вспомогательная функция
func Env(name, def string) string {
	val := os.Getenv(name)
	if val == "" {
		return def
	}
	return val
}

// XLSX
func GenerateXLSX(xlsxPath string, timestamp time.Time, voltage, temp float64, isValid bool, srcFile string) error {
	f := excelize.NewFile()
	sheet := "Telemetry"
	index, _ := f.NewSheet(sheet)

	headers := []string{"recorded_at", "voltage", "temp", "is_valid", "source_file"}
	for i, h := range headers {
		cell, _ := excelize.CoordinatesToCellName(i+1, 1)
		f.SetCellValue(sheet, cell, h)
	}

	// Запись данных
	f.SetCellValue(sheet, "A2", timestamp.Format(time.RFC3339))
	f.SetCellValue(sheet, "B2", voltage)
	f.SetCellValue(sheet, "C2", temp)
	f.SetCellValue(sheet, "D2", isValid)
	f.SetCellValue(sheet, "E2", srcFile)

	f.SetActiveSheet(index)

	err := f.SaveAs(xlsxPath)
	if err != nil {
		return fmt.Errorf("failed to save xlsx file: %w", err)
	}
	return nil
}

// Генерация данных, создание CSV и XLSX, вставка CSV в PostgreSQL
func GenerateAndCopy() error {
	outDir := Env("CSV_OUT_DIR", "/data/csv")

	timestamp := time.Now()
	fileName := fmt.Sprintf("telemetry_%s.csv", timestamp.Format("20060102_150405"))
	fullPath := filepath.Join(outDir, fileName)
	xlsxPath := strings.TrimSuffix(fullPath, ".csv") + ".xlsx"

	voltage := 3.2 + rand.Float64()*(12.6-3.2)
	temp := -50 + rand.Float64()*130
	isValid := rand.Intn(2) == 1

	if err := os.MkdirAll(outDir, 0755); err != nil {
		return fmt.Errorf("failed to create output directory: %w", err)
	}

	// CSV
	f, err := os.Create(fullPath)
	if err != nil {
		return fmt.Errorf("failed to create csv file: %w", err)
	}
	defer f.Close()

	csvWriter := csv.NewWriter(f)
	defer csvWriter.Flush()

	headers := []string{"recorded_at", "voltage", "temp", "is_valid", "source_file"}
	if err := csvWriter.Write(headers); err != nil {
		return fmt.Errorf("failed to write csv headers: %w", err)
	}

	record := []string{
		timestamp.Format(time.RFC3339),
		strconv.FormatFloat(voltage, 'f', 6, 64),
		strconv.FormatFloat(temp, 'f', 6, 64),
		strings.ToUpper(strconv.FormatBool(isValid)),
		fileName,
	}

	if err := csvWriter.Write(record); err != nil {
		return fmt.Errorf("failed to write csv record: %w", err)
	}

	// XLSX
	if err := GenerateXLSX(xlsxPath, timestamp, voltage, temp, isValid, fileName); err != nil {
		return err
	}

	// PostgreSQL
	host := Env("PGHOST", "db_csv")
	port := Env("PGPORT", "5432")
	user := Env("PGUSER", "csvuser")
	password := Env("PGPASSWORD", "csvpass")
	dbname := Env("PGDATABASE", "telemetry_data")

	psqlInfo := fmt.Sprintf("host=%s port=%s user=%s password=%s dbname=%s sslmode=disable",
		host, port, user, password, dbname)

	db, err := sql.Open("postgres", psqlInfo)
	if err != nil {
		return fmt.Errorf("failed to open postgres connection: %w", err)
	}
	defer db.Close()

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	// Вставка данных
	isValidBool := false
	if strings.ToUpper(record[3]) == "TRUE" {
		isValidBool = true
	}

	query := `INSERT INTO telemetry_legacy (recorded_at, voltage, temp, is_valid, source_file)
			  VALUES ($1, $2, $3, $4, $5)`

	_, err = db.ExecContext(ctx, query, timestamp, voltage, temp, isValidBool, fileName)
	if err != nil {
		return fmt.Errorf("failed to insert telemetry record: %w", err)
	}

	log.Printf("[go] Imported telemetry data from %s into PostgreSQL\n", fileName)
	return nil
}

func main() {
	rand.Seed(time.Now().UnixNano())

	periodSecStr := Env("GEN_PERIOD_SEC", "300")
	periodSec, err := strconv.Atoi(periodSecStr)
	if err != nil || periodSec <= 0 {
		periodSec = 300
	}

	log.Printf("[go] LegacyCSV generator started (period = %d sec)", periodSec)

	if err := GenerateAndCopy(); err != nil {
		log.Printf("[go][error] %v", err)
	} else {
		log.Printf("[go] %s — done", time.Now().Format("15:04:05"))
	}
}

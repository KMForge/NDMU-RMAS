@echo off
if "%1"=="--version" (
  echo pg_dump 16.0
  exit /b 0
)
exit /b 42

@echo off
title Monitor SEMEX C26
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0monitor-c26.ps1" -Puerto COM6 -SolicitarFlujo
if errorlevel 1 (
  echo.
  echo No se pudo abrir COM6. Cierra Tera Term u otro programa que este usando el puerto.
  pause
)

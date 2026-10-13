@echo off
powershell.exe -NoProfile -File "%~dp0tools\Start-Showcase.ps1"
if errorlevel 1 pause

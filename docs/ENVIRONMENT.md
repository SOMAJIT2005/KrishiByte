# KrishiByte — Local Environment Discovery & Setup

**Discovery Date:** 2026-10-12  
**Platform:** Windows 11 (x64)  
**Project Root:** `D:\KrishiByte`

## Discovered Tooling & Versions

- **PHP:** `8.2.33` (ZTS Visual C++ 2019 x64)
  - Loaded extensions: `bcmath`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `mysqli`, `mysqlnd`, `openssl`, `pdo`, `pdo_mysql`, `phar`, `xml`, `zip`, `zlib`.
- **Composer:** `2.10.3` (Installed in tools bin `C:\Users\nisla\.gemini\antigravity\bin\composer.phar` with batch wrapper).
- **MySQL / Wampserver:**
  - Wampserver Root: `D:\wamp64`
  - MySQL Version: `8.0.46` / Client `8.4.7`
  - Default Host: `127.0.0.1`
  - Default Port: `3306`
  - Default User: `root`
  - Default Password: `` (empty)
- **Node.js:** `v24.14.0`
- **npm:** `11.9.0`

## Selected Architecture & Framework

- **Framework:** Laravel 10.x / 11.x (compatible with PHP 8.2)
- **Database:** MySQL via Wampserver (`krishibyte` database)
- **Frontend:** Laravel Blade + Bootstrap 5 + Vanilla JS + Chart.js + Leaflet (OpenStreetMap)
- **SMS System:** Dummy / Mock Log Adapter (as requested, ready for SMS gateway provider when configured)
- **Runtime Server:** `php artisan serve` on port 8000

# Architecture Overview

This repository is now split into **controller**, **service**, and **view** layers to make maintenance and redesign easier.

## Request Flow

1. `index.php`
2. `app/bootstrap.php`
3. `app/dashboard_service.php`
4. `views/dashboard.php`

`index.php` is intentionally minimal and only wires the page together.

## Folder Structure

- `app/bootstrap.php`
  - Runtime and error handling bootstrap.
  - Cache initialization (`CacheManager`).
- `app/config.php`
  - Central app constants and shared config:
  - API URL, stopwords, party labels/colors, time-range definitions.
- `app/parliament_api.php`
  - Shared API client functions for Parliament data calls.
- `app/party.php`
  - Party mapping and normalization logic.
- `app/inquiry_helpers.php`
  - Shared row parsing, link building, answer extraction, keyword extraction.
- `app/time_range.php`
  - Time range resolution (`range`, label, cutoff, GP codes).
- `app/dashboard_service.php`
  - Main dashboard data processing:
  - caching, aggregation, chart datasets, pagination, and view model creation.
- `views/dashboard.php`
  - Full dashboard HTML/JS template.
  - Contains only presentation concerns.

## Scripts Using Shared Modules

- `send-daily-emails.php`
  - Uses shared API and parsing helpers from `app/`.
- `test-api.php`
  - Uses shared API helper instead of custom CURL code.

## Redesign Guidance

- Change layout/content: `views/dashboard.php`
- Change styles: `styles.css`
- Change data logic: `app/dashboard_service.php`
- Change source/query behavior: `app/parliament_api.php`, `app/time_range.php`, `app/config.php`

## Notes

- Cache behavior and TTL remain the same (15 minutes).
- Dashboard cache keys are versioned (`inquiry_data_v3_...`) so structure changes do not conflict with old cache entries.

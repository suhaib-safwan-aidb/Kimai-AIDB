# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [1.1.5] - 2026-04-15

### Release maintenance

- Version bump for release 1.1.5
- Synchronized release metadata in README and store listing files

## [1.1.4] - 2026-04-14

### Fixed

- WorklogSyncService: persist `jira_worklog_id` meta field via EntityManager to prevent duplicates and missing deletions
- TimesheetSubscriber: fixed wrong delete event handler and DEFERRED_EXPLICIT meta flush bug causing timer-stop sync failures

## [1.1.3] - 2026-04-14

### Changed

- Version bump for release 1.1.3

## [1.1.2] - 2026-04-13

### Changed

- Removed unused code and directory

## [1.1.1] - 2026-04-13

### Changed

- Documentation language consistency updates

## [1.1.0] - 2026-04-12

### Added

- License activation and verification against the remote license server at https://licenses.rebma.cz/
- New entity `LicenseActivation` for persisting license key, status and expiry in `kimai2_jira_sync_license` table
- `LicenseClient` / `LicenseClientInterface` – HTTP client that calls `/api/licenses/activate` and `/api/licenses/verify` endpoints
- `LicenseService` – business logic for activation, re-verification and status query
- `LicenseController` with routes `/jira-sync/license` (status page), `/jira-sync/license/activate` and `/jira-sync/license/verify`
- `LicenseActivationType` form for entering a license key in the admin UI
- Twig template `Resources/views/license/index.html.twig` for the license management page
- Install command (`kimai:bundle:kimaijirasync:install`) now also creates `kimai2_jira_sync_license` table
- Doctrine migration `Version20260412000000` for the license table
- "License" menu item in the navigation for super admins
- English and Czech translations for all license-related strings
- Unit tests for `LicenseService` and `LicenseResponse`
- **`SchemaSubscriber`** – automatically creates the plugin's database tables on the first web request after installation; no console commands required

## [1.0.0] - 2026-03-11

### Added

- Initial implementation of KimaiJiraSyncBundle
- Automatic synchronization of Kimai timesheets to Jira worklogs (event-driven via Kimai events)
- Support for activity meta field `jira_issue_key` and description prefix `PROJECT-123: ...` for issue key resolution
- Per-user × project Jira credentials management with admin UI
- AES-256-CBC encryption of Jira API tokens stored in the database
- Project meta fields: `jira_instance_url`, `time_multiplier`, `sync_tasks_enabled`, `task_sync_schedule`
- Hidden timesheet meta fields: `jira_worklog_id`, `sync_status`, `synced_at`
- Jira tasks import as Kimai activities (`TaskImportService`, `SyncTasksCommand`)
- Install command: `kimai:bundle:kimaijirasync:install`
- Navigation menu entry "Jira Sync → Credentials" for admins
- English and Czech translations
- Docker Compose development environment (Kimai + MariaDB)
- Unit tests for `EncryptionService`, `JiraCredentialService`, `WorklogSyncService`, `JiraClient`

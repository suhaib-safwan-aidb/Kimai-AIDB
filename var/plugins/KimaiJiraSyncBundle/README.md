# KimaiJiraSync

**Version: 1.1.5** | Released 2026-04-15 | [Changelog](./CHANGELOG.md)

Plugin for [Kimai2](https://www.kimai.org/) enabling automatic synchronization of time records into [Atlassian Jira](https://www.atlassian.com/software/jira).

## Features

- **Automatic worklog synchronization:** every time a timesheet entry is created, updated, or deleted in Kimai, the corresponding Jira worklog is created / updated / deleted
- **Jira task import:** the `kimai:bundle:kimaijirasync:sync-tasks` command downloads Jira tasks from a project and creates Kimai activities from them
- **Activity-to-Jira-issue mapping:** via activity meta field (`jira_issue_key`) or description prefix (`PROJECT-123: description`)
- **Per-user × project configuration:** each user has their own Jira credentials for each project
- **Time coefficient:** optional multiplier applied to record duration before sending to Jira (`time_multiplier`)
- **Encrypted token storage:** Jira API tokens are encrypted using AES-256-CBC
- **License activation and verification:** the plugin verifies its license against the license server at <https://licenses.rebma.cz/>

## Requirements

- PHP 8.2+
- Kimai2 ≥ 2.0
- Access to Atlassian Jira REST API (Cloud or Server)
- Composer

## Installation

The plugin is installed as a Kimai bundle in the `var/plugins/` directory:

```bash
# Clone the plugin
cd /opt/kimai/var/plugins
git clone <repository-url> KimaiJiraSyncBundle

# Create the database table for Jira credentials
cd /opt/kimai
bin/console kimai:bundle:kimaijirasync:install

# Clear cache
bin/console cache:clear
bin/console kimai:reload
```

## Configuration

All configuration is performed through the **Kimai administrative interface** (no configuration files required).

### License Activation

After installing the plugin, a license must be activated. The license management page is available for super admins in the **License** menu:

|Field|Description|
|---|---|
|`License Key`|License key assigned to your plugin (e.g. `XXXX-XXXX-XXXX-XXXX`)|

The plugin verifies the license against the license server at <https://licenses.rebma.cz/>. During activation it sends the instance name (typically the domain/hostname), then stores the manager-provided instance ID (UUID) and uses that ID for follow-up actions (verification, deactivation, ...).

Press **Verify Now** to manually verify the license validity at any time.

### Project Meta Fields

Each Kimai project has the following synchronization fields (configured in the project detail):

|Meta Field|Description|
|---|---|
|`jira_instance_url`|URL of the Jira instance (e.g. `https://company.atlassian.net`)|
|`jira_project_key`|Jira project key (e.g. `PROJ`, `DEV`) – required for task import|
|`time_multiplier`|Record duration multiplier when sending to Jira (default: `1.0`)|
|`sync_tasks_enabled`|Import Jira tasks as Kimai activities|

### Jira Credentials (per user × project)

Credentials management is available to logged-in administrators in the **Jira Sync → Credentials** menu:

|Parameter|Description|
|---|---|
|`Jira Username`|Email or Jira account login|
|`Jira API Token`|API token (generated at [id.atlassian.com](https://id.atlassian.com/manage-profile/security/api-tokens))|

### Activity ↔ Jira Issue Mapping

Priority when resolving the Jira issue key for worklog synchronization:

1. **Activity meta field** `jira_issue_key` (e.g. `PROJECT-123`) – set automatically on import or manually in the Kimai activity detail
2. **Record description prefix** – format `PROJECT-123: work description` (the key is automatically stripped from the submitted comment)

If the issue key cannot be determined, synchronization is skipped (the record remains without a `jira_worklog_id`).

### Import Jira Tasks – Field Mapping

When importing with the `sync-tasks` command, Kimai activity fields are populated as follows:

|Kimai activity field|Value from Jira|
|---|---|
|`name`|Summary (task description in Jira)|
|`comment`|Issue key (e.g. `PROJ-123`)|
|meta `jira_issue_key`|Issue key (used for matching on subsequent imports)|

Matching existing activities on subsequent imports always uses the meta field `jira_issue_key` — the activity name can be freely renamed in Kimai without losing the link to the Jira issue.

### Import Jira Tasks

The command downloads all tasks from a Jira project and creates Kimai activities from them (one activity = one Jira issue):

```bash
bin/console kimai:bundle:kimaijirasync:sync-tasks [--user=<userId>] [--project=<projectId>]
```

|Option|Description|
|---|---|
|`--user` / `-u`|ID of the Kimai user whose Jira credentials will be used (default: `1`)|
|`--project` / `-p`|ID of a specific Kimai project (without this parameter, all projects are synchronized)|

For synchronization to work, the project must have `jira_instance_url`, `jira_project_key` meta fields filled in and `sync_tasks_enabled` checked. The resulting activities have the Jira issue key stored in the `jira_issue_key` meta field.

## Agent Skills

The `.agents/skills/` directory contains [Agent Skills](https://agentskills.io/specification) – instructions for AI agents.

|Skill|Description|
|---|---|
|[`.agents/skills/jira`](./.agents/skills/jira/SKILL.md)|Communication with Atlassian Jira REST API v3 from PHP 8.2+ (authentication, CRUD issues, worklogs, JQL)|
|[`.agents/skills/kimai-plugin`](./.agents/skills/kimai-plugin/SKILL.md)|Kimai2 plugin development – bundle structure, event subscribers, meta fields, controllers, database|

## Development

### Local Environment with Docker (recommended)

```bash
# Start Kimai + MariaDB
docker compose up -d

# Install plugin (creates kimai2_jira_credentials table)
docker compose exec kimai bin/console kimai:bundle:kimaijirasync:install

# Clear cache after changes
docker compose exec kimai bin/console cache:clear
docker compose exec kimai bin/console kimai:reload
```

> Kimai is available at **<http://localhost:8080>**

### Tests

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpunit --coverage-text

# Docker: unit tests on the minimum supported PHP version
docker compose -f docker-compose.test.yml run --rm unit-tests

# Docker: integration tests on the minimum supported PHP version
docker compose -f docker-compose.test.yml run --rm integration-tests
```

See [AGENTS.md](./AGENTS.md) for details.

## AI Agent Instructions

- [**AGENTS.md**](./AGENTS.md) – Key instructions and commands for all AI agents
- [**instructions**](./.agents/instructions.md) – Code generation instructions

## Git Flow

This repository uses git-flow with the following branches:

- **main**: Production version
- **develop**: Development version

## Changelog

Changes are recorded in [CHANGELOG.md](./CHANGELOG.md) using the [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format.

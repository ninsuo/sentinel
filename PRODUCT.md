# Sentinel

## Local AI-Assisted Development Tool (Symfony / PHP / MySQL)

### Goal

Build a **local web application** that assists developers in working on software projects by using AI to **read, modify, and generate code directly inside a project directory**, while keeping full control, traceability, and safety.

The tool is designed for experienced developers and integrates naturally into existing workflows.

---

## High-Level Architecture

* **Web application** running locally
* **Symfony** backend
* **MySQL** database
* **File system access** to local projects
* **Pluggable AI providers** (OpenAI, local models, etc.)
* AI interactions are **project-scoped**, **feature-scoped**, and **auditable**

---

## Global Features Overview

* Global AI configuration
* Project management (CRUD)
* Project-scoped AI instructions
* Feature-based AI interactions
* File-aware context selection
* Diff-based file modifications
* Optional test/command execution
* Full audit trail and rollback capability

---

## Global Configuration

### Global Configuration Page

Allows configuring system-wide AI behavior.

**Settings include:**

* AI provider selection
* Provider credentials / connection settings
* Global system prompt (baseline rules and behavior)
* Safety defaults (max files per request, max file size, forbidden paths)
* Default model and options

---

## Home Page

### Layout

* **Jumbotron**: “Create a Project”
* **Projects table** below

### Projects Table

* Project name
* Project path
* Last updated
* Actions: Open / Edit / Delete

---

## Project

### Project Creation

When creating a project, prompt for:

* Project name
* Local filesystem path
* Save

After creation, redirect to **Project Configuration**.

---

### Project Layout

* **Breadcrumb**: `Home > Project Name`
* **Left sidebar**:

    * Configuration
    * Features (list)
* **Main content area**:

    * Configuration editor
    * Project home
    * Feature views

---

### Project Configuration

Defines **project-specific AI context**.

**Fields:**

* Project name
* Project path (read-only or editable with care)
* **Project system prompt instructions** (textarea)

**Purpose of project instructions:**

* Describe architecture conventions
* Coding standards
* Expected structure (entities, managers, repositories, controllers, transformers, etc.)
* “How features should be implemented in this project”

This prompt is **always injected** when interacting with AI for this project.

After saving:

* Redirect to Project Home.

---

## Project Home

### Layout

* Jumbotron: “Create Feature”
* Table of existing features

### Features Table

* Feature name
* Last run
* Status
* Actions: Open / Run / Delete

---

## Feature

A **Feature** represents a goal or task, implemented through one or more AI runs.

### Feature Creation

Triggered via modal.

**Fields:**

* Feature name
* User prompt (textarea)
* Files to attach (optional)

---

### File Selection

File chooser is based on the project path:

* Tree view
* Search
* File preview
* Ability to pin frequently used files

**Safety rules:**

* Enforced allowlist / denylist (e.g. forbid `.env`, secrets, keys)
* Max file size
* Max number of files

---

## Feature Execution Model

### Feature vs Feature Run

* **Feature**: long-lived goal
* **Feature Run**: one execution attempt

Each run stores:

* User prompt
* Selected files
* File hashes (before)
* AI request and response
* Generated patch
* Apply result
* File hashes (after)
* Timestamp

---

## AI Interaction Rules

Each AI request includes:

* Global system prompt
* Project system prompt
* Feature user prompt
* Selected file contents (or excerpts)
* Optional previous run context

---

## Output Format (Critical)

AI **must not** output raw full files.

Instead, AI outputs **file operations**, preferably as:

* Unified diffs (`--- / +++ / @@`)
* Or structured operations:

    * CREATE file
    * UPDATE file (diff)
    * DELETE file

This enables:

* Review
* Validation
* Transactional application
* Rollback

---

## Preview & Apply Workflow

1. AI generates a **patch**
2. User sees a **diff preview**
3. User can:

    * Apply all
    * Apply selected files
    * Reject changes
4. Changes are applied atomically

If file hashes changed since selection:

* Abort
* Ask user to refresh context

---

## Optional: Command Runner Integration

The system may run safe, whitelisted commands:

* PHPUnit
* PHPStan
* PHP-CS-Fixer
* Symfony console commands

Command output can be:

* Displayed to the user
* Injected into the next AI run for iteration

---

## Safety & Guardrails

### File Access

* Allowlist / denylist paths
* No binary files
* No secrets
* Encoding normalization (UTF-8)

### AI Safety

* Strict prompt instructions
* No execution without review
* No direct shell access
* No uncontrolled file writes

### Secrets Protection

* Automatic scanning for secrets before sending context
* `.env*`, keys, credentials never sent

---

## Audit & Traceability

For every Feature Run, persist:

* Selected files and hashes
* AI prompts and responses
* Patch applied
* Before/after hashes
* Provider + model
* Duration
* User

This enables:

* Debugging
* Rollback
* Accountability

---

## Non-Goals (Explicit)

* No autonomous background code changes
* No silent overwrites
* No “magic” execution without review
* No cloud-only dependency (local models supported)

---

## Summary

This tool is:

* Local
* Deterministic
* Reviewable
* Diff-based
* Project-aware
* Built for experienced developers

It aims to **accelerate development without surrendering control**, turning AI into a powerful, disciplined collaborator rather than a chaotic one.


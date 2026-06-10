# AGENT_GUIDE.md

## Purpose

This document defines the mandatory workflow and engineering standards for all AI agents working on this codebase.

The primary objective is:

* Maintain consistency with the existing architecture.
* Reuse existing implementations whenever possible.
* Avoid unnecessary abstractions.
* Minimize technical debt.
* Follow existing project patterns before introducing new ones.

---

# Golden Rules

## Rule #1 — Understand Before Building

Never start implementation immediately.

Before creating any feature:

1. Read PROJECT_CONTEXT.md
2. Analyze related modules
3. Analyze similar existing features
4. Understand current architecture
5. Create implementation plan

No code should be written before understanding the existing pattern.

---

## Rule #2 — Reuse Before Creating

Before creating:

* New component
* New composable
* New service
* New store
* New middleware
* New helper

Agent MUST search the codebase for an existing implementation.

If similar functionality already exists:

USE IT.

Do not create duplicates.

---

## Rule #3 — Follow Existing Patterns

When implementing features:

DO NOT introduce:

* New architecture styles
* New folder structures
* New naming conventions
* New API patterns

Unless explicitly requested.

The project architecture always has higher priority than personal preference.

---

## Rule #4 — Smallest Change Principle

Modify only files necessary for the requested feature.

Avoid:

* Global refactors
* Large-scale file restructuring
* Renaming unrelated files
* Rewriting working code

---

# Mandatory Feature Development Workflow

Every task must follow this workflow.

---

## Step 1 — Requirement Analysis

Understand:

* Business goal
* User goal
* Expected output
* Existing related features

Document:

* What is being built
* Why it is needed
* Which users use it

---

## Step 2 — Similar Feature Discovery

Search the codebase for:

* Similar pages
* Similar API endpoints
* Similar controllers
* Similar forms
* Similar tables
* Similar CRUD modules

Create a reference list.

Example:

User Management Feature:

* UserIndex.vue
* UserForm.vue
* UserController.php

These become implementation references.

---

## Step 3 — Architecture Tracing

Trace the complete flow.

Frontend:

Route
→ Page
→ Components
→ Store
→ Service
→ API

Backend:

Route
→ Controller
→ Service
→ Repository
→ Model
→ Database

Understand each layer before modification.

---

## Step 4 — Implementation Plan

Before coding:

Provide:

### Files To Create

### Files To Modify

### Data Flow

### API Flow

### Risks

Implementation starts only after planning.

---

# Frontend Development Standards

---

## Mandatory Component Reuse Policy

Before creating any UI component:

Search:

/components

If a suitable component exists:

MUST USE EXISTING COMPONENT.

Creating duplicate UI components is prohibited.

---

## Base Components Are Mandatory

Always prioritize:

* BaseSelect
* BaseModal
* BaseTable

Never replace them with raw HTML unless necessary.

---

## Page Creation Workflow

Before creating a page:

1. Find similar page
2. Clone page structure
3. Adapt business logic
4. Reuse components
5. Reuse styling

Avoid inventing new page layouts.

Consistency > Creativity.

---

## Styling Rules

Follow existing:

* spacing
* typography
* colors
* card styles
* table styles
* modal styles

Never introduce a new design language.

---

## API Integration Rules

Always inspect:

/services
/api
/composables

before creating API calls.

Reuse:

* Existing API client
* Existing interceptors
* Existing auth flow

Do not create duplicate axios instances.

---

# Backend Development Standards

---

## Route Analysis First

Before creating route:

Check:

routes/web.php
routes/api.php

Follow existing grouping pattern.

---

## Controller Standards

Controllers should:

* Validate requests
* Call service layer
* Return response

Avoid business logic inside controllers.

---

## Service Standards

Business logic belongs in:

Services

Not in:

Controllers
Routes
Views

But in this existing codebase some business logic is still in controller always double check in whether service or controller

---

## Model Standards

Before creating model:

Search existing entities.

Avoid duplicate entities.

Use relationships whenever possible.

---

## Database Standards

Before creating migration:

Check:

* Existing tables
* Existing relationships
* Existing indexes

Avoid schema duplication.

---

## Validation Standards

Always use:

Form Request

instead of inline validation if project already uses requests.

---

# Debugging Workflow

Before fixing a bug:

1. Reproduce
2. Trace route
3. Trace controller
4. Trace service
5. Trace model
6. Identify root cause

Never apply blind fixes.

---

# Pull Request Checklist

Before completing task:

## Architecture

* Follows existing architecture
* No duplicated logic
* No unnecessary abstractions

## Frontend

* Uses base components
* Uses existing styling patterns
* Responsive
* No duplicated components

## Backend

* Uses existing services
* Uses existing validation patterns
* No business logic in controller

## Database

* No duplicate schema
* Proper relationships

## Code Quality

* No dead code
* No unused imports
* No console logs
* No debug statements

## Documentation

* Update PROJECT_CONTEXT.md if architecture changes

---

# Absolute Prohibitions

Never:

* Rewrite unrelated code
* Refactor entire modules without request
* Introduce new libraries without justification
* Duplicate components
* Duplicate services
* Duplicate API clients
* Duplicate business logic

Always prioritize consistency over innovation.

The best feature implementation is the one that feels like it was originally written by the existing development team.

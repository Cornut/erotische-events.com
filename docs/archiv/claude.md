# CLAUDE.md

## Project Overview

This project is an event discovery and booking platform.

Users can browse events, submit booking requests and communicate with event organizers.

Bookings are processed by the organizer outside of the platform.

The platform acts as:

* Event directory
* Lead generation platform
* Booking request platform
* Administration and moderation platform

The platform is NOT:

* A payment provider
* A ticketing system
* A hotel booking system

---

# Core Business Rules

## User Roles

### Guest

Can:

* Browse events
* Search events
* View organizers
* Submit booking requests

Cannot:

* Create events
* Moderate content

---

### Organizer

Can:

* Create events
* Edit own events
* Receive booking requests
* Manage event content
* Upload event images

Cannot:

* Approve own moderation requests
* Access administration functions

---

### Admin

Can:

* Review submitted events
* Approve events
* Reject events
* Manage users
* Manage organizers
* Manage sponsored placements
* Manage system settings

---

## Event Lifecycle

Event status:

```text
draft
pending_review
published
rejected
archived
```

Rules:

* New events start as draft.
* Submission moves event to pending_review.
* Admin approval changes status to published.
* Admin rejection changes status to rejected.
* Only published events are publicly visible.

---

## Booking Workflow

### Important

The platform does NOT process payments.

The organizer handles:

* Reservations
* Contracts
* Invoicing
* Payments

The platform only manages booking requests.

Workflow:

```text
Visitor
   ↓
Booking Request
   ↓
Organizer receives request
   ↓
Organizer processes request externally
```

---

## Organizer Verification

Organizers can register freely.

After registration:

* Organizer account requires approval.
* Admin receives notification.
* Admin can approve or reject organizer.

Only approved organizers may publish events.

---

## Sponsored Listings

Sponsored events are displayed separately.

Rules:

* Sponsored placement never changes event data.
* Sponsored placement only affects visibility.
* Sponsored events must remain clearly identifiable.

---

# Domain Model

## Event

Required fields:

* title
* slug
* description
* organizer
* start_date
* end_date
* location
* category
* status

Optional:

* images
* gallery
* website
* social_links
* sponsorship

---

## Organizer

Fields:

* company_name
* contact_name
* email
* phone
* website
* verification_status

Status:

```text
pending
approved
rejected
```

---

## BookingRequest

Fields:

* event_id
* participant_name
* email
* phone
* message
* participant_count
* status

Status:

```text
new
contacted
closed
```

---

# API Standards

All APIs must follow OpenAPI 3.1.

Every endpoint must:

* be documented
* define request schema
* define response schema
* define error responses

Response structure:

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

Error structure:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {}
}
```

---

# Laravel Architecture

## Controllers

Controllers must remain thin.

Allowed:

* Request validation
* Authorization
* Service invocation

Forbidden:

* Business logic
* Database queries
* Complex transformations

---

## Services

Business logic belongs in Services.

Examples:

* EventPublishingService
* BookingRequestService
* OrganizerApprovalService

---

## Models

Models should contain:

* Relationships
* Scopes
* Accessors
* Mutators

Models should not contain complex business workflows.

---

# Frontend Standards

## Vue

Use:

* Composition API
* TypeScript
* Pinia

Avoid:

* Options API
* Global state outside Pinia

---

## Components

Components should:

* Have a single responsibility
* Be reusable
* Remain under 300 lines whenever possible

---

# Filament Administration

Filament is reserved for:

* Admin users
* Moderation
* User management
* Organizer management
* Sponsored content management

Public user functionality must never depend on Filament.

---

# Security Rules

Always:

* Validate all input
* Sanitize rich text content
* Protect uploads
* Use policies and permissions
* Apply rate limiting

Never:

* Trust client-side validation
* Expose internal system information
* Allow direct file execution

---

# Database Rules

Always:

* Use foreign keys
* Use indexes
* Use soft deletes where appropriate

Never:

* Use manual schema changes
* Skip migrations

---

# Testing Requirements

Every feature requires:

* Feature Tests
* Validation Tests

Critical workflows require:

* Integration Tests

Bug fixes require:

* Regression Tests

---

# Documentation Requirements

Whenever functionality changes:

Update:

* OpenAPI specification
* Engineering specification
* Database documentation

Documentation is part of the implementation.

---

# Claude Operating Procedure

For every task:

1. Analyze current implementation.
2. Explain implementation plan.
3. Identify affected files.
4. Implement changes.
5. Run tests.
6. Report results.
7. Report risks.

Before generating code:

* Search existing codebase.
* Reuse existing patterns.
* Avoid duplication.

When uncertain:

STOP and ask for clarification.

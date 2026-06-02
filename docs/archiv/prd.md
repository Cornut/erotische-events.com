# PRD – Conscious Events Platform

Version: 1.0

---

# 1. Product Vision

The Conscious Events Platform is a global event discovery platform focused on:

* Tantra
* Conscious Relating
* Sacred Sexuality
* Sex Positive Communities
* Retreats
* Festivals
* Workshops
* Bodywork
* Men's Work
* Women's Work
* LGBTQ+
* Shibari
* Kink

The platform aggregates event information from organizers worldwide and provides users with a centralized search, discovery, and recommendation experience.

Long-term goal:

Become the largest global database and search engine for conscious, tantra, and sex-positive events.

The platform will be available through:

* Responsive Web Application
* Progressive Web App (PWA)
* Native Mobile App (Phase 2)
* Public REST API

---

# 2. Business Goals

## Primary Goals

* Build the largest event database in this niche.
* Acquire organic traffic through SEO.
* Create recurring revenue through premium memberships.
* Create revenue opportunities for organizers through sponsored placements.

## Success Metrics

Year 1:

* 10,000 events indexed
* 1,000 organizers
* 25,000 registered users
* 5,000 newsletter subscribers

Year 2:

* 100,000 events indexed
* 10,000 organizers
* 250,000 users

---

# 3. User Types

## Guest User

Goals:

* Discover events
* Search by location
* Explore organizers

Can:

* Search events
* View event details
* View organizer profiles
* Read reviews

---

## Registered User

Additional capabilities:

* Save favorites
* Write reviews
* Subscribe to newsletters
* Receive recommendations
* Receive notifications

---

## Premium Member

Additional capabilities:

* Personalized recommendations
* Advanced search filters
* Event watchlists
* Location alerts
* Category alerts

---

## Organizer

Goals:

* Promote events
* Manage event portfolio
* Increase visibility

Can:

* Manage organizer profile
* Manage venues
* Create events
* Edit events
* Access statistics
* Purchase sponsored placements

---

## Administrator

Responsible for:

* User management
* Organizer moderation
* Event moderation
* Category management
* Scraper management
* System configuration

---

# 4. Core Product Features

## Event Discovery

Users can:

* Search events
* Filter events
* Browse categories
* Discover nearby events

Searchable fields:

* Event title
* Description
* Organizer
* Teacher
* Venue
* Categories
* Tags

---

## Event Details

An event page includes:

* Title
* Description
* Images
* Organizer
* Venue
* Teachers
* Dates
* Pricing
* Languages
* Categories
* Audience
* Booking link
* Reviews

---

## Organizer Profiles

Each organizer has:

* Public profile
* Contact information
* Website
* Event listings
* Reviews
* Statistics

---

## Venue Management

Organizers can manage multiple venues.

A venue contains:

* Name
* Description
* Address
* Coordinates
* Images
* Contact information

---

## Reviews

Registered users can:

* Rate events
* Write reviews

Reviews require moderation.

---

## Tracking & Analytics

All outgoing booking links are tracked.

Tracked data:

* Event
* Organizer
* Timestamp
* Country
* Device information

Organizers can access analytics dashboards.

---

# 5. Event Data Model

Each event contains:

## Core Data

* Title
* Slug
* Short Description
* Long Description
* Main Image

## Scheduling

* Start Date
* End Date

## Relations

* Organizer
* Venue
* Teachers

## Audience

* Singles
* Couples
* Men
* Women
* LGBTQ+
* Everyone

## Capacity

* Minimum Participants
* Maximum Participants

## Languages

* German
* English
* Spanish
* French
* Other

## Accommodation

* None
* Optional
* Mandatory
* External

## Pricing

* Early Bird
* Regular Price
* Late Bird
* Currency

## Metadata

* Categories
* Tags
* Tracking ID
* Source URL
* Import Date
* Last Updated

---

# 6. Search & Discovery

## Filters

Users can filter by:

* Country
* Region
* City
* Date
* Category
* Organizer
* Language
* Audience
* Price

## Geosearch

Search radius:

* 10 km
* 25 km
* 50 km
* 100 km
* 250 km
* Worldwide

Location sources:

* Browser Geolocation
* IP Detection
* Manual Location Entry

---

# 7. Artificial Intelligence Features

After importing an event:

* Automatic categorization
* Tag generation
* Language detection
* Translation
* Summary generation

---

# 8. Multilingual Support

Phase 1:

* German
* English

Requirements:

* Fully localized user interface
* Automatic event translation

Possible providers:

* OpenAI
* DeepL

---

# 9. Scraper Platform

The scraping platform is a separate system.

Responsibilities:

* Crawl organizer websites
* Extract event information
* Normalize data
* Import via API

Rule:

Organizer-managed content always overrides scraper updates.

---

# 10. Public API

Version:

/api/v1/

Resources:

* Events
* Organizers
* Venues
* Categories
* Reviews
* Users

Authentication:

Laravel Sanctum

---

# 11. MVP Scope

Version 1 includes:

* Event search
* Event details
* Categories
* Geosearch
* Organizer profiles
* Venue management
* Event management
* Reviews
* Tracking
* Newsletter basics
* Scraper imports
* AI categorization
* German/English support
* REST API
* PWA support

Excluded from MVP:

* Native mobile apps
* Advanced recommendation engine
* Marketplace functionality
* Internal messaging
* Payment processing

---

# 12. Non-Goals

The platform will not:

* Sell tickets
* Process payments
* Manage bookings internally
* Replace organizer websites
* Provide CRM functionality

The platform acts as a discovery and lead-generation platform.

---

# 13. Technical Stack

Backend

* Laravel 12
* PHP 8.4

Frontend

* Vue 3
* TypeScript
* Pinia
* TailwindCSS

Search

* Laravel Scout
* Meilisearch

Infrastructure

* Docker
* MySQL
* Redis
* S3-compatible Storage

Deployment

* GitHub Actions
* Amazon Lightsail

Future options:

* AWS ECS
* Kubernetes
* App Runner

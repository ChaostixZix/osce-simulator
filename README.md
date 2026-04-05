# osce-simulator

## Overview
Laravel 12 OSCE simulator for clinical case practice, AI-assisted assessment, and post-session learning workflows.

## Problem
Medical skills practice needs a single application for running simulated OSCE cases, tracking sessions, and reviewing structured feedback.

## Solution
This project combines a Laravel backend with an Inertia/React frontend for OSCE chat sessions, assessment results, onboarding, patient visualization, replay, microskills coaching, growth tracking, and rationalization flows.

## Demo
No public demo URL is documented in this repository.

## Setup
```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
composer run dev
```

Set required environment values before running features that depend on external services, including WorkOS authentication, Redis-backed session/queue services, and the configured AI provider credentials.


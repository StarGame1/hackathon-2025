# Budget Tracker – Evozon PHP Internship Hackathon 2025

## Delivery details

Participant:

-   Full name: Paul Oprisor
-   Email address: pauloprisor2@gmail.com

Features fully implemented:

-   User registration with validation (username ≥ 4 chars, password ≥ 8 chars + 1 number)
-   Login/logout functionality with session management
-   Complete expense CRUD operations with user ownership checks
-   Monthly expense listing with pagination and year/month filtering
-   Dashboard showing total expenditure, per-category totals and averages
-   Overspending alerts for current month based on category budgets
-   CSV import for bulk expense data with duplicate detection
-   Password hashing using bcrypt
-   CSRF protection on all forms
-   Session security with regeneration
-   Database indexes for performance
-   Soft delete implementation
-   Flash messages for user feedback
-   Password confirmation field in registration
-   Numbered pagination links
-   Categories and budgets configurable via .env
-   Transaction wrapper for CSV imports
-   Unit tests for services
-   PSR-12 compliant code
-   Clean static analysis results

## Setup instructions:

composer install
cp .env.example .env
cd database && ./apply_migrations.sh && cd ..
composer start
Access the application at http://localhost:8000

## Testing

vendor/bin/phpunit
composer analyze

All tests pass and code analysis shows no issues.

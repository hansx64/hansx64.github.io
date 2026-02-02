# Syllabus Repository

A web-based system for managing and reviewing course syllabi, organized by department.

## Features
- Administrator Dashboard with department filtering and search
- Instructor Dashboard with department-wide syllabus view
- Automated department assignment for uploaded syllabi
- Secure login and registration for instructors

## Requirements
- PHP 7.4+
- MySQL/MariaDB
- XAMPP or similar local server environment

## Installation
1. Clone the repository to your `htdocs` folder.
2. Import `database/schema.sql` into your MySQL database.
3. Configure your database connection in `config/database.php`.
4. Run `add_department_column.php` to ensure the schema is up to date.

# Submission Period Management Feature

## Overview
The Submission Period Management feature allows administrators to create, manage, and control submission periods for the syllabus repository system.

## Files Created
1. **manage_period.php** - Main interface for managing submission periods
2. **add_submission_period_table.php** - Database migration script

## Installation Steps

### 1. Run the Database Migration
First, you need to add the SUBMISSION_PERIOD table to your database:

1. Open your browser and navigate to:
   ```
   http://localhost/AppdevRepository/add_submission_period_table.php
   ```

2. This will create the `SUBMISSION_PERIOD` table and add a sample period

3. You should see a success message

### 2. Access the Management Interface
1. Log in as an administrator
2. From the Admin Dashboard, click the **"Manage Submission Period"** button
3. You'll be taken to the submission period management page

## Features

### 1. View Active Period
- The current active submission period is displayed at the top
- Shows period name, start/end dates, and current status (Upcoming/Active/Ended)

### 2. Create New Periods
- Fill out the form with:
  - **Period Name**: e.g., "Fall 2026 Submission"
  - **Start Date**: When the submission period begins
  - **End Date**: When the submission period ends
  - **Description**: Optional details about the period
  - **Active Status**: Check to make this the active period

### 3. Manage Existing Periods
For each period, you can:
- **Activate/Deactivate**: Toggle the active status
- **Edit**: Modify period details
- **Delete**: Remove the period (with confirmation)

### 4. Period Status Indicators
- **Active** (green badge): Currently active period
- **Inactive** (gray badge): Not currently active

## Database Schema

The SUBMISSION_PERIOD table includes:
- `periodID` - Primary key
- `period_name` - Name of the submission period
- `start_date` - Start date
- `end_date` - End date
- `description` - Optional description
- `is_active` - Boolean flag for active status
- `created_at` - Timestamp of creation
- `updated_at` - Timestamp of last update

## Usage Tips

1. **Only one active period**: While you can have multiple periods, typically only one should be active at a time
2. **Plan ahead**: Create upcoming periods in advance and activate them when ready
3. **Clear naming**: Use descriptive names like "Fall 2026 Submission" or "Spring 2027 Syllabus Period"

## Future Enhancements (Optional)

You could extend this feature to:
- Automatically prevent submissions outside active periods
- Send notifications when periods start/end
- Generate reports based on submission periods
- Add recurring period templates

## Troubleshooting

**Issue**: Can't access manage_period.php
- **Solution**: Make sure you're logged in as an administrator

**Issue**: Database error when creating periods
- **Solution**: Ensure you've run the migration script first

**Issue**: Changes not saving
- **Solution**: Check that your database connection is working properly

## Support

If you encounter any issues, check:
1. Database connection in `config/database.php`
2. Session is active and user has admin role
3. All required fields are filled in forms

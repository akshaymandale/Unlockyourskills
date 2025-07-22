# Course Tables Migration

This directory contains the database migration for the Course Creation System.

## Files

- `create_course_tables.sql` - The main migration SQL file
- `migrate_course_tables.php` - PHP migration runner script
- `migration_log.txt` - Migration execution log (created automatically)

## Running the Migration

### Option 1: Command Line (Recommended)

Navigate to the database directory and run:

```bash
cd database
php migrate_course_tables.php run
```

### Option 2: Web Interface

Access the migration script through your web browser:

```
http://your-domain/database/migrate_course_tables.php
```

This provides a web interface with buttons to run, check status, or rollback the migration.

### Option 3: Direct SQL Execution

You can also run the SQL file directly in your database management tool (phpMyAdmin, MySQL Workbench, etc.):

```sql
-- Execute the contents of create_course_tables.sql
```

## Migration Commands

### Run Migration
```bash
php migrate_course_tables.php run
```

### Check Migration Status
```bash
php migrate_course_tables.php status
```

### Rollback Migration (Drops all tables)
```bash
php migrate_course_tables.php rollback
```

## What the Migration Creates

### Core Tables (17 tables total)

1. **course_categories** - Course categories (Technology, Business, etc.)
2. **course_subcategories** - Subcategories within each category
3. **courses** - Main courses table with all course information
4. **course_modules** - Course modules/sections
5. **course_module_content** - VLR content within modules
6. **course_prerequisites** - Course prerequisites
7. **course_assessments** - Course assessments
8. **course_feedback** - Course feedback forms
9. **course_surveys** - Course surveys
10. **course_enrollments** - Student enrollments
11. **module_progress** - Module completion tracking
12. **content_progress** - Content completion tracking
13. **assessment_results** - Assessment scores and results
14. **feedback_responses** - Feedback form responses
15. **survey_responses** - Survey responses
16. **course_analytics** - Aggregated analytics data
17. **course_settings** - Course configuration settings

### Views

- **course_overview** - Comprehensive course information with statistics
- **course_analytics_view** - Course analytics and performance metrics

### Default Data

- Default course categories (Technology, Business, Marketing, etc.)
- Default subcategories for each category
- Default course settings

## Prerequisites

1. **Database Connection**: Ensure your database connection is properly configured in `config/Database.php`
2. **Permissions**: The database user must have CREATE, ALTER, and DROP permissions
3. **PHP PDO**: PDO extension must be enabled
4. **File Permissions**: The script needs read access to the SQL file and write access for logs

## Troubleshooting

### Common Issues

1. **Permission Denied**
   ```
   Error: Access denied for user 'username'@'localhost'
   ```
   - Check database credentials in `config/Database.php`
   - Ensure user has proper permissions

2. **File Not Found**
   ```
   Error: Migration file not found
   ```
   - Ensure `create_course_tables.sql` exists in the same directory
   - Check file permissions

3. **Table Already Exists**
   ```
   Error: Table 'courses' already exists
   ```
   - Use `status` command to check current state
   - Use `rollback` command to drop existing tables first

4. **Foreign Key Constraints**
   ```
   Error: Cannot add foreign key constraint
   ```
   - Ensure all referenced tables exist
   - Check table creation order in SQL file

### Checking Migration Status

```bash
php migrate_course_tables.php status
```

This will show which tables exist and which are missing.

### Rolling Back

If you need to start over:

```bash
php migrate_course_tables.php rollback
```

**Warning**: This will drop ALL course-related tables and delete all data!

## Migration Log

The migration script creates a detailed log file (`migration_log.txt`) with:

- Timestamp of each operation
- SQL statements executed
- Success/failure status
- Error messages
- Table verification results

## After Migration

Once the migration is complete:

1. **Verify Tables**: Check that all 17 tables were created
2. **Test Connection**: Ensure your application can connect to the new tables
3. **Test Course Creation**: Try creating a test course through the web interface
4. **Check Default Data**: Verify categories and subcategories were inserted

## Backup Recommendation

Before running the migration on a production database:

```bash
mysqldump -u username -p database_name > backup_before_course_migration.sql
```

## Support

If you encounter issues:

1. Check the migration log file for detailed error messages
2. Verify database connection and permissions
3. Ensure all prerequisites are met
4. Check the main application logs for additional errors

---

**Note**: This migration is designed to be idempotent - you can run it multiple times safely. Tables will only be created if they don't already exist. 
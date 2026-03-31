# Workflow Management System - Phase 1
Version 1.0.0

## Overview
Complete workflow and task management system for Media Vines Corp built on WordPress.

**Phase 1 Features:**
- Custom Post Types (Clients, Workflows, Tasks)
- User Roles (Team Member, Supervisor, Admin)
- Basic Task Management
- Frontend Dashboard
- Status Updates
- Activity Logging

## Requirements
- WordPress 5.0 or higher (Latest version recommended)
- PHP 7.4 or higher (PHP 8.0+ recommended)
- ACF Pro plugin (required)
- MySQL 5.6 or higher

## Installation Instructions

### Step 1: Prerequisites
1. Install WordPress on your domain
2. Install and activate ACF Pro plugin
3. Make sure you can access WordPress admin

### Step 2: Upload Plugin
1. Download the `workflow-system` folder
2. Upload via FTP to `/wp-content/plugins/workflow-system/`
   
   OR
   
   Zip the folder and upload via WordPress admin:
   - Go to Plugins → Add New → Upload Plugin
   - Choose the zip file
   - Click Install Now

### Step 3: Activate Plugin
1. Go to WordPress admin → Plugins
2. Find "Workflow Management System"
3. Click "Activate"

### Step 4: Verify Installation
After activation, the plugin will automatically:
- Create custom post types (Clients, Workflows, Tasks)
- Create user roles (Team Member, Supervisor, Workflow Admin)
- Register ACF fields
- Create a "Workflow Dashboard" page
- Flush rewrite rules

### Step 5: Check Menu Items
In WordPress admin, you should now see:
- Clients (menu item)
- Workflows (menu item)
- Tasks (menu item)

## User Roles

### Team Member
- View assigned tasks
- Update task status
- View workflows they're involved in
- Add notes to their tasks

**Capabilities:**
- `read`
- `read_wfs_task`
- `edit_wfs_task` (own tasks only)
- `access_wfs_dashboard`

### Supervisor
- All Team Member permissions
- Create and manage workflows
- Assign tasks to team members
- View all tasks across all users
- Approve tasks
- Close workflows

**Additional Capabilities:**
- `edit_others_wfs_tasks`
- `publish_wfs_tasks`
- `delete_wfs_tasks`
- Full workflow and client management
- `access_wfs_supervisor_dashboard`
- `approve_wfs_tasks`
- `close_wfs_workflows`

### Workflow Admin
- All Supervisor permissions
- Manage workflow templates
- Manage system settings
- View reports
- Export data

**Additional Capabilities:**
- `manage_wfs_settings`
- `manage_wfs_templates`
- `view_wfs_reports`

## Creating Your First Workflow

### 1. Create a Client
1. Go to WordPress admin → Clients → Add New
2. Enter client name
3. Add contact information (optional)
4. Add notes (optional)
5. Make sure "Active Client" is checked
6. Click Publish

### 2. Create a Workflow
1. Go to Workflows → Add New
2. Enter workflow title (e.g., "Website Launch - ABC Corp")
3. In the "Workflow Details" box:
   - Select the Client
   - Status will default to "Active"
   - Start Date will auto-populate
4. Click Publish

### 3. Create Tasks
1. Go to Tasks → Add New
2. Enter task title (e.g., "Design homepage mockup")
3. Add task description
4. In the "Task Details" box:
   - Select the Workflow
   - Assign to a user
   - Set status (defaults to "Assigned")
   - Set due date
   - Start date will auto-populate
5. Click Publish

Repeat step 3 for all tasks in your workflow.

## Using the Dashboard

### For Team Members

**Accessing Dashboard:**
1. Log into WordPress
2. Navigate to: yourdomain.com/workflow-dashboard
   OR
3. Click on the "Workflow Dashboard" page

**Dashboard Features:**
- View all assigned tasks
- Tasks are grouped by:
  - Overdue (highlighted in red)
  - Due Today (highlighted in yellow)
  - Upcoming (highlighted in green)
- Each task shows:
  - Client name
  - Workflow name
  - Due date
  - Current status
- Update task status using dropdown
- Click "View Details" to see full task in admin

**Updating Task Status:**
1. Find your task in the dashboard
2. Use the status dropdown to change status
3. Confirm the change
4. Status will update and be logged in activity log

## Task Statuses

### Available Statuses:
1. **Assigned** - Task given to user, not started yet
2. **In Progress** - User actively working on it
3. **Waiting** - Blocked by dependencies
4. **Needs Info** - User needs clarification
5. **Awaiting External** - Waiting on external people
6. **Needs Approval** - Waiting for supervisor review
7. **Completed** - Task is done

### Status Flow Example:
Assigned → In Progress → Needs Approval → Completed

## Activity Logging

Every task automatically logs:
- Creation (who and when)
- Status changes (old → new, who changed it)
- Assignment changes (who reassigned it)

View the activity log in the task edit screen (WordPress admin).

## File Structure

```
workflow-system/
├── workflow-system.php          # Main plugin file
├── includes/
│   ├── custom-post-types.php    # Clients, Workflows, Tasks
│   ├── user-roles.php           # Team Member, Supervisor, Admin
│   ├── acf-fields.php           # ACF field definitions
│   └── dashboard.php            # Frontend dashboard
├── assets/
│   ├── css/
│   │   └── dashboard.css        # Dashboard styling
│   └── js/
│       └── dashboard.js         # Dashboard AJAX functionality
└── README.md                    # This file
```

## Troubleshooting

### Dashboard page not found (404 error)
1. Go to Settings → Permalinks
2. Click "Save Changes" (no need to change anything)
3. This flushes rewrite rules and should fix the issue

### ACF fields not showing
1. Make sure ACF Pro is installed and activated
2. Deactivate and reactivate the Workflow System plugin
3. Check if you're editing the correct post type

### Can't see tasks in dashboard
1. Make sure you're logged in
2. Check if you have tasks assigned to you
3. Verify the task status is not "Completed"
4. Check that tasks are published (not draft)

### Status dropdown not updating
1. Check browser console for JavaScript errors
2. Make sure jQuery is loaded
3. Clear browser cache
4. Verify AJAX URL is correct

## What's Next: Phase 2

Phase 2 will add:
- Priority levels (High, Medium, Low)
- Resource links
- Work links
- Consolidated notes
- Enhanced activity logging

Stay tuned!

## Support

For questions or issues:
1. Check the main specification document: `WORKFLOW_SYSTEM_SPEC.md`
2. Review this README
3. Check WordPress and ACF documentation

## Version History

### 1.0.0 (Phase 1)
- Initial release
- Custom post types
- User roles
- Basic ACF fields
- Frontend dashboard
- Status updates
- Activity logging

## Credits

Developed for Media Vines Corp
Built on WordPress and ACF Pro

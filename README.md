This plugin enables administrators to manage church branches and their associated programs and services through a dynamic backend system.

## What's in this plugin?

#### Admin Interface

- **Complete Admin Dashboard** with statistics and quick actions
- **Create Branch Page** with WYSIWYG editors for rich text content
- **Edit Branch** functionality with ability to update all fields
- **All Branches Table** view with management actions
- **Services Management** per branch with add/delete functionality
- **Programs Management** per branch with add/delete functionality
- **Settings Page** to customize brand colors and typography

### Backend-Driven vs Elementor

- **Eliminated** Elementor dependency for content updates
- **Replaced** with dynamic database-driven content
- **Administrators** manage content through WordPress admin
- **End users** never need to touch Elementor

### Structured Data

- Programs and services use **dedicated database tables** (not post meta)
- Efficient querying and scaling
- Proper relationships with foreign keys
- Chronological ordering support

### Scalability

- Supports unlimited branches
- Supports unlimited programs per branch
- Supports unlimited services per branch
- Efficient database queries with proper indexing
- No hardcoding of content on pages

## Installation

1. **Upload the plugin** to your WordPress site at `/wp-content/plugins/church-branches-generator/`
2. **Activate the plugin** from WordPress Admin → Plugins
3. The database tables will be automatically created on activation

## Accessing the Admin Interface

Once activated, you'll see a new **"Church Branches"** menu in the WordPress Admin sidebar with the following sections:

### 1. **Dashboard**

- Overview with total branch count
- Quick action buttons for common tasks
- Direct links to create branches, manage programs, and settings

### 2. **Create Branch**

- Fill in branch information (name, address, contact details)
- Upload a hero image from your Media Library
- Add About Us content with the WYSIWYG editor
- Add Directions information
- A page is automatically generated at `/branch-name-branch/`

### 3. **All Branches**

- View all created branches in a table
- Edit, view, or delete branches
- Quick links to manage programs and services for each branch

### 4. **Services**

- Select a branch from the dropdown
- Add services (Sunday Service, Prayer Meeting, etc.)
- Specify day of week and time
- Delete services individually

### 5. **Programs**

- Select a branch from the dropdown
- Add programs (Bible Study, Youth Group, etc.)
- Choose program type (Weekly, Monthly, Special Event)
- Specify day, time, and location
- Delete programs individually

### 6. **Settings**

- Customize primary color (for buttons)
- Customize secondary color
- Choose font family
- Settings apply site-wide

## Frontend Display

### Branch Page Display

When you create a branch named "Lagos", a page is automatically created that displays:

- **Hero Section**: Branch name with uploaded background image
- **Contact Information**: Address, phone, email, service times, pastor name
- **About Us Section**: Rich text content
- **Services & Activities**: Listed services for the branch
- **Programs Section**: Programs grouped by type (Weekly, Monthly, etc.)
- **Call-to-Action Section**: "Visit Us This Sunday" with Get Directions button

### Shortcode (Advanced)

You can display a branch on any page using:

```
[church_branch id="1"]
```

Where `id` is the branch ID from the database.

## Creating and Managing Content

### Adding a New Branch

1. Go to **Church Branches → Create Branch**
2. Fill in all required fields
3. Upload a high-quality background image
4. Click **Create Branch**
5. A page is instantly created and you can view it

### Adding Programs to a Branch

1. Go to **Church Branches → Programs**
2. Select the branch from dropdown
3. Add program name, description, type (weekly/monthly/special)
4. Specify day and time
5. Click **Add Program**
6. The program instantly appears on the branch page

### Adding Services to a Branch

1. Go to **Church Branches → Services**
2. Select the branch from dropdown
3. Add service name, description
4. Specify day and time
5. Click **Add Service**
6. The service instantly appears on the branch page

### Editing a Branch

1. Go to **Church Branches → All Branches**
2. Click **Edit** next to the branch name
3. Update any fields
4. Click **Update Branch**

### Deleting Content

1. Click **Delete** next to the item you want to remove
2. Confirm the deletion
3. The item is instantly removed

**Note:** Deleting a branch will also delete all associated programs and services.

## Features Implemented

**Branch CRUD**

- Create new branches with hero images
- Edit all branch details
- Delete branches with cascade delete
- Automatic WordPress page generation

**Program Management**

- Create unlimited programs per branch
- Categorize programs (Weekly/Monthly/Special)
- Delete individual programs
- Display grouped by type on frontend

**Service Management**

- Add unlimited services per branch
- Specify day and time for each service
- Delete individual services
- Display on branch pages

**Dynamic Frontend**

- No Elementor editing required
- All content managed in admin panel
- Real-time updates reflected on frontend
- Responsive mobile design

**Get Directions Popup**

- Google Maps embed
- Smooth animations
- Travel tips and landmarks
- Keyboard accessible (Escape to close)
- Click outside to dismiss

**Settings**

- Customize brand colors
- Choose font family
- Settings apply globally

**Security**

- All inputs sanitized
- Nonce-protected forms
- AJAX security

## Troubleshooting

### Database table not created

- Go to **Church Branches** menu
- If you see an error about missing tables, deactivate and reactivate the plugin

### Branch page not showing correctly

- Make sure you've assigned a hero image
- Check that the branch address is correct and accurate (it's used directly in Google Maps)
- Verify programs and services are added

### Changes not appearing on frontend

- Clear any caching plugins you may have
- Refresh the page
- Changes are instant in the admin but may be cached

### Get Directions not showing

- Make sure you've entered a valid address in the branch settings
- Google Maps will use this address to center the map
- Add any extra information in the "Directions Info" field

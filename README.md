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

### Scalability

- Supports unlimited branches
- Supports unlimited programs per branch
- Supports unlimited services per branch
- Efficient database queries with proper indexing
- No need to hardcode any content on pages

## Installation

1. **Upload the plugin** to your WordPress site at `/wp-content/plugins/church-branches-generator/`
2. **Activate the plugin** from WordPress Admin → Plugins
3. The database tables will be automatically created on activation

## Using the Admin Interface

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

When you create a branch, a page is automatically created that displays:

- **Hero Section**: Branch name with uploaded background image
- **Contact Information**: Address, phone, email, service times, pastor name
- **About Us Section**: Rich text content
- **Services & Activities**: Listed services for the branch
- **Programs Section**: Programs grouped by type (Weekly, Monthly, etc.)
- **A Call-to-Action Section**: "Visit Us This Sunday" with Get Directions button

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

**Note:** Deleting a branch will also delete all its associated programs and services.

### Problems Solved

- **Eliminated** Elementor dependency for content updates
- **Replaced** with dynamic database-driven content
- **Administrators** manage content through WordPress admin
- **End users** never need to touch Elementor

## Features Implemented

**Branch CRUD**

- Automatic WordPress page generation
- Create new branches with hero images
- Edit and delete all branch details

**Program Management**

- Create programs per branch with no limits
- Categorize programs (Weekly/Monthly/Special)
- Delete individual programs

**Service Management**

- Add unlimited services per branch
- Specify a day and time for each service
- Delete individual services

**Dynamic Frontend**

- No Elementor required
- All content managed in admin panel
- Responsive for all devices

**Get Directions Popup**

- Google Maps embed
- General travel tips and landmark information, unique info should be added in the **Directions Info field**

**Settings**

- Customize colors
- Choose font family

**Security**

- All inputs are sanitized
- Nonce-protected forms

## Troubleshooting

### Database table not created

- If you see an error about missing tables, **deactivate and reactivate** the plugin.

### Branch page not showing correctly

- Check that the branch address is correct and accurate. (it's used directly in Google Maps)

### Changes not appearing on frontend

- Changes are instant in the admin dashboard, but they may be cached. Give it a moment to update, then refresh your page.

### Get Directions not showing

- Make sure you've entered a valid address in the branch settings
- Google Maps will center the map around this address
- Add any special information in the "Directions Info" field

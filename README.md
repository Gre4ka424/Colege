# Mediterranean College Alumni Platform

A comprehensive web application designed for Mediterranean College students and alumni to access academic resources, track modules, manage their profiles, and interact with the college community.

## Features

- **User Authentication**: Secure login and profile management
- **Dashboard**: Overview of academic activities and important notifications
- **Modules**: Access to course materials and academic resources
- **Calendar**: Schedule of classes, events, and assignment deadlines
- **Assessments & Grades**: View and track academic performance
- **Live Learning**: Access to online classes and learning materials
- **Social Learning**: Interact with peers and faculty members
- **Profile Management**: Update personal information and avatar

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP
- **Database**: MySQL
- **Deployment**: Railway

## Project Structure

```
Mediterranean-College-Alumni/
├── Frontend/               # Client-side code
│   ├── assets/             # Images and other static assets
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   └── *.html              # HTML pages
└── Backend/                # Server-side code
    ├── api/                # API endpoints
    ├── db/                 # Database related files
    ├── uploads/            # User uploaded files (avatars, etc.)
    └── config/             # Configuration files
```

## Deployment on Railway

This project is configured for deployment on Railway platform. The deployment process handles:

1. PHP and MySQL setup
2. Environment configuration
3. Automatic deployment from the main branch

### Environment Variables

The following environment variables need to be set in Railway:

- `DB_HOST`: Database hostname
- `DB_NAME`: Database name
- `DB_USER`: Database username
- `DB_PASSWORD`: Database password
- `BASE_URL`: Base URL of the deployed application

## Local Development

1. Clone this repository
2. Configure your local web server (Apache, Nginx) to serve the project
3. Import the database schema from `Backend/db/schema.sql`
4. Configure the database connection in `Backend/config/db.php`
5. Access the application via your local web server

## Browser Compatibility

The platform is optimized for:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Contact

For any inquiries regarding this platform, please contact:
- Phone: 210 88 99 600
- Email: platform@mc-class.gr 
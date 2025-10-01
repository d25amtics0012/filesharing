# 📁 File Sharing Application

A simple PHP web application for uploading and sharing files using Supabase as the backend storage. Each uploaded file gets a public URL and QR code for easy access from any device.

## Features

✅ **File Upload** - Drag & drop or click to upload files (max 10MB)  
✅ **QR Codes** - Automatically generated for each file  
✅ **File History** - View all uploaded files with size and date  
✅ **Direct Download** - One-click download links  
✅ **Delete Files** - Remove files from both storage and database  
✅ **Security** - CSRF protection, file validation, secure storage

## Setup Instructions

### 1. Create Supabase Storage Bucket

1. Go to your [Supabase Dashboard](https://supabase.com/dashboard)
2. Navigate to **Storage** → Click **New bucket**
3. Name it: `uploads`
4. Make it **Public** (so files can be accessed via public URLs)
5. Click **Create bucket**

### 2. Create Database Table

1. Go to **SQL Editor** in your Supabase dashboard
2. Run the SQL from `create_table.sql` file
3. This creates the `files` table with proper security settings

### 3. Configure Environment

Your Supabase credentials are already configured in Replit Secrets:
- `SUPABASE_URL` - Your Supabase project URL
- `SUPABASE_ANON_KEY` - Public API key
- `SUPABASE_SERVICE_KEY` - Service role key (server-side only)

### 4. Start Using!

Once the storage bucket and database table are set up, your application is ready to use. Just click the webview to start uploading files!

## File Restrictions

- **Max Size**: 10MB per file
- **Allowed Types**: Images (JPEG, PNG, GIF, WebP), PDF, Text, ZIP files

## Security Notes

⚠️ **Important**: This application does not include user authentication. Anyone who can access the page can upload and delete files. For public deployment, consider:
- Adding Basic Authentication via reverse proxy
- Restricting access by IP address
- Implementing user authentication
- Adding rate limiting

## Technology Stack

- **Backend**: PHP 8.4
- **Database**: PostgreSQL (Supabase)
- **Storage**: Supabase Storage
- **QR Codes**: chillerlan/php-qrcode
- **Frontend**: HTML, CSS, JavaScript

## Project Structure

```
.
├── index.php              # Main application file
├── SupabaseClient.php     # Supabase API wrapper
├── style.css              # Styling
├── create_table.sql       # Database schema
├── composer.json          # PHP dependencies
└── vendor/                # Dependencies (auto-generated)
```

## Development

The application runs on PHP's built-in development server on port 5000. For production deployment, use a proper web server (Nginx/Apache with PHP-FPM) and ensure proper security headers are configured.

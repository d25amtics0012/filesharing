# File Sharing Application

## Overview

This is a PHP-based file sharing application that allows users to upload files and share them via public URLs with QR codes. The application uses Supabase as a backend-as-a-service platform for both file storage and database management. Files are stored in Supabase Storage buckets and metadata is tracked in a PostgreSQL database via Supabase.

## Recent Changes (Oct 1, 2025)

### Security Enhancements
- Added CSRF protection with session tokens on all forms
- Implemented file validation (10MB size limit, restricted MIME types to images, PDF, text, ZIP)
- Added server-side verification for delete operations (fetches file record by ID before deletion)
- Sanitized uploaded filenames to prevent path traversal attacks
- URL-encoded storage paths for safe API calls
- Enhanced error handling with proper curl error checks
- Row Level Security enabled on database (service role bypasses for server operations)

### Features Implemented
- File upload with drag-and-drop support
- QR code generation for each uploaded file
- File history display with metadata (filename, size, upload date)
- Direct download links for all files
- Delete functionality with confirmation dialog
- Clean, modern UI with gradient backgrounds and responsive design

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture
- **Technology**: Plain HTML/CSS with minimal JavaScript
- **Styling Approach**: Custom CSS with gradient backgrounds and modern UI components
- **Design Pattern**: Traditional server-side rendering with form submissions
- **Rationale**: Simple, lightweight approach suitable for a file sharing utility without complex client-side requirements

### Backend Architecture
- **Language**: PHP (version 7.4+)
- **Dependency Management**: Composer for PHP package management
- **QR Code Generation**: chillerlan/php-qrcode library (v5.0+)
  - Chosen for its comprehensive QR code generation features with support for multiple output formats
  - Provides flexible configuration through settings container pattern
- **Application Pattern**: Procedural/script-based PHP without framework
- **Rationale**: Lightweight implementation suitable for simple file operations without the overhead of a full framework

### Data Storage Solutions
- **File Storage**: Supabase Storage (object storage)
  - Public bucket named "uploads" for storing uploaded files
  - Files are accessible via public URLs
  - Chosen for: Scalability, built-in CDN capabilities, and seamless integration with Supabase ecosystem
  
- **Database**: PostgreSQL via Supabase
  - Table: `files` with the following schema:
    - `id`: BIGSERIAL primary key (auto-incrementing)
    - `filename`: TEXT - original filename
    - `file_size`: BIGINT - file size in bytes
    - `public_url`: TEXT - publicly accessible URL from Supabase Storage
    - `uploaded_at`: TIMESTAMP WITH TIME ZONE - upload timestamp with timezone awareness
  - Index: `idx_files_uploaded_at` on uploaded_at column (descending) for efficient time-based queries
  - Row Level Security (RLS) is enabled but no policies are defined

### Authentication and Authorization
- **Service Role Key**: Application uses Supabase service role key for backend operations
- **RLS Bypass**: Service role key bypasses Row Level Security policies, enabling server-side operations without user-specific policies
- **Security Model**: No user authentication implemented; relies on service-level authorization
- **Rationale**: Simplified security model suitable for a file sharing utility where files in public bucket are meant to be accessible

### External Dependencies

**Backend Services:**
- **Supabase**: Primary backend-as-a-service platform
  - Provides PostgreSQL database hosting
  - Object storage via Supabase Storage
  - RESTful API access to database and storage
  - Requires project setup with storage bucket and database table configuration

**PHP Libraries (via Composer):**
- **chillerlan/php-qrcode** (^5.0): QR code generation library
  - Supports multiple output formats (images, SVG, markup, etc.)
  - Provides QR code reader functionality (though likely not used in this application)
  - Dependency: chillerlan/php-settings-container for configuration management
  - Requires PHP extensions: mbstring

**Configuration Requirements:**
- Supabase project credentials (URL and service role key)
- Pre-configured storage bucket named "uploads" (public access)
- Database table "files" with specified schema
- Composer-managed PHP dependencies installed via `composer install`
-- Create the files table in Supabase
-- Run this SQL in your Supabase SQL Editor

CREATE TABLE IF NOT EXISTS files (
    id BIGSERIAL PRIMARY KEY,
    filename TEXT NOT NULL,
    file_size BIGINT NOT NULL,
    public_url TEXT NOT NULL,
    uploaded_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create index for faster queries
CREATE INDEX IF NOT EXISTS idx_files_uploaded_at ON files(uploaded_at DESC);

-- Enable Row Level Security
-- Service role key bypasses RLS, so server-side operations will work
-- This prevents unauthorized access via anon key
ALTER TABLE files ENABLE ROW LEVEL SECURITY;

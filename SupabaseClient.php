<?php

class SupabaseClient {
    private $url;
    private $anonKey;
    private $serviceKey;
    private $bucketName = 'uploads';
    
    public function __construct() {
        $this->url = getenv('SUPABASE_URL');
        $this->anonKey = getenv('SUPABASE_ANON_KEY');
        $this->serviceKey = getenv('SUPABASE_SERVICE_KEY');
        
        if (!$this->url || !$this->anonKey || !$this->serviceKey) {
            throw new Exception('Missing Supabase credentials');
        }
    }
    
    public function uploadFile($filePath, $fileName) {
        $encodedFileName = implode('/', array_map('rawurlencode', explode('/', $fileName)));
        $url = "{$this->url}/storage/v1/object/{$this->bucketName}/{$encodedFileName}";
        
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new Exception("Failed to read file");
        }
        
        $mimeType = mime_content_type($filePath);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fileContent,
            CURLOPT_HTTPHEADER => [
                'Content-Type: ' . $mimeType,
                'apikey: ' . $this->serviceKey,
                'Authorization: Bearer ' . $this->serviceKey
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Upload failed: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Upload failed: " . $response . " (HTTP $httpCode)");
        }
        
        return json_decode($response, true);
    }
    
    public function getPublicUrl($fileName) {
        return "{$this->url}/storage/v1/object/public/{$this->bucketName}/{$fileName}";
    }
    
    public function deleteFile($fileName) {
        $encodedFileName = implode('/', array_map('rawurlencode', explode('/', $fileName)));
        $url = "{$this->url}/storage/v1/object/{$this->bucketName}/{$encodedFileName}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->serviceKey,
                'Authorization: Bearer ' . $this->serviceKey
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Delete failed: " . $error);
        }
        
        if ($httpCode !== 200 && $httpCode !== 204) {
            throw new Exception("Delete failed: " . $response . " (HTTP $httpCode)");
        }
        
        return true;
    }
    
    public function insertFileRecord($fileName, $fileSize, $publicUrl) {
        $url = "{$this->url}/rest/v1/files";
        
        $data = [
            'filename' => $fileName,
            'file_size' => $fileSize,
            'public_url' => $publicUrl,
            'uploaded_at' => date('c')
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $this->serviceKey,
                'Authorization: Bearer ' . $this->serviceKey,
                'Prefer: return=representation'
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            throw new Exception("Database insert failed: " . $response . " (HTTP $httpCode)");
        }
        
        return json_decode($response, true);
    }
    
    public function getFiles() {
        $url = "{$this->url}/rest/v1/files?order=uploaded_at.desc";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->serviceKey,
                'Authorization: Bearer ' . $this->serviceKey
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to fetch files: " . $response . " (HTTP $httpCode)");
        }
        
        return json_decode($response, true);
    }
    
    public function getFileById($id) {
        $url = "{$this->url}/rest/v1/files?id=eq.{$id}&select=*";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->serviceKey,
                'Authorization: Bearer ' . $this->serviceKey
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Failed to fetch file: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Failed to fetch file: " . $response . " (HTTP $httpCode)");
        }
        
        $data = json_decode($response, true);
        return !empty($data) ? $data[0] : null;
    }
    
    public function deleteFileRecord($id) {
        $url = "{$this->url}/rest/v1/files?id=eq.{$id}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $this->serviceKey,
                'Authorization: Bearer ' . $this->serviceKey
            ],
            CURLOPT_RETURNTRANSFER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 204 && $httpCode !== 200) {
            throw new Exception("Database delete failed: " . $response . " (HTTP $httpCode)");
        }
        
        return true;
    }
}

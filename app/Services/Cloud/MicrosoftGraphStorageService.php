<?php

namespace App\Services\Cloud;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MicrosoftGraphStorageService
{
    private ?string $clientId;

    private ?string $clientSecret;

    private string $tenantId;

    private ?string $userId;

    private ?string $driveId;

    private string $baseFolder;

    public function __construct()
    {
        $this->clientId = config('services.microsoft.client_id');
        $this->clientSecret = config('services.microsoft.client_secret');
        $this->tenantId = config('services.microsoft.tenant_id', 'common');
        $this->userId = config('services.microsoft.user_id');
        $this->driveId = config('services.microsoft.drive_id');
        $this->baseFolder = trim((string) config('services.microsoft.folder', 'CBM_Evidence'), '/');
    }

    /**
     * Check if Microsoft credentials are configured in .env.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->clientId) && ! empty($this->clientSecret);
    }

    /**
     * Obtain OAuth2 access token from Microsoft Entra ID (Azure AD).
     */
    public function getAccessToken(): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $cacheKey = 'ms_graph_token_'.md5($this->clientId.$this->tenantId);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            try {
                $tokenUrl = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

                $response = Http::asForm()->timeout(15)->post($tokenUrl, [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('Microsoft Graph Auth Error: '.$response->body());

                return null;
            } catch (\Exception $e) {
                Log::error('Microsoft Graph Auth Exception: '.$e->getMessage());

                return null;
            }
        });
    }

    /**
     * Upload evidence binary to Microsoft Outlook / OneDrive (1 TB).
     * Automatically organizes into folders: {baseFolder}/{DOC_TYPE}/{DOC_NO}/
     *
     * Falls back to local public storage if Microsoft credentials are not yet configured.
     *
     * @return array{
     *     success: bool,
     *     storage: string,
     *     filename: string,
     *     cloud_path: string,
     *     web_url: string|null,
     *     local_path: string|null,
     *     size: int,
     *     message: string
     * }
     */
    public function uploadEvidence(
        string $fileContents,
        string $docType,
        string $docNo,
        ?string $tcType = null,
        string $extension = 'jpg',
        ?string $mimeType = 'image/jpeg'
    ): array {
        $cleanDocType = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $docType));
        $cleanDocNo = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $docNo));
        $cleanTc = $tcType ? strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $tcType)) : 'EVIDENCE';
        $timestamp = now()->format('Ymd_His');
        $random = Str::random(4);
        $extension = ltrim($extension, '.');

        $filename = "{$cleanDocNo}_{$cleanTc}_{$timestamp}_{$random}.{$extension}";
        $folderPath = "{$this->baseFolder}/{$cleanDocType}/{$cleanDocNo}";
        $fullPath = "{$folderPath}/{$filename}";

        $fileSize = strlen($fileContents);

        // Always save a local copy in storage/app/public/evidence for backup and fast serving
        $localRelativePath = "evidence/{$cleanDocType}/{$cleanDocNo}/{$filename}";
        Storage::disk('public')->put($localRelativePath, $fileContents);
        $localUrl = Storage::disk('public')->url($localRelativePath);

        // If Microsoft Graph is configured, upload to OneDrive 1TB
        if ($this->isConfigured()) {
            $token = $this->getAccessToken();
            if ($token) {
                $uploadResult = $this->putToDrive($token, $fullPath, $fileContents, $mimeType);
                if ($uploadResult['success']) {
                    Log::info("Uploaded evidence to Microsoft OneDrive: {$fullPath}");

                    return [
                        'success' => true,
                        'storage' => 'onedrive',
                        'filename' => $filename,
                        'cloud_path' => $fullPath,
                        'web_url' => $uploadResult['web_url'] ?? $localUrl,
                        'local_path' => $localRelativePath,
                        'size' => $fileSize,
                        'message' => 'Berhasil disimpan ke Microsoft Outlook (OneDrive 1 TB)',
                    ];
                }

                Log::warning("Failed to upload to OneDrive, using local storage: {$uploadResult['error']}");
            }
        }

        // Local storage fallback
        return [
            'success' => true,
            'storage' => 'local_fallback',
            'filename' => $filename,
            'cloud_path' => $fullPath,
            'web_url' => $localUrl,
            'local_path' => $localRelativePath,
            'size' => $fileSize,
            'message' => 'Disimpan di storage lokal (kredensial Microsoft Graph belum diatur di .env)',
        ];
    }

    /**
     * Perform HTTP PUT upload to Microsoft Graph Drive.
     */
    private function putToDrive(string $token, string $path, string $content, ?string $mimeType): array
    {
        try {
            $encodedPath = rawurlencode($path);

            if ($this->userId) {
                $url = "https://graph.microsoft.com/v1.0/users/{$this->userId}/drive/root:/{$path}:/content";
            } elseif ($this->driveId) {
                $url = "https://graph.microsoft.com/v1.0/drives/{$this->driveId}/root:/{$path}:/content";
            } else {
                $url = "https://graph.microsoft.com/v1.0/me/drive/root:/{$path}:/content";
            }

            $response = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => $mimeType ?: 'application/octet-stream',
                ])
                ->timeout(30)
                ->withBody($content, $mimeType ?: 'application/octet-stream')
                ->put($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'id' => $response->json('id'),
                    'name' => $response->json('name'),
                    'web_url' => $response->json('webUrl'),
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP '.$response->status().': '.$response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

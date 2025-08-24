<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class MiltyService
{
    /**
     * Extract draft ID from a Milty URL
     *
     * @param string $url
     * @return string|null
     */
    public function extractDraftId(string $url): ?string
    {
        // Extract draft ID from URL like https://milty.shenanigans.be/d/688cc17676088
        if (preg_match('/\/d\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Fetch draft data from Milty API
     *
     * @param string $draftId
     * @return array|null
     */
    public function fetchDraftData(string $draftId): ?array
    {
        try {
            // Skip SSL verification in local environment
            $http = Http::withOptions([
                'verify' => !App::environment('local'),
            ]);

            $response = $http->get("https://milty.shenanigans.be/api/data", [
                'draft' => $draftId
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch Milty draft data', [
                'draft_id' => $draftId,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception while fetching Milty draft data', [
                'draft_id' => $draftId,
                'exception' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Validate if a URL is a valid Milty URL
     *
     * @param string $url
     * @return bool
     */
    public function isValidMiltyUrl(string $url): bool
    {
        $draftId = $this->extractDraftId($url);

        if (!$draftId) {
            return false;
        }

        // Optionally check if the draft actually exists
        $draftData = $this->fetchDraftData($draftId);

        return $draftData !== null;
    }
}

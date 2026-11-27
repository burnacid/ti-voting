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
        // Anchored to the Milty host, so the stored URL cannot be e.g. a javascript: link
        if (preg_match('~^https?://(www\.)?milty\.shenanigans\.be/d/([a-zA-Z0-9_]+)~', $url, $matches)) {
            return $matches[2];
        }

        return null;
    }

    public static function draftUrl(string $draftId): string
    {
        return "https://milty.shenanigans.be/d/{$draftId}";
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
            $http = Http::timeout(10)->withOptions([
                'verify' => !App::environment('local'),
            ]);

            $response = $http->get("https://milty.shenanigans.be/api/draft/{$draftId}");

            if ($response->successful()) {
                $data = $response->json();

                // The rest of the app reads the players of the draft, so other responses are not a usable draft
                if (is_array($data) && is_array($data['draft']['players'] ?? null)) {
                    return $data;
                }

                Log::error('Milty draft data has no players', ['draft_id' => $draftId]);

                return null;
            }

            Log::error('Failed to fetch Milty draft data', [
                'draft_id' => $draftId,
                'status' => $response->status(),
                'response' => \Illuminate\Support\Str::limit($response->body(), 500)
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
}

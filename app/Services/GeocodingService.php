<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocodingService
{
    public function search(string $query): array
    {
        $driver = config('geocoder.driver', 'osm');

        return match ($driver) {
            'google' => $this->googleSearch($query),
            'osm'    => $this->osmSearch($query),
            default  => [],
        };
    }

    /**
     * Google Autocomplete (Places API New)
     */
    protected function googleSearch(string $query): array
    {
        $apiKey = config('geocoder.google.key');
        $url = config('geocoder.google.autocomplete_url');

        $response = Http::withHeaders([
            'Content-Type'     => 'application/json',
            'X-Goog-Api-Key'   => $apiKey,
            'X-Goog-FieldMask' => 'suggestions.placePrediction.place,'
                .'suggestions.placePrediction.structuredFormat,'
                .'suggestions.placePrediction.text',
        ])->post($url, [
            'input' => $query,
        ])->json();

        $suggestions = $response['suggestions'] ?? [];

        return collect($suggestions)->map(function ($item) {
            $p = $item['placePrediction'] ?? [];

            return [
                'place_id'    => $p['place'] ?? $p['placeId'] ?? null,
                'description' => $p['structuredFormat']['mainText']['text']
                    . ', '
                    . ($p['structuredFormat']['secondaryText']['text'] ?? ''),
                'lat' => null,
                'lng' => null,
            ];
        })->all();
    }

    /**
     * Google Place Details (Places API New)
     * Lấy lat/lng theo place_id
     */
    public function getGooglePlaceDetail(string $placeId): ?array
    {
        $apiKey = config('geocoder.google.key');
        $cleanId = str_replace('places/', '', $placeId);
        $url = config('geocoder.google.details_url') . $cleanId;

        $response = Http::withHeaders([
            'X-Goog-Api-Key'   => $apiKey,
            'X-Goog-FieldMask' => 'id,location,formattedAddress,displayName',
        ])->get($url)->json();

        if (!isset($response['location'])) {
            return null;
        }

        return [
            'lat' => $response['location']['latitude'],
            'lng' => $response['location']['longitude'],
            'address' => $response['formattedAddress']
                ?? $response['displayName']['text']
                ?? null,
        ];
    }

    /**
     * OSM API
     */
    protected function osmSearch(string $query): array
    {
        $url = config('geocoder.osm.base_url', 'https://nominatim.openstreetmap.org/search');

        $response = Http::withHeaders([
            'User-Agent' => 'MyApp/1.0',
        ])->get($url, [
            'format' => 'json',
            'q' => $query,
            'limit' => 5,
            'addressdetails' => 1,
        ])->json();

        return collect($response ?? [])->map(fn($item) => [
            'id' => $item['place_id'] ?? $item['osm_id'],
            'description' => $item['display_name'],
            'lat' => $item['lat'],
            'lng' => $item['lon'],
        ])->all();
    }
}

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
     * Google Autocomplete API
     * Trả về list suggestion (chỉ description + place_id)
     */
    protected function googleSearch(string $query): array
    {
        $apiKey = config('geocoder.google.key');
        $url = config('geocoder.google.base_url', 'https://maps.googleapis.com/maps/api/place/autocomplete/json');

        $response = Http::get($url, [
            'input' => $query,
            'key' => $apiKey,
            'components' => 'country:vn',
        ])->json();

        $predictions = $response['predictions'] ?? [];

        return collect($predictions)->map(fn($item) => [
            'id' => $item['place_id'],
            'description' => $item['description'],
            'lat' => null, // chưa có lat/lng
            'lng' => null,
        ])->all();
    }

    /**
     * Google Place Detail API
     * Lấy lat/lng cho 1 place_id
     */
    public function getGooglePlaceDetail(string $placeId): ?array
    {
        $apiKey = config('geocoder.google.key');
        $url = config('geocoder.google.details_url', 'https://maps.googleapis.com/maps/api/place/details/json');

        $response = Http::get($url, [
            'place_id' => $placeId,
            'key'      => $apiKey,
        ])->json();

        $result = $response['result'] ?? null;
        $location = $result['geometry']['location'] ?? null;

        return $location ? [
            'lat' => $location['lat'],
            'lng' => $location['lng'],
            'address' => $result['formatted_address'] ?? null,
        ] : null;
    }

    /**
     * OpenStreetMap Nominatim API
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

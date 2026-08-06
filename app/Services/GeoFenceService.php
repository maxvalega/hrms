<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Utility;

class GeoFenceService
{
    /**
     * Validate that punch coordinates are within the allowed radius.
     *
     * @return array{allowed: bool, message: string, distance_metres: float|null, radius_metres: int|null}
     */
    public function validatePunch(?float $latitude, ?float $longitude, ?Employee $employee = null, ?int $createdBy = null): array
    {
        $createdBy = $createdBy ?: (\Auth::check() ? (int) \Auth::user()->creatorId() : (int) ($employee->created_by ?? 0));
        $settings = $createdBy > 0 ? Utility::settingsByUser($createdBy) : Utility::settings();

        if (($settings['geo_fencing_enabled'] ?? 'off') !== 'on') {
            return [
                'allowed' => true,
                'message' => '',
                'distance_metres' => null,
                'radius_metres' => null,
            ];
        }

        $radius = max(1, (int) ($settings['geo_fence_radius_metres'] ?? 100));

        $office = $this->resolveOfficeCoordinates($employee, $settings);
        if ($office === null) {
            return [
                'allowed' => false,
                'message' => __('Geo tagging is enabled, but office location is not configured. Please set company or branch latitude/longitude in Settings.'),
                'distance_metres' => null,
                'radius_metres' => $radius,
            ];
        }

        if ($latitude === null || $longitude === null || ! is_numeric($latitude) || ! is_numeric($longitude)) {
            return [
                'allowed' => false,
                'message' => __('Location is required for attendance. Please enable GPS and try again.'),
                'distance_metres' => null,
                'radius_metres' => $radius,
            ];
        }

        $distance = $this->distanceInMetres(
            (float) $office['latitude'],
            (float) $office['longitude'],
            (float) $latitude,
            (float) $longitude
        );

        if ($distance > $radius) {
            return [
                'allowed' => false,
                'message' => __('You are :distance m away from the office. Allowed radius is :radius m.', [
                    'distance' => (int) round($distance),
                    'radius' => $radius,
                ]),
                'distance_metres' => round($distance, 1),
                'radius_metres' => $radius,
            ];
        }

        return [
            'allowed' => true,
            'message' => '',
            'distance_metres' => round($distance, 1),
            'radius_metres' => $radius,
        ];
    }

    /**
     * Prefer employee branch coordinates; fall back to company settings.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function resolveOfficeCoordinates(?Employee $employee, array $settings): ?array
    {
        if ($employee && ! empty($employee->branch_id)) {
            $branch = Branch::find($employee->branch_id);
            if ($branch
                && $branch->latitude !== null
                && $branch->longitude !== null
                && $branch->latitude !== ''
                && $branch->longitude !== ''
            ) {
                return [
                    'latitude' => (float) $branch->latitude,
                    'longitude' => (float) $branch->longitude,
                ];
            }
        }

        $lat = $settings['company_latitude'] ?? null;
        $lng = $settings['company_longitude'] ?? null;
        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            return null;
        }

        return [
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ];
    }

    /**
     * Haversine distance in metres.
     */
    public function distanceInMetres(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // metres
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}

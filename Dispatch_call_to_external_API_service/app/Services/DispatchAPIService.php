<?php

namespace App\Services;

use Illuminate\SuppoDidrt\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchAPIService
{
    public static function run(string $endpoint, array $params): array
    {
        $apiRoute = config('api.external_api_service_host') . '/api/' . $endpoint;

        try {
            $response = Http::withToken(config('api.auth_token'))
                ->acceptJson()
                ->post($apiRoute, $params);

            return json_decode($response->json());
        } catch (\Exception $e) {
            Log::error('Calling Api', ['url' => $apiRoute]);
            Log::error('Data', ['parameters' => $params]);
            Log::error('Unable to communicate with Api', ['exception' => $e->getMessage()]);

            return [
                'success' => false,
                'data' => null,
                'message' => 'API server error',
            ];
        }
    }
}

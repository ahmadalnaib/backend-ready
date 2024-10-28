<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ProductGroupController extends Controller
{
    /**
     * Fetch product groups.
     */
    public function fetchProductGroups()
    {
        // Step 1: Log in and get the bearer token
        $loginResponse = Http::post('http://192.168.200.56:8000/api/v1/login/via-token/n7dp3qklxu92vq1lmbhyzaosnhkpye6jtmt9xcwrkf8spyumganx5i4jvl7bzohe', []);
        
        if ($loginResponse->successful()) {
            $token = $loginResponse->json('body.token');

            // Step 2: Fetch product groups
            $groupPayload = [
                ["name" => "timestamp", "type" => "C", "value" => "19700101000000000"],
                ["name" => "getstructure", "type" => "L", "value" => "false"],
                ["name" => "getbilder" ,"type" => "L", "value" =>"true"]
            ];

            $groupResponse = Http::withToken($token)
                ->post('http://192.168.200.56:8000/api/v1/execute/WEBSHOP_GETPRODUKTGRUPPEN', $groupPayload);

            if ($groupResponse->successful()) {
                $groups = $groupResponse->json();

                // Check if the expected data structure exists
                if (isset($groups['body']['data']['object']['data']['output']['productgroups']['productgroupitem'])) {
                    $produktgruppen = $groups['body']['data']['object']['data']['output']['productgroups']['productgroupitem'];
                    return response()->json($produktgruppen);
                } else {
                    Log::error('Group data not structured as expected:', $groups);
                    return response()->json(['error' => 'Group data not structured as expected'], 500);
                }
            } else {
                Log::error('Failed to fetch product groups:', $groupResponse->json());
                return response()->json(['error' => 'Failed to fetch product groups', 'details' => $groupResponse->body()], 500);
            }
        } else {
            Log::error('Failed to login and get token:', $loginResponse->json());
            return response()->json(['error' => 'Failed to login and get token', 'details' => $loginResponse->body()], 500);
        }
    }
}
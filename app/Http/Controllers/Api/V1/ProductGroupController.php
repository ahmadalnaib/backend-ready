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
        $token = $this->loginAndGetToken();
        if (!$token) {
            return response()->json(['error' => 'Failed to login and get token'], 500);
        }

        $groups = $this->fetchGroups($token);
        if (!$groups) {
            return response()->json(['error' => 'Failed to fetch group data'], 500);
        }

        $detailedChildProdGroups = $this->processGroups($groups);
        if ($detailedChildProdGroups === null) {
            return response()->json(['error' => 'No product group with bezeichnung containing "Home_NEU" found'], 404);
        }

        return response()->json($detailedChildProdGroups);
    }

    private function loginAndGetToken()
    {
        $loginResponse = Http::post('http://192.168.200.56:8000/api/v1/login/via-token/n7dp3qklxu92vq1lmbhyzaosnhkpye6jtmt9xcwrkf8spyumganx5i4jvl7bzohe', []);
        
        if ($loginResponse->successful()) {
            return $loginResponse->json('body.token');
        } else {
            Log::error('Failed to login and get token:', $loginResponse->body());
            return null;
        }
    }

    private function fetchGroups($token)
    {
        $groupPayload = [
            ["name" => "timestamp", "type" => "C", "value" => "19700101000000000"],
            ["name" => "getstructure", "type" => "L", "value" => "true"],
            ["name" => "getbilder" ,"type" => "L", "value" =>"true"]
        ];

        $groupResponse = Http::withToken($token)
            ->post('http://192.168.200.56:8000/api/v1/execute/WEBSHOP_GETPRODUKTGRUPPEN', $groupPayload);
        
        if ($groupResponse->successful()) {
            return $groupResponse->json();
        } else {
            Log::error('Failed to fetch group data:', $groupResponse->body());
            return null;
        }
    }

    private function processGroups($groups)
    {
        if (!isset($groups['body']['data']['object']['data']['output']['productgroups']['productgroupitem'])) {
            Log::error('Group data not structured as expected:', $groups);
            return null;
        }

        $produktgruppen = $groups['body']['data']['object']['data']['output']['productgroups']['productgroupitem'];
        
        $homeNeuGroup = array_filter($produktgruppen, function($group) {
            return strpos($group['bezeichnung'], 'Home_NEU') !== false;
        });

        if (empty($homeNeuGroup)) {
            return null;
        }

        $homeNeuGroup = array_values($homeNeuGroup)[0];
        $childProdGroups = $homeNeuGroup['childprodgroups']['childprodgroup'] ?? [];

        return array_filter($produktgruppen, function($group) use ($childProdGroups) {
            return in_array($group['productgroupid'], array_column($childProdGroups, 'productgroupid'));
        });
    }
}
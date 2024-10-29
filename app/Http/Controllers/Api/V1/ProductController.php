<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $token = $this->loginAndGetToken();
        if (!$token) {
            return response()->json(['error' => 'Failed to login and get token'], 500);
        }

        $products = $this->fetchProducts($token);
        if (!$products) {
            return response()->json(['error' => 'Failed to fetch product data'], 500);
        }

        $processedProducts = $this->processProducts($products, $token);
        if ($processedProducts === null) {
            return response()->json(['error' => 'Product data not structured as expected'], 500);
        }

        return response()->json($processedProducts);
    }

    private function loginAndGetToken()
    {
        $loginResponse = Http::post('http://192.168.200.56:8000/api/v1/login/via-token/n7dp3qklxu92vq1lmbhyzaosnhkpye6jtmt9xcwrkf8spyumganx5i4jvl7bzohe', []);
        
        if ($loginResponse->successful()) {
            return $loginResponse->json('body.token');
        } else {
            Log::error('Failed to login and get token:', $loginResponse->json());
            return null;
        }
    }

    private function fetchProducts($token)
    {
        $payload = [
            ["name" => "getbilder", "type" => "L", "value" => "true"],
            ["name" => "getpreis", "type" => "L", "value" => "true"],
            ["name" => "artikelids", "type" => "C", "value" => "48110,48113,48117"]
        ];

        $productResponse = Http::withToken($token)
            ->post('http://192.168.200.56:8000/api/v1/execute/WEBSHOP_GETARTIKELDATEN', $payload);
        
        if ($productResponse->successful()) {
            return $productResponse->json();
        } else {
            Log::error('Failed to fetch product data:', $productResponse->json());
            return null;
        }
    }

    private function processProducts($products, $token)
    {
        if (!isset($products['body']['data']['object']['data']['output']['artikellist']['artikel'])) {
            Log::error('Product data not structured as expected:', $products);
            return null;
        }

        $artikellist = $products['body']['data']['object']['data']['output']['artikellist']['artikel'];

        foreach ($artikellist as &$artikel) {
            if (isset($artikel['bilder']['bild'])) {
                $bilder = $artikel['bilder']['bild'];
                if (isset($bilder['bilddatei'])) {
                    $this->fetchBase64Image($bilder['bilddatei'], $token, $artikel);
                } elseif (is_array($bilder)) {
                    foreach ($bilder as $bild) {
                        if (isset($bild['bilddatei'])) {
                            $this->fetchBase64Image($bild['bilddatei'], $token, $artikel);
                        }
                    }
                }
            }
        }

        return $artikellist;
    }

    private function fetchBase64Image($imagePath, $token, &$artikel)
    {
        $imagePayload = [
            ["name" => "bildpfad", "type" => "C", "value" => $imagePath]
        ];

        $imageResponse = Http::withToken($token)
            ->post('http://192.168.200.56:8000/api/v1/execute/WEBSHOP_GETBASE64BILD', $imagePayload);

        if ($imageResponse->successful()) {
            $base64Image = $imageResponse->json('body.data.object.data.output.bild_base64.#text');
            if ($base64Image) {
                $artikel['bilder']['bild']['base64'] = "data:image/jpeg;base64," . $base64Image;
            } else {
                Log::warning('Base64 image data not found for image path: ' . $imagePath);
            }
        } else {
            Log::error('Failed to fetch base64 image for path: ' . $imagePath);
        }
    }

   
}
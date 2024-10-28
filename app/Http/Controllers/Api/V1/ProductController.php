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
        // Step 1: Log in and get the bearer token
        $loginResponse = Http::post('http://192.168.200.56:8000/api/v1/login/via-token/n7dp3qklxu92vq1lmbhyzaosnhkpye6jtmt9xcwrkf8spyumganx5i4jvl7bzohe', []);
        

        if ($loginResponse->successful()) {
            $token = $loginResponse->json('body.token');

            // Step 2: Define payload and fetch products
            $payload = [
                ["name" => "timestamp", "type" => "C", "value" => "202012310000000"],
                ["name" => "getbilder", "type" => "L", "value" => "true"],
                ["name" => "getpreis", "type" => "L", "value" => "true"]
            ];

            $productResponse = Http::withToken($token)
                ->post('http://192.168.200.56:8000/api/v1/execute/WEBSHOP_GETARTIKELDATEN', $payload);


            if ($productResponse->successful()) {
                $products = $productResponse->json();


                // Check if there is a proper path to access the articles
                if (isset($products['body']['data']['object']['data']['output']['artikellist']['artikel'])) {
                    $artikellist = $products['body']['data']['object']['data']['output']['artikellist']['artikel'];

                    // Step 3: Process each product to fetch the base64 image data
                    foreach ($artikellist as &$artikel) {
                        if (isset($artikel['bilder']['bild'])) {
                            $bilder = $artikel['bilder']['bild']; // This can be an array or a single item

                            // Ensure we handle both cases where 'bilder' can be a single image or an array of images
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

                    return response()->json($artikellist);
                } else {
                    Log::error('Product data not structured as expected:', $products);
                    return response()->json(['error' => 'Product data not structured as expected'], 500);
                }
            } else {
                Log::error('Failed to fetch product data:', $productResponse->json());
                return response()->json(['error' => 'Failed to fetch product data'], 500);
            }
        } else {
            Log::error('Failed to login and get token:', $loginResponse->json());
            return response()->json(['error' => 'Failed to login and get token'], 500);
        }
    }

    /**
     * Fetch base64 image and add it to the article
     */
    private function fetchBase64Image($imagePath, $token, &$artikel)
    {
    
        // Prepare the payload for base64 image retrieval
        $imagePayload = [
            ["name" => "bildpfad", "type" => "C", "value" => $imagePath]
        ];

        // Fetch the base64 image
        $imageResponse = Http::withToken($token)
            ->post('http://192.168.200.56:8000/api/v1/execute/WEBSHOP_GETBASE64BILD', $imagePayload);

        if ($imageResponse->successful()) {
            // Adjust this line based on your actual API response structure
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

    // Other methods (create, store, show, edit, update, destroy) remain unchanged
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApiAuthRequest;

class AuthController extends Controller
{
    //
    use ApiResponses;
    public function login(ApiAuthRequest $request){
        return $this->ok($request->get('email'));
      
    }

    public function register(Request $request){
        return $this->ok('register');
    }
}

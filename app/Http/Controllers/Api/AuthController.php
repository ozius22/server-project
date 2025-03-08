<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Interfaces\Services\AuthServiceInterface;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private $authService;

    public function __construct(AuthServiceInterface $auth_service)
    {

        $this->authService = $auth_service;
    }

    public function authenticate(AuthRequest $request)
    {
        return $this->authService->login($request);
    }

    public function unauthenticate(Request $request)
    {
        return $this->authService->logout($request);
    }
}

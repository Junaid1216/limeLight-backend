<?php

namespace App\Services\Api;

use App\Repositories\Api\Interfaces\AuthRepositoryInterface;

class AuthService
{
    protected $authRepo;
    public function __construct(AuthRepositoryInterface $authRepo)
    {
        $this->authRepo = $authRepo;
    }
    public function register(array $request)
    {
        return $this->authRepo->register($request);
    }
    public function login(array $request)
    {
        return $this->authRepo->login($request);
    }
    public function sendOtp(array $request)
    {
        return $this->authRepo->sendOtp($request);
    }
    public function verifyOtp(array $request)
    {
        return $this->authRepo->verifyOtp($request);
    }
    public function resetPassword(array $request)
    {
        return $this->authRepo->resetPassword($request);
    }
    public function logout()
    {
        return $this->authRepo->logout();
    }
    public function updateProfile(array $request)
    {
        return $this->authRepo->updateProfile($request);
    }
    public function changePassword(array $request)
    {
        return $this->authRepo->changePassword($request);
    }
}

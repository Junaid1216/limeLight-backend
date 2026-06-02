<?php
namespace App\Repositories\Api\Interfaces;
interface AuthRepositoryInterface
{
    public function register(array $request);
    public function login(array $request);
    public function logout();
    public function sendOtp(array $request);
    public function verifyOtp(array $request);
    public function resetPassword(array $request);
    public function updateProfile(array $request);
    public function changePassword(array $request);
}

<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Interfaces\Services\AuthServiceInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthService implements AuthServiceInterface
{
    private $userRepository;

    public function __construct(
        UserRepositoryInterface $user_repository
    ) {
        $this->userRepository = $user_repository;
    }

    public function login(object $payload)
    {
        $user = $this->userRepository->findByEmail($payload->email);

        if (! $user) {
            return $this->errorResponse('exception.invalid_email.message');
        }

        // app()->setLocale($user->language->code);

        $this->validateUser($user, $payload->password);

        $token = $user->createToken('auth-token')->plainTextToken;

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
                'login_time' => now()->toDateTimeString(),
            ])
            ->log('User logged in');

        // $payload->event = 'Login';
        // $payload->description = 'logged in.';
        // $this->activityLogRepository->create($payload, $user);

        return response()->json([
            'token' => $token,
            // 'user' => new UserResource($user),
        ], Response::HTTP_OK);
    }

    public function logout(object $payload)
    {
        // try {
        //     $user = auth()->user();
        //     app()->setLocale($user->language->code);

        //     if (! $user) {
        //         return response()->json([
        //             'message' => trans('exception.unsucessful_logout.message'),
        //         ], Response::HTTP_BAD_REQUEST);
        //     }

        //     $user->tokens()->delete();

        //     return response()->json([
        //         'message' => trans('exception.successful_logout.message'),
        //     ], Response::HTTP_OK);
        // } catch (Throwable $e) {
        //     Log::debug($e);

        //     return response()->json([
        //         'message' => $e->getMessage(),
        //     ], Response::HTTP_BAD_REQUEST);
        // }
    }

    private function validateUser($user, $password)
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'message' => trans('exception.invalid_password.message'),
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'message' => trans('exception.inactive_user.message'),
            ]);
        }

        if (! $user->is_email_verified) {
            throw ValidationException::withMessages([
                'message' => trans('exception.unverified_email.message'),
            ]);
        }

        if ($user->is_archived) {
            throw ValidationException::withMessages([
                'message' => trans('exception.archived_user.message'),
            ]);
        }

        if ($user->trashed()) {
            throw ValidationException::withMessages([
                'message' => trans('exception.deleted_user.message'),
            ]);
        }
    }

    private function errorResponse(string $messageKey, int $status = Response::HTTP_BAD_REQUEST)
    {
        return response()->json([
            'message' => trans($messageKey),
        ], $status);
    }
}

<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Jobs\ActivityLogJob;
use App\Models\Admin\ActivityLog;
use App\Models\Setup\SetupCounter;
use App\Models\User\User;
use App\Notifications\User\WelcomeMail;
use App\Services\Cache\ClearCacheService;
use App\Services\Config;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserManagementController extends Controller
{
    // Display a listing of the resource.
    public function index(Request $request)
    {
        try {
            $baseQuery = User::with([
                'title:title_id,title_name',
                'gender:gender_id,gender_name',
                'status:status_id,status_name',
                'lga:lga_id,lga_name,state_id',
                'lga.state:state_id,state_name,country_id',
                'lga.state.country:country_id,country_name',
            ]);

            $activeCount = (clone $baseQuery)->where('status_id', 1)->count();
            $suspendedCount = (clone $baseQuery)->where('status_id', 3)->count();

            if ($request->filled('status_id')) {
                $baseQuery->where('status_id', $request->status_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('user_id', 'like', "%{$search}%");
                });
            }

            $userData = $baseQuery
                ->orderBy('last_name', 'asc')
                ->cursorPaginate(30);

            if ($userData->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No User records found.',
                    'summary' => [
                        'active_count' => $activeCount,
                        'suspended_count' => $suspendedCount,
                        'total_count' => $activeCount + $suspendedCount,
                    ],
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'User records fetched successfully.',
                'summary' => [
                    'active_count' => $activeCount,
                    'suspended_count' => $suspendedCount,
                    'total_count' => $activeCount + $suspendedCount,
                ],
                'data' => UserResource::collection($userData),
                'pagination' => [
                    'next_cursor' => $userData->nextCursor()?->encode(),
                    'previous_cursor' => $userData->previousCursor()?->encode(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user records: ' . $e->getMessage()
            ], 500);
        }
    }

    // Store a newly created resource in storage.
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                // ================= MEMBER =================
                'titleId' => 'required|integer|exists:setup_titles,title_id',
                'firstName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
                'middleName' => ['nullable', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
                'lastName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
                'genderId' => 'required|integer|exists:setup_genders,gender_id',
                'emailAddress' => 'required|string|email|max:255|unique:users,email',
                'mobileNumber' => ['required', 'string', 'unique:users,mobile_number'],
                'homeAddress' => 'nullable|string|max:255',
            ]);

            $admin = Auth::guard('admin')->user();

            $user = null;
            $userId = null;
            $registeredData = [];

            DB::transaction(function () use ($request, $admin, &$user, &$userId, &$registeredData) {
                $userId = SetupCounter::generateCustomId('MEM');
                $user = User::create([
                    'user_id' => $userId,
                    'title_id' => $request->titleId,
                    'first_name' => strtoupper($request->firstName),
                    'middle_name' => $request->middleName ? strtoupper($request->middleName) : null,
                    'last_name' => strtoupper($request->lastName),
                    'gender_id' => $request->genderId,
                    'email' => strtolower($request->emailAddress),
                    'mobile_number' => $request->mobileNumber,
                    'home_address' => $request->homeAddress ? strtoupper($request->homeAddress) : null,
                    'created_by' => $admin->staff_id ?? $userId,
                    'updated_by' => $admin->staff_id ?? $userId,
                    'password' => $request->lastName . '123',
                ]);

                // ================= EMAIL =================
                try {
                    $titleName = Config::getTitleNameById($user->title_id);
                    $fullName = $request->lastName . ' ' . $request->firstName;

                    $user->notify(new WelcomeMail(
                        Str::title($fullName),
                        Str::title($titleName),
                        $request->emailAddress,
                        $request->lastName
                    ));
                } catch (\Throwable $e) {
                    \Log::error('Signup mail notification error: ' . $e->getMessage());
                }

                ClearCacheService::clearListCache('user_list');

                // ================= PREPARE LOG DATA =================
                $registeredData = $user->only([
                    'user_id',
                    'title_id',
                    'first_name',
                    'middle_name',
                    'last_name',
                    'gender_id',
                    'email',
                    'mobile_number',
                    'status',
                    'created_by',
                    'created_at',
                ]);
            });

            try {
                ActivityLogJob::dispatch(
                    modelClass: ActivityLog::class,
                    action: 'New user registration',
                    description: "A new user with ID: {$userId} has been registered.",
                    userType: $admin ? 'Staff' : 'Member',
                    performedBy: $admin->staff_id ?? $userId,
                    roleId: $admin?->roles?->pluck('id')->first() ?? 0,
                    metadata: [
                        'Registered Data' => $registeredData,
                    ],
                    deviceInfo: Config::requestDetails(),
                )->afterCommit();
            } catch (\Throwable $e) {
                \Log::error('Activity log error during signup: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'user created successfully. Default password is ' . $request->lastName . '123',
                'data' => [
                    'user_id' => $userId,
                    'email' => $request->emailAddress,
                    'default_password' => $request->lastName . '123'
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flat()->first() ?: 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Display the specified resource.
    public function show(string $id)
    {
        try {
            Cache::forget("user_profile_{$id}");
            $userData = new UserResource(User::with([
                'title:title_id,title_name',
                'gender:gender_id,gender_name',
                'status:status_id,status_name',
                'lga:lga_id,lga_name,state_id',
                'lga.state:state_id,state_name,country_id',
                'lga.state.country:country_id,country_name',
            ])->findOrFail($id));

            return response()->json([
                'success' => true,
                'message' => 'User profile fetched successfully.',
                'data' => $userData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user profile: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $updateUser = User::where('user_id', $id)->firstOrFail();

        $admin = Auth::guard('admin')->user();
        $user = Auth::guard('user')->user();

        if (!$admin && $user?->user_id !== $updateUser->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized attempt to update profile of another user.',
            ], 403);
        }

        $request->validate([
            'titleId' => 'required|integer|exists:setup_titles,title_id',
            'firstName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'middleName' => ['nullable', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'lastName' => ['required', 'string', 'regex:/^[A-Za-z\s\'-]+$/', 'min:2', 'max:50'],
            'dateOfBirth' => 'nullable|date',
            'genderId' => 'nullable|integer|exists:setup_genders,gender_id',
            'emailAddress' => 'required|string|email|max:255|unique:users,email,' . $id . ',user_id',
            'mobileNumber' => ['required', 'string', 'unique:users,mobile_number,' . $id . ',user_id', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'homeAddress' => 'nullable|string|max:255',
            'lgaId' => 'nullable|integer|exists:setup_lgas,lga_id',
            'nin' => 'nullable|string|max:20',
            'statusId' => 'nullable|integer|exists:setup_statuses,status_id',
        ]);

        $admin = Auth::guard('admin')->user();
        $user = Auth::guard('user')->user();

        $beforeData = [];
        $afterData = [];

        DB::transaction(function () use ($request, $updateUser, $admin, $user, &$beforeData, &$afterData, $id) {
            $beforeData = Arr::only($updateUser->getOriginal(), [
                'user_id',
                'title_id',
                'first_name',
                'middle_name',
                'last_name',
                'date_of_birth',
                'gender_id',
                'email',
                'mobile_number',
                'home_address',
                'lga_id',
                'nin',
                'status_id',
                'updated_by',
                'updated_at',
            ]);

            $updateUser->update([
                'title_id' => $request->titleId,
                'first_name' => strtoupper($request->firstName),
                'middle_name' => $request->middleName ? strtoupper($request->middleName) : null,
                'last_name' => strtoupper($request->lastName),
                'date_of_birth' => $request->dateOfBirth,
                'gender_id' => $request->genderId,
                'email' => strtolower($request->emailAddress),
                'mobile_number' => $request->mobileNumber,
                'home_address' => $request->homeAddress ? strtoupper($request->homeAddress) : null,
                'lga_id' => $request->filled('lgaId')
                    ? (int) $request->lgaId
                    : null,
                'nin' => $request->nin,
                'status_id' => $admin ? ($request->statusId ?? $updateUser->status_id) : $updateUser->status_id,
                'updated_by' => $admin?->staff_id ?? $id,
            ]);

            $changes = $updateUser->getChanges();
            $afterData = Arr::only($changes, [
                'user_id',
                'title_id',
                'first_name',
                'middle_name',
                'last_name',
                'date_of_birth',
                'gender_id',
                'email',
                'mobile_number',
                'home_address',
                'lga_id',
                'nin',
                'status_id',
                'updated_by',
                'updated_at',
            ]);

            ClearCacheService::clearListCache('user_list');
            Cache::forget("user_profile_{$id}");
        });

        if ($admin) {
            $performedBy = $admin->staff_id;
            $userType    = 'Staff';
            $roleId      = $admin->roles?->pluck('id')->first();
        } elseif ($user) {
            // Bug fix: was '$member' (undefined variable) — corrected to '$user'
            $performedBy = $user->user_id;
            $userType    = 'User';
            $roleId      = null;
        } else {
            $performedBy = null;
            $userType    = 'System';
            $roleId      = null;
        }
        ActivityLogJob::dispatch(
            modelClass: ActivityLog::class,
            action: 'Update user',
            description: "user with ID: {$id} was updated.",
            userType: $userType,
            performedBy: $performedBy,
            roleId: $roleId,
            metadata: [
                'before' => $beforeData,
                'after' => $afterData,
            ],
            deviceInfo: Config::requestDetails(),
        )->afterCommit();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
        ], 200);
    }
}

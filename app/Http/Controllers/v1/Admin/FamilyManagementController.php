<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\AddFamilyMemberRequest;
use App\Http\Requests\Family\InviteFamilyMemberRequest;
use App\Http\Requests\Family\StoreFamilyRequest;
use App\Http\Requests\Family\UpdateFamilyMemberRoleRequest;
use App\Http\Requests\Family\UpdateFamilyRequest;
use App\Http\Resources\Family\FamilyInvitationResource;
use App\Http\Resources\Family\FamilyMemberResource;
use App\Http\Resources\Family\FamilyResource;
use App\Models\Family\Family;
use App\Models\Family\FamilyInvitation;
use App\Models\Family\FamilyMember;
use App\Models\User\User;
use App\Services\Family\FamilyMemberService;
use App\Services\Family\FamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FamilyManagementController extends Controller
{
    public function __construct(
        protected FamilyService $familyService,
        protected FamilyMemberService $familyMemberService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Family::class);

        $query = Family::with(['owner', 'members.user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('family_code', 'like', "%{$search}%");
            });
        }

        $families = $query->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Admin families retrieved successfully.',
            'data' => FamilyResource::collection($families->items()),
            'pagination' => [
                'current_page' => $families->currentPage(),
                'last_page' => $families->lastPage(),
                'per_page' => $families->perPage(),
                'total' => $families->total(),
            ],
        ], 200);
    }

    public function show(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        return response()->json([
            'success' => true,
            'message' => 'Family details retrieved successfully.',
            'data' => new FamilyResource($family->load(['owner', 'members.user'])),
        ], 200);
    }

    public function store(StoreFamilyRequest $request): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('create', Family::class);

        $owner = User::findOrFail($request->owner_user_id ?? $request->validated()['owner_user_id']);

        $family = $this->familyService->createFamily($owner, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Family created successfully by admin.',
            'data' => new FamilyResource($family->load(['owner', 'members'])),
        ], 201);
    }

    public function update(UpdateFamilyRequest $request, Family $family): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('update', $family);

        $updated = $this->familyService->updateFamily($staff, $family, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Family updated successfully.',
            'data' => new FamilyResource($updated->load(['owner', 'members'])),
        ], 200);
    }

    public function members(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $members = FamilyMember::with('user')
            ->where('family_id', $family->family_id)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Family members retrieved successfully.',
            'data' => FamilyMemberResource::collection($members),
        ], 200);
    }

    public function addMember(AddFamilyMemberRequest $request, Family $family): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('manageMembers', $family);

        $targetUser = User::where('user_id', $request->userId)
            ->orWhere('email', $request->userId)
            ->firstOrFail();

        $member = $this->familyMemberService->addMember(
            actor: $staff,
            family: $family,
            user: $targetUser,
            role: $request->role ?? 'member',
            relationship: $request->relationship ?? 'member'
        );

        return response()->json([
            'success' => true,
            'message' => 'Family member added by admin successfully.',
            'data' => new FamilyMemberResource($member->load('user')),
        ], 201);
    }

    public function removeMember(Request $request, Family $family, string $userId): JsonResponse
    {
        $staff = $request->user();

        $member = FamilyMember::where('family_id', $family->family_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $this->authorize('remove', $member);

        $this->familyMemberService->removeMember($staff, $member);

        return response()->json([
            'success' => true,
            'message' => 'Family member removed by admin successfully.',
        ], 200);
    }

    public function changeRole(UpdateFamilyMemberRoleRequest $request, Family $family, string $userId): JsonResponse
    {
        $staff = $request->user();

        $member = FamilyMember::where('family_id', $family->family_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $this->authorize('update', $member);

        $updatedMember = $this->familyMemberService->changeRole($staff, $member, $request->validated()['role']);

        return response()->json([
            'success' => true,
            'message' => 'Family member role updated by admin successfully.',
            'data' => new FamilyMemberResource($updatedMember->load('user')),
        ], 200);
    }

    public function invite(InviteFamilyMemberRequest $request, Family $family): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('manageMembers', $family);

        $invitation = FamilyInvitation::create([
            'family_id' => $family->family_id,
            'email' => strtolower($request->email),
            'role' => $request->role ?? 'member',
            'token' => Str::random(40),
            'invited_by' => $staff->staff_id,
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Family invitation created by admin successfully.',
            'data' => new FamilyInvitationResource($invitation),
        ], 201);
    }
}

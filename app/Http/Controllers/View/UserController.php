<?php

namespace App\Http\Controllers\View;

use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Models\MessageGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Application\Services\UserService;

class UserController extends Controller
{
    public function __construct(protected UserService $userService) {}


    /**
     * Create form
     */
    public function create()
    {
        $user = Auth::user();

        if ($user->role->name == 'superadmin') {
            $roles = Role::all();
            $departments = Department::all();
        } elseif ($user->role->name == 'admin') {
            $roles = Role::where('name', '!=', 'superadmin')->get();
            $departments = Department::where('id', $user->department_id)->get();
        }

        return view('user.create', compact('roles', 'departments'));
    }

    /**
     * Store user
     */
    public function store(UserStoreRequest $request)
    {
        $authUser = $request->user();
        $data = $request->validated();

        $user = $this->userService->store($data, $authUser);

        // request ichidagi department_id asosida redirect
        $departmentId = $request->input('department_id');
        if ($request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Foydalanuvchi qo‘shildi'
        ]);
    }
        return redirect()->route('departments.show', $departmentId)
            ->with('success', 'User created successfully');
    }


    /**
     * Show user
     */
    public function show(Request $request, $id)
{
    $user = User::with([
        'phones:id,user_id,phone,is_active',
        'phones.ban',
        'ban'
    ])->findOrFail($id);

    /** ---------------- PHONES ---------------- */
    $phonesCount = $user->phones->count();
    $activePhonesCount = $user->phones->where('is_active', 1)->count();

    /** ------------- MESSAGE GROUPS (searchable) ----------- */
    $search = $request->input('q');

    $phoneIdsQuery = function ($q) use ($user) {
        $q->select('id')
          ->from('user_phones')
          ->where('user_id', $user->id);
    };

    $messageGroups = MessageGroup::whereIn('user_phone_id', $phoneIdsQuery)
        ->when($search, function ($q) use ($search) {
            $q->whereExists(function ($sub) use ($search) {
                $sub->selectRaw(1)
                    ->from('telegram_messages')
                    ->whereColumn('telegram_messages.message_group_id', 'message_groups.id')
                    ->where('telegram_messages.message_text', 'like', "%{$search}%");
            });
        })
        ->orderByDesc('id')
        ->paginate(10, ['*'], 'groups_page');

    $groupIds = $messageGroups->pluck('id')->toArray();

    /** -------- TEXT STATS PER GROUP ----------- */
    $textStats = DB::table('telegram_messages')
        ->whereIn('message_group_id', $groupIds)
        ->select(
            'message_group_id',
            DB::raw('COUNT(*) as total_messages'),
            DB::raw('COUNT(DISTINCT message_text) as distinct_texts'),
            DB::raw('MIN(message_text) as sample_text'),
            DB::raw('MIN(send_at) as started_at'),
            DB::raw('MAX(send_at) as ended_at')
        )
        ->groupBy('message_group_id')
        ->get()
        ->keyBy('message_group_id');

    /** -------- PEER + STATUS COUNTS ----------- */
    $peerStatusRaw = DB::table('telegram_messages')
        ->whereIn('message_group_id', $groupIds)
        ->whereIn('status', ['pending', 'scheduled', 'sent', 'canceled', 'failed'])
        ->select(
            'message_group_id',
            'peer',
            'status',
            DB::raw('COUNT(*) as cnt')
        )
        ->groupBy('message_group_id', 'peer', 'status')
        ->get();

    $peerStatusByGroup = [];
    $groupTotals = [];

    foreach ($peerStatusRaw as $row) {
        $gid = $row->message_group_id;
        $peer = $row->peer;
        $status = $row->status;

        $peerStatusByGroup[$gid][$peer][$status] = $row->cnt;
        $groupTotals[$gid][$status] = ($groupTotals[$gid][$status] ?? 0) + $row->cnt;
    }

    /** ------------- TOTAL COUNTS -------------- */
    $totals = DB::table('message_groups')
        ->whereIn('user_phone_id', $phoneIdsQuery)
        ->selectRaw('COUNT(*) as groups_count')
        ->selectRaw('(
            SELECT COUNT(*) FROM telegram_messages
            WHERE telegram_messages.message_group_id IN (
                SELECT id FROM message_groups
                WHERE user_phone_id IN (SELECT id FROM user_phones WHERE user_id = ?)
            )
        ) as messages_count', [$user->id])
        ->first();

    return view('user.show', compact(
        'user',
        'phonesCount',
        'activePhonesCount',
        'messageGroups',
        'textStats',
        'peerStatusByGroup',
        'groupTotals',
        'search',
        'totals'
    ));
}



    /**
     * Edit form
     */
    public function edit(User $user)
    {
        $user = $this->userService->show($user);
        $roles = Role::all();

        return view('user.edit', compact('user', 'roles'));
    }

    /**
     * Update user
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $authUser = $request->user();
        $data = $request->validated();

        $this->userService->update($user, $data, $authUser);
        $departmentId = $user->department_id;
        return redirect()
            ->route('departments.show', $departmentId)
            ->with('success', 'User updated successfully');
    }

    /**
     * Delete user
     */
    public function destroy(User $user, Request $request)
{
    $authUser = $request->user();

    $this->userService->delete($user, $authUser);

    // Agar JS/Fetch yoki API chaqirayotgan bo'lsa - JSON qaytaramiz
    if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
            'department_id' => $user->department_id, 
        ], 200);
    }

    // Oddiy browser form submit bo'lsa
    return redirect()
        ->route('departments.index')
        ->with('success', 'User deleted successfully');
}

}

<?php

namespace App\Http\Controllers\View\Admin;

use App\Models\User;
use App\Models\UserPhone;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\TelegramAuthJob;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Jobs\VerifyPhoneWithUserJob;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Application\Services\LimitService;
use Illuminate\Validation\ValidationException;
use App\Application\Services\TelegramAuthService;

class UserController extends Controller
{
    public function __construct(protected LimitService $limit,protected TelegramAuthService $authService)
    {
    }
    public function show(Request $request, $id)
{
    $user = User::with([
        'avatar',
        'phones.messageGroups.messages',
        'ban',
        'role',
        'department',
    ])->findOrFail($id);

    $department = $user->department;

    // ✅ OPERATIONS = messageGroups
    $operationsCount = $user->phones
        ->pluck('messageGroups')
        ->flatten()
        ->count();

    // ✅ MESSAGES
    $messagesCount = $user->phones
        ->pluck('messageGroups')
        ->flatten()
        ->pluck('messages')
        ->flatten()
        ->count();

    return view('admin.users.show', compact(
        'user',
        'department',
        'operationsCount',
        'messagesCount'
    ));
}


    public function update(Request $request, $id)
    {
        $user = User::with('avatar')->findOrFail($id);

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['nullable','email','max:255', Rule::unique('users')->ignore($user->id)],
            'telegram_id' => ['nullable','string','max:255'],
            // password no confirm now:
            'password' => ['nullable','min:6'],
            'avatar' => ['nullable','image','max:2048'],
            'remove_avatar' => ['nullable','boolean'],
            'active_phone_id' => ['nullable','integer','exists:user_phones,id'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'] ?? $user->email;
        $user->telegram_id = $data['telegram_id'] ?? $user->telegram_id;

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        // avatar upload
        if ($request->hasFile('avatar')) {
            $f = $request->file('avatar');
            $path = $f->store('avatars', 'public');

            try {
                $old = $user->avatar;
                if ($old && $old->path) Storage::disk('public')->delete($old->path);
            } catch (\Throwable $e) {}

            $user->avatar()->updateOrCreate([], ['path' => $path]);
        } elseif ($request->boolean('remove_avatar')) {
            try {
                $old = $user->avatar;
                if ($old && $old->path) Storage::disk('public')->delete($old->path);
                $user->avatar()->delete();
            } catch (\Throwable $e) {}
        }

        $user->save();

        // set active phone if requested
        if (!empty($data['active_phone_id'])) {
            DB::transaction(function() use ($user, $data) {
                DB::table('user_phones')->where('user_id', $user->id)->update(['is_active' => 0]);
                DB::table('user_phones')->where('id', $data['active_phone_id'])->update(['is_active' => 1]);
            });
        }

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', __('messages.users.user_updated') ?? 'User updated');
    }

    // add new phone
    public function addPhone(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'phone' => ['required','string','max:50'],
        ]);

        $phone = new UserPhone();
        $phone->user_id = $user->id;
        $phone->phone = $data['phone'];
        $phone->is_active = 0;
        $phone->save();

        return redirect()->route('admin.users.show', $user->id)->with('success', __('messages.users.phone_added') ?? 'Phone added');
    }

    // delete phone
    public function deletePhone(Request $request, $id, $phoneId)
    {
        $user = User::findOrFail($id);
        $phone = UserPhone::where('user_id', $user->id)->where('id', $phoneId)->firstOrFail();

        // if it's active, try to unset or set another phone active
        if ($phone->is_active) {
            // set another phone active (if exists)
            $other = UserPhone::where('user_id', $user->id)->where('id', '<>', $phone->id)->first();
            if ($other) {
                $other->is_active = 1;
                $other->save();
            }
        }

        $phone->delete();

        return redirect()->route('admin.users.show', $user->id)->with('success', __('messages.users.phone_deleted') ?? 'Phone deleted');
    }
    public function canUsePhone(string $phone): bool
    {
        $userPhone = UserPhone::where('phone', $phone)->whereNotNull('telegram_user_id')->first();
        if (!$userPhone || !$userPhone->telegram_user_id) {
            return true;
        }
        $exists = User::where('telegram_id', $userPhone->telegram_user_id)->exists();

        return ! $exists;
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        try {
            $old = $user->avatar;
            if ($old && $old->path) Storage::disk('public')->delete($old->path);
            $user->avatar()->delete();
        } catch (\Throwable $e) {}

        $departmentId = $user->department_id;
        $user->delete();

        return redirect()->back()->with('success', __('messages.users.user_deleted') ?? 'User deleted');
    }

    public function sendPhone(Request $request)
    {
        
        $request->validate(['phone' => 'required|string']);

    try {
        $phone = preg_replace('/[^0-9+]/', '', $request->input('phone', ''));
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        $user = $this->resolveUserFromRequest($request);

        if (!$this->canUsePhone($phone)) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.telegram.user_exists')
            ], 403);
        }

        if (!$this->limit->canCreateUser($user)) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.telegram.limit')
            ], 403);
        }

        $lockKey = "telegram_verify_lock_{$phone}_{$user->id}";
        $lockTtlSeconds = 60 * 10; 

        $started = false;
        if (Cache::add($lockKey, true, $lockTtlSeconds)) {
            TelegramAuthJob::dispatch($phone, $user->id)->onQueue('telegram');
            $started = true;
        }

        return response()->json([
            'status' => $started ? 'sms_sent' : 'locked',
            'message' => $started
                ? __('messages.telegram.sms_sent')
                : (__('messages.telegram.already_in_progress') ?? 'Verification already in progress'),
            'user_id' => $user->id,
        ], 200);

    } catch (ValidationException $e) {
        return response()->json(['status' => 'error', 'errors' => $e->errors()], 422);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
    }
    
    public function storeUserWithTelegram(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string',
            'department_id' => 'nullable|integer|exists:departments,id',
        ]);
        

        $departmentId = $data['department_id'] ?? optional($request->user())->department_id ?? null;

        $existsInDepartment = UserPhone::where('phone', $data['phone'])
            ->whereHas('user', function ($q) use ($departmentId) {
                if ($departmentId === null) {
                    $q->whereNull('department_id');
                } else {
                    $q->where('department_id', $departmentId);
                }
            })
            ->exists();

        if ($existsInDepartment) {
            return redirect()->back()->with('error', __('messages.telegram.user_exists'));
        }

        VerifyPhoneWithUserJob::dispatch($data['phone'], $data['code'], null, $departmentId)
            ->onQueue('telegram');
        $token = (string) Str::uuid();
        Cache::put("notif:{$token}", [
            'message' => __('messages.telegram.started'),
            'type'    => 'success',
        ], now()->addMinutes(10));

        return redirect()->route('departments.users', $departmentId)->with('success', __('messages.telegram.started'));
    }
    public function newTelegramUsers()
    {
        $user=request()->user();
        $department=$user->department;
        return view('admin.telegram.telegram-login', compact('department'));
    }
        
    






























    protected function resolveUserFromRequest(Request $request): User
    {
        $userId = $request->input('user_id') ?? $request->query('user_id');
        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                abort(404, 'User topilmadi');
            }
            return $user;
        }

        $user = $request->user();
        if (! $user) {
            abort(401, 'Login talab qilinadi');
        }
        return $user;
    }
}

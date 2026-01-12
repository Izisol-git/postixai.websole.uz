<?php

namespace App\Http\Controllers\View;

use App\Models\User;
use App\Models\MessageGroup;
use Illuminate\Http\Request;
use App\Jobs\CleanupScheduledJob;
use App\Jobs\RefreshGroupStatusJob;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use App\Application\Services\TelegramAuthService;

class TelegramController extends Controller
{
    public function __construct(protected TelegramAuthService $authService) {
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

    public function showLoginForm(Request $request)
    {
        $userId = $request->query('user_id');
        return view('telegram.login', compact('userId'));
    }

    public function sendPhone(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        try {
            $user = $this->resolveUserFromRequest($request);

            $this->authService->login($user, $request->phone);
            sleep(2);
            return response()->json([
                'status' => 'sms_sent',
                'message' => __('messages.telegram.sms_sent'),
                'user_id' => $user->id,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code'  => 'required|string',
        ]);

        try {
            $user = $this->resolveUserFromRequest($request);
            sleep(3);

            $this->authService->completedLogin([
                'user' => $user,
                'phone' => $request->phone,
                'code' => $request->code,
            ]);


            $redirect = $user->department_id
                ? route('departments.show', $user->department_id)
                : url()->previous();

            return response()->json([
                'status' => 'verified',
                'message' => "Telegram Tasdiqlash jarayoni boshlandi {$request->phone}!",
                'redirect' => $redirect,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
    public function cancel(MessageGroup $group): RedirectResponse
    {
        // (ixtiyoriy) permission check
        // abort_if(auth()->user()->role?->name !== 'superadmin', 403);

        // Job dispatch
        CleanupScheduledJob::dispatch($group->id)
            ->onQueue('telegram');

        return redirect()
            ->back()
            ->with('success', "Operatsiya #{$group->id} bekor qilish jarayoniga yuborildi.");
    }
    /**
     * Operatsiyani yangilash (REFRESH)
     */
    public function refresh(MessageGroup $group): RedirectResponse
    {
        RefreshGroupStatusJob::dispatch($group->id)
            ->onQueue('telegram');

        return redirect()
            ->back()
            ->with('success', "Operatsiya #{$group->id} yangilash jarayoniga yuborildi.");
    }
    public function logout(Request $request): RedirectResponse
    {   
        $user = $this->resolveUserFromRequest($request);

        $this->authService->logout($user, $request->input('phone'));
        return redirect()
            ->back()
            ->with('success', 'Siz muvaffaqiyatli Telegramdan chiqdingiz.');
    }

}

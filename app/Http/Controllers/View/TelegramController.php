<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use App\Application\Services\TelegramAuthService;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TelegramController extends Controller
{
    public function __construct(protected TelegramAuthService $authService) {}

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
                'message' => "SMS yuborildi {$user->name} uchun!",
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
}

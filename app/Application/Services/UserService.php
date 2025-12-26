<?php

namespace App\Application\Services;

use App\Models\Role;
use App\Models\User;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function index(array $filters = [], int $perPage = 10)
    {
        $query = User::with(['role', 'department']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->paginate($perPage);
    }

    public function store(array $data, $authUser)
    {   
        if ($authUser->role->name === 'admin') {
            $data['department_id'] = $authUser->department_id;
            $userRole = Role::where('name', 'user')->first();
            $data['role_id'] = $userRole->id;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = User::create($data);
        return $user->load(['role', 'department']);
    }

    public function show(User $user)
    {
        return $user->load(['role', 'department']);
    }

    public function update(User $user, array $data, $authUser)
    {
        $this->ensureCanModify($authUser, $user);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if ($authUser->role->name === 'admin') {
            $data['department_id'] = $authUser->department_id;
            $userRole = Role::where('name', 'user')->first();
            $data['role_id'] = $userRole->id;
        }

        $user->update($data);
        return $user->load(['role', 'department']);
    }

    public function delete(User $user, $authUser)
    {
        $this->ensureCanModify($authUser, $user);
        $user->delete();
        return true;
    }


    private function ensureCanModify(User $authUser, User $targetUser): void
    {
        // Superadmin har doim ruxsatli
        if ($authUser->role->name === 'superadmin') {
            return;
        }

        // Admin superadminni o‘zgartira olmaydi
        if ($targetUser->role?->name === 'superadmin') {
            throw new ApiException('You cannot modify superadmin users', 403);
        }

        // Admin faqat o‘z departmentidagi userlarni o‘zgartira oladi
        if ($authUser->role->name === 'admin' && $authUser->department_id !== $targetUser->department_id) {
            throw new ApiException('You can only modify users in your department', 403);
        }
    }
}

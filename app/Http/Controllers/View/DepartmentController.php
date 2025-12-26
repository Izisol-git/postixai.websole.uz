<?php

namespace App\Http\Controllers\View;

use Carbon\Carbon;
use App\Models\UserPhone;
use App\Models\Department;
use App\Models\MessageGroup;
use Illuminate\Http\Request;
use App\Models\TelegramMessage;
use Illuminate\Support\FacadesDB;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        // Range: all, year, month, week, day
        $range = $request->get('range', 'all');

        $since = null;
        if ($range === 'day') {
            $since = Carbon::now()->subDay();
        } elseif ($range === 'week') {
            $since = Carbon::now()->subWeek();
        } elseif ($range === 'month') {
            $since = Carbon::now()->subMonth();
        } elseif ($range === 'year') {
            $since = Carbon::now()->subYear();
        }
        $sinceStr = $since ? $since->toDateTimeString() : null;

        // Per-department aggregate using subqueries (memory-friendly)
        $selects = [
            'departments.id',
            'departments.name',
            DB::raw(
                $sinceStr
                    ? "(SELECT COUNT(*) FROM users WHERE users.department_id = departments.id AND users.created_at >= '{$sinceStr}') AS users_count"
                    : "(SELECT COUNT(*) FROM users WHERE users.department_id = departments.id) AS users_count"
            ),
            DB::raw(
                $sinceStr
                    ? "(SELECT COUNT(*) FROM user_phones up JOIN users u ON u.id = up.user_id WHERE u.department_id = departments.id AND up.is_active = 1 AND up.created_at >= '{$sinceStr}') AS active_phones_count"
                    : "(SELECT COUNT(*) FROM user_phones up JOIN users u ON u.id = up.user_id WHERE u.department_id = departments.id AND up.is_active = 1) AS active_phones_count"
            ),
            DB::raw(
                $sinceStr
                    ? "(SELECT COUNT(*) FROM message_groups mg JOIN user_phones up2 ON up2.id = mg.user_phone_id JOIN users u2 ON u2.id = up2.user_id WHERE u2.department_id = departments.id AND mg.created_at >= '{$sinceStr}') AS message_groups_count"
                    : "(SELECT COUNT(*) FROM message_groups mg JOIN user_phones up2 ON up2.id = mg.user_phone_id JOIN users u2 ON u2.id = up2.user_id WHERE u2.department_id = departments.id) AS message_groups_count"
            ),
            DB::raw(
                $sinceStr
                    ? "(SELECT COUNT(*) FROM telegram_messages tm JOIN message_groups mg2 ON mg2.id = tm.message_group_id JOIN user_phones up3 ON up3.id = mg2.user_phone_id JOIN users u3 ON u3.id = up3.user_id WHERE u3.department_id = departments.id AND tm.send_at >= '{$sinceStr}') AS telegram_messages_count"
                    : "(SELECT COUNT(*) FROM telegram_messages tm JOIN message_groups mg2 ON mg2.id = tm.message_group_id JOIN user_phones up3 ON up3.id = mg2.user_phone_id JOIN users u3 ON u3.id = up3.user_id WHERE u3.department_id = departments.id) AS telegram_messages_count"
            ),
        ];

        $deptStats = DB::table('departments')
            ->select($selects)
            ->orderBy('departments.name')
            ->get();

        // Build charts datasets
        $chartUsers = $deptStats->pluck('users_count', 'name');
        $chartPhones = $deptStats->pluck('active_phones_count', 'name');
        $chartGroups = $deptStats->pluck('message_groups_count', 'name');
        $chartMessages = $deptStats->pluck('telegram_messages_count', 'name');

        $totals = [
            'users' => $chartUsers->sum(),
            'phones' => $chartPhones->sum(),
            'groups' => $chartGroups->sum(),
            'messages' => $chartMessages->sum(),
        ];

        $n = max(1, $deptStats->count());
        $colors = [];
        for ($i = 0; $i < $n; $i++) {
            $h = intval(($i * 360) / $n);
            $s = 70;
            $l = 50;
            $colors[] = "hsl({$h}, {$s}%, {$l}%)";
        }

        return view('dashboard', compact(
            'deptStats',
            'chartUsers',
            'chartPhones',
            'chartGroups',
            'chartMessages',
            'totals',
            'colors',
            'range'
        ));
    }
    public function create()
    {
        return view('departments.create');
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
        ]);
        Department::create($data);
        return redirect()->route('departments.index');
    }
    public function edit(Department $department)
    {
        return view('departments.edit', compact('department'));
    }
    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
        ]);

        $department->update($data);

        return redirect()->route('departments.index');
    }
    public function destroy(Department $department)
    {
        dd(123);
        $department->delete();
        return redirect()->route('departments.index');
    }
    public function show(Request $request, $id)
{
    $department = Department::findOrFail($id);

    /** ---------------- USERS ---------------- */
    $users = $department->users()
        ->select('id', 'name', 'telegram_id', 'email', 'department_id')
        ->with(['phones:id,user_id,phone,is_active','phones.ban', 'ban'])
        ->get();

    $usersCount = $users->count();
    $activePhonesCount = $users->sum(fn($u) => $u->phones->where('is_active', 1)->count());

    $search = $request->input('q');

    /** ------------- MESSAGE GROUPS (for department, searchable) ---------- */
    $messageGroups = MessageGroup::whereIn('user_phone_id', function ($q) use ($department) {
            $q->select('user_phones.id')
                ->from('user_phones')
                ->join('users', 'users.id', '=', 'user_phones.user_id')
                ->where('users.department_id', $department->id);
        })
        ->when($search, function ($q) use ($search, $department) {
            // restrict to groups that have a telegram_message whose text matches
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

    /** -------- TEXT STATS PER GROUP ---------- */
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

    /** -------- PEER + STATUS COUNTS ---------- */
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
        ->whereIn('user_phone_id', function ($q) use ($department) {
            $q->select('user_phones.id')
                ->from('user_phones')
                ->join('users', 'users.id', '=', 'user_phones.user_id')
                ->where('users.department_id', $department->id);
        })
        ->selectRaw('COUNT(*) as groups_count')
        ->selectRaw('(SELECT COUNT(*) FROM telegram_messages WHERE telegram_messages.message_group_id IN (SELECT id FROM message_groups WHERE message_groups.user_phone_id IN (SELECT user_phones.id FROM user_phones JOIN users ON users.id = user_phones.user_id WHERE users.department_id = ?))) as messages_count', [$department->id])
        ->first();

    $messageGroupsTotal = $totals->groups_count;
    $telegramMessagesTotal = $totals->messages_count;

    /** --------- RECENT (MULTI TEXT) ---------- */
    $recentMessagesByGroup = [];
    $multiTextGroupIds = [];

    foreach ($textStats as $gid => $stat) {
        if ($stat->distinct_texts > 1) {
            $multiTextGroupIds[] = $gid;
        }
    }

    if (!empty($multiTextGroupIds)) {
        $recentRows = DB::table('telegram_messages')
            ->whereIn('message_group_id', $multiTextGroupIds)
            ->orderByDesc('sent_at')
            ->get()
            ->groupBy('message_group_id');

        foreach ($recentRows as $gid => $rows) {
            $recentMessagesByGroup[$gid] = $rows->take(10);
        }
    }

    return view('departments.show', compact(
        'department',
        'users',
        'usersCount',
        'activePhonesCount',
        'messageGroups',
        'textStats',
        'peerStatusByGroup',
        'groupTotals',
        'recentMessagesByGroup',
        'messageGroupsTotal',
        'telegramMessagesTotal',
        'search'
    ));
}

}

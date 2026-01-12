<?php

namespace App\Http\Controllers\View\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Department;
use App\Models\MessageGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class MainController extends Controller
{
    public function index(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        // range: all | year | month | day
        $range = $request->get('range', 'year');

        /** FAST COUNTS */
        $usersCount = $department->users()->count();

        $activePhonesCount = DB::table('user_phones')
            ->join('users', 'users.id', '=', 'user_phones.user_id')
            ->where('users.department_id', $department->id)
            ->where('user_phones.is_active', 1)
            ->count();

        $messageGroupsCount = DB::table('message_groups')
            ->whereIn('user_phone_id', function ($q) use ($department) {
                $q->select('user_phones.id')
                    ->from('user_phones')
                    ->join('users', 'users.id', '=', 'user_phones.user_id')
                    ->where('users.department_id', $department->id);
            })
            ->count();

        $telegramMessagesCount = DB::table('telegram_messages')
            ->whereIn('message_group_id', function ($q) use ($department) {
                $q->select('message_groups.id')
                    ->from('message_groups')
                    ->join('user_phones', 'user_phones.id', '=', 'message_groups.user_phone_id')
                    ->join('users', 'users.id', '=', 'user_phones.user_id')
                    ->where('users.department_id', $department->id);
            })
            ->count();

        /** LAST ACTIVE USERS */
        $lastActiveUsers = User::with('avatar')
            ->where('department_id', $department->id)
            ->whereHas('phones.messageGroups.messages')
            ->select('users.id', 'users.name')
            ->selectRaw('MAX(telegram_messages.sent_at) as last_active')
            ->selectRaw('COUNT(telegram_messages.id) as ops_count')
            ->join('user_phones', 'user_phones.user_id', '=', 'users.id')
            ->join('message_groups', 'message_groups.user_phone_id', '=', 'user_phones.id')
            ->join('telegram_messages', 'telegram_messages.message_group_id', '=', 'message_groups.id')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('last_active')
            ->limit(5)
            ->get();

        /** Determine start / interval / SQL format by range (including all) */
        $now = Carbon::now();
        if ($range === 'day') {
            $start = $now->copy()->subDay()->startOfHour();
            $intervalSpec = 'PT1H';
            $sqlFormat = '%Y-%m-%d %H:00';
            $labelType = 'hour';
        } elseif ($range === 'month') {
            $start = $now->copy()->subMonth()->startOfDay();
            $intervalSpec = 'P1D';
            $sqlFormat = '%Y-%m-%d';
            $labelType = 'day';
        } elseif ($range === 'all') {
            // earliest message in department
            $minDate = DB::table('telegram_messages')
                ->join('message_groups', 'message_groups.id', '=', 'telegram_messages.message_group_id')
                ->join('user_phones', 'user_phones.id', '=', 'message_groups.user_phone_id')
                ->join('users', 'users.id', '=', 'user_phones.user_id')
                ->where('users.department_id', $department->id)
                ->min('telegram_messages.sent_at');

            if ($minDate) {
                $start = Carbon::parse($minDate)->startOfMonth();
            } else {
                $start = $now->copy()->subYear()->startOfMonth();
            }
            $intervalSpec = 'P1M';
            $sqlFormat = '%Y-%m';
            $labelType = 'month';
        } else { // year
            $start = $now->copy()->subYear()->startOfMonth();
            $intervalSpec = 'P1M';
            $sqlFormat = '%Y-%m';
            $labelType = 'month';
        }

        $end = $now->copy();
        $periodEnd = $end->copy()->addDay();

        /** MESSAGES PER PERIOD (line) */
        $rawPerPeriod = DB::table('telegram_messages')
            ->join('message_groups', 'message_groups.id', '=', 'telegram_messages.message_group_id')
            ->join('user_phones', 'user_phones.id', '=', 'message_groups.user_phone_id')
            ->join('users', 'users.id', '=', 'user_phones.user_id')
            ->where('users.department_id', $department->id)
            ->where('telegram_messages.sent_at', '>=', $start)
            ->select(DB::raw("DATE_FORMAT(telegram_messages.sent_at, '{$sqlFormat}') as period"), DB::raw("COUNT(*) as cnt"))
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('cnt', 'period');

        // build labels sequence
        $iterator = new \DatePeriod($start, new \DateInterval($intervalSpec), $periodEnd);
        $messagesPerDayLabels = [];
        $messagesPerDayValues = [];
        foreach ($iterator as $dt) {
            if ($labelType === 'month') {
                $key = $dt->format('Y-m');
                $label = $dt->format('M Y');
            } elseif ($labelType === 'day') {
                $key = $dt->format('Y-m-d');
                $label = $dt->format('Y-m-d');
            } else {
                $key = $dt->format('Y-m-d H:00');
                $label = $dt->format('d H:00');
            }
            $messagesPerDayLabels[] = $label;
            $messagesPerDayValues[] = isset($rawPerPeriod[$key]) ? (int)$rawPerPeriod[$key] : 0;
        }

        /** USERS BY OPERATIONS (top N) - doughnut */
        $topN = 10;
        $usersOps = DB::table('users')
            ->join('user_phones', 'user_phones.user_id', '=', 'users.id')
            ->join('message_groups', 'message_groups.user_phone_id', '=', 'user_phones.id')
            ->join('telegram_messages', 'telegram_messages.message_group_id', '=', 'message_groups.id')
            ->where('users.department_id', $department->id)
            ->where('telegram_messages.sent_at', '>=', $start)
            ->select('users.id', 'users.name', DB::raw('COUNT(telegram_messages.id) as cnt'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('cnt')
            ->limit($topN)
            ->get();

        $usersOpsLabels = $usersOps->pluck('name')->toArray();
        $usersOpsValues = $usersOps->pluck('cnt')->map(fn($v) => (int)$v)->toArray();
        $userIds = $usersOps->pluck('id')->toArray();

        /** GROUPED BAR: phones per user (for top users) */
        // get phones for top users
        $phones = DB::table('user_phones')
            ->whereIn('user_id', $userIds)
            ->select('id', 'user_id', 'phone', 'is_active')
            ->orderBy('user_id')
            ->get();

        // map user_id => [phone objects]
        $phonesByUser = [];
        $phoneIds = [];
        foreach ($phones as $p) {
            $phonesByUser[$p->user_id][] = $p;
            $phoneIds[] = $p->id;
        }

        // If there are no phones, grouped chart will be empty
        $phoneLabels = []; // unique phone labels (we will use masked number)
        $phoneIndexMap = []; // phone_id => index in labels

        foreach ($phones as $p) {
            $label = $p->phone;
            // mask number a bit for display: show last 4
            $masked = preg_replace('/\d(?=\d{4})/', '*', $label);
            $phoneLabels[] = $masked;
            $phoneIndexMap[$p->id] = count($phoneLabels) - 1;
        }

        // initialize matrix: rows = phoneLabels, cols = users (in usersOps order)
        $phoneCount = count($phoneLabels);
        $userCount = count($userIds);
        $matrix = array_fill(0, $phoneCount, array_fill(0, $userCount, 0));

        if (!empty($phoneIds)) {
            // get counts per phone (no period dimension) within range
            $rawPhoneCounts = DB::table('telegram_messages')
                ->join('message_groups', 'message_groups.id', '=', 'telegram_messages.message_group_id')
                ->join('user_phones', 'user_phones.id', '=', 'message_groups.user_phone_id')
                ->join('users', 'users.id', '=', 'user_phones.user_id')
                ->where('users.department_id', $department->id)
                ->whereIn('user_phones.id', $phoneIds)
                ->where('telegram_messages.sent_at', '>=', $start)
                ->select('user_phones.id as phone_id', 'users.id as user_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('user_phones.id', 'users.id')
                ->get();

            // map user id to its position in userIds ordering
            $userPos = array_flip($userIds);

            foreach ($rawPhoneCounts as $r) {
                $pId = $r->phone_id;
                $uId = $r->user_id;
                $cnt = (int)$r->cnt;

                if (isset($phoneIndexMap[$pId]) && isset($userPos[$uId])) {
                    $pi = $phoneIndexMap[$pId];
                    $ui = $userPos[$uId];
                    $matrix[$pi][$ui] = $cnt;
                }
            }
        }

        // Prepare datasets: for each phone (row in matrix) create dataset (data per user)
        $phoneDatasets = [];
        for ($i = 0; $i < $phoneCount; $i++) {
            $phoneDatasets[] = [
                'label' => $phoneLabels[$i],
                'data' => $matrix[$i],
            ];
        }

        // return view
        return view('departments.adminShow', compact(
            'department',
            'usersCount',
            'activePhonesCount',
            'messageGroupsCount',
            'telegramMessagesCount',
            'lastActiveUsers',
            'messagesPerDayLabels',
            'messagesPerDayValues',
            'usersOpsLabels',
            'usersOpsValues',
            'phoneLabels',
            'phoneDatasets',
            'userIds',
            'userCount',
            'phoneCount',
            'range'
        ));
    }
    public function users(Request $request, Department $department)
    {
        
        $q = $request->input('q');

        $usersQuery = User::with(['avatar', 'phones.ban', 'ban', 'role'])
            ->where('department_id', $department->id);

        if ($q) {
            $usersQuery->where(function ($w) use ($q) {
                $w->where('users.name', 'like', "%{$q}%")
                  ->orWhere('users.email', 'like', "%{$q}%")
                  ->orWhere('users.telegram_id', 'like', "%{$q}%");
            });
        }

        $users = $usersQuery->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('user.index', compact('department', 'users', 'q'));
    }
    public function operations(Request $request, Department $department)
    {
        // Only superadmin allowed in your app? replicate your check if needed.
        $user = $request->user();
        

        // auto-activate scheduled ban as in show()
        if ($department->ban && $department->ban->active == 0 && $department->ban->starts_at && $department->ban->starts_at < now()) {
            $department->ban->active = 1;
            $department->ban->save();
        }

        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status', null);
        $from = $request->get('from', null);
        $to = $request->get('to', null);

        // Base query: message_groups for department phones
        $base = MessageGroup::whereIn('user_phone_id', function ($qsub) use ($department) {
            $qsub->select('user_phones.id')
                ->from('user_phones')
                ->join('users', 'users.id', '=', 'user_phones.user_id')
                ->where('users.department_id', $department->id);
        });

        // Apply search: groups that have telegram_messages whose text matches
        if ($q !== '') {
            $base->whereExists(function ($sub) use ($q) {
                $sub->selectRaw(1)
                    ->from('telegram_messages')
                    ->whereColumn('telegram_messages.message_group_id', 'message_groups.id')
                    ->where('telegram_messages.message_text', 'like', "%{$q}%");
            });
        }

        // Apply status filter (status relates to message rows; we keep groups that have at least one message with that status)
        if ($status) {
            $base->whereExists(function ($sub) use ($status) {
                $sub->selectRaw(1)
                    ->from('telegram_messages')
                    ->whereColumn('telegram_messages.message_group_id', 'message_groups.id')
                    ->where('telegram_messages.status', $status);
            });
        }

        // Apply date range on telegram_messages.sent_at (groups that have messages in range)
        if ($from || $to) {
            $base->whereExists(function ($sub) use ($from, $to) {
                $sub->selectRaw(1)
                    ->from('telegram_messages')
                    ->whereColumn('telegram_messages.message_group_id', 'message_groups.id');

                if ($from) {
                    $sub->where('telegram_messages.sent_at', '>=', Carbon::parse($from)->startOfDay());
                }
                if ($to) {
                    $sub->where('telegram_messages.sent_at', '<=', Carbon::parse($to)->endOfDay());
                }
            });
        }

        $messageGroups = $base->orderByDesc('id')
            ->paginate(10, ['*'], 'groups_page')
            ->withQueryString();

        $groupIds = $messageGroups->pluck('id')->toArray();

        // TEXT STATS per group
        $textStats = collect();
        if (!empty($groupIds)) {
            $textStats = DB::table('telegram_messages')
                ->whereIn('message_group_id', $groupIds)
                ->select(
                    'message_group_id',
                    DB::raw('COUNT(*) as total_messages'),
                    DB::raw('COUNT(DISTINCT message_text) as distinct_texts'),
                    DB::raw('MIN(message_text) as sample_text'),
                    DB::raw('MIN(sent_at) as started_at'),
                    DB::raw('MAX(sent_at) as ended_at')
                )
                ->groupBy('message_group_id')
                ->get()
                ->keyBy('message_group_id');
        }

        // Peer + status counts: per group -> per peer -> status counts
        $peerStatusRaw = collect();
        if (!empty($groupIds)) {
            $peerStatusRaw = DB::table('telegram_messages')
                ->whereIn('message_group_id', $groupIds)
                ->whereIn('status', ['pending', 'scheduled', 'sent', 'canceled', 'failed'])
                ->select('message_group_id', 'peer', 'status', DB::raw('COUNT(*) as cnt'))
                ->groupBy('message_group_id', 'peer', 'status')
                ->get();
        }

        $peerStatusByGroup = [];
        $groupTotals = [];

        foreach ($peerStatusRaw as $row) {
            $gid = $row->message_group_id;
            $peer = $row->peer;
            $statusKey = $row->status;

            $peerStatusByGroup[$gid][$peer][$statusKey] = $row->cnt;
            $groupTotals[$gid][$statusKey] = ($groupTotals[$gid][$statusKey] ?? 0) + $row->cnt;
        }

        // TOTALS for header (groups & messages)
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

        $messageGroupsTotal = $totals->groups_count ?? 0;
        $telegramMessagesTotal = $totals->messages_count ?? 0;

        // Recent multi-text groups (sample messages)
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

        return view('operations.index', compact(
            'department',
            'messageGroups',
            'textStats',
            'peerStatusByGroup',
            'groupTotals',
            'recentMessagesByGroup',
            'messageGroupsTotal',
            'telegramMessagesTotal',
            'q',
            'status',
            'from',
            'to'
        ));
    }
}

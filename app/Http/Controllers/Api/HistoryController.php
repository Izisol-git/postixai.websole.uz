<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();

        $usersLimit  = 20;
        $phonesLimit = 5;
        $groupsLimit = 10;

        // Eager load bilan cheklash
        $departmentsQuery = Department::query();

        if ($authUser->role->name === 'admin') {
            $departmentsQuery->where('id', $authUser->department_id);
        } elseif ($authUser->role->name !== 'superadmin') {
            return $this->error('Forbidden', 403);
        }

        $departments = $departmentsQuery
            ->with(['users' => function($q) use ($usersLimit, $phonesLimit, $groupsLimit) {
                $q->take($usersLimit)->with(['phones' => function($q2) use ($phonesLimit, $groupsLimit) {
                    $q2->take($phonesLimit)->with(['messageGroups' => function($q3) use ($groupsLimit) {
                        $q3->take($groupsLimit)->withCount(['messages'])->with(['messages:id,message_group_id,peer,status']);
                    }]);
                }]);
            }])
            ->get();

        // Total statistics
        $totalMessages = 0;
        $totalByStatus = [];
        $totalByPeer = [];

        $departmentsData = [];

        foreach ($departments as $department) {
            $depMessagesCount = 0;
            $depStatusCount = [];
            $depPeerCount = [];

            $depData = [
                'id' => $department->id,
                'name' => $department->name,
                'users' => [],
            ];

            foreach ($department->users as $user) {
                $userMessagesCount = 0;
                $userStatusCount = [];
                $userPeerCount = [];

                $userData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phones' => [],
                ];

                foreach ($user->phones as $phone) {
                    $phoneData = [
                        'id' => $phone->id,
                        'number' => $phone->phone,
                        'message_groups' => [],
                    ];

                    foreach ($phone->messageGroups as $group) {
                        $messagesCountByStatus = $group->messages->groupBy('status')->map->count();
                        $messagesCountByPeer   = $group->messages->groupBy('peer')->map->count();

                        $groupData = [
                            'id' => $group->id,
                            'status' => $group->status,
                            'messages_count' => $group->messages_count,
                            'messages_count_by_status' => $messagesCountByStatus,
                            'messages_count_by_peer' => $messagesCountByPeer,
                        ];

                        // accumulate stats
                        $userMessagesCount += $group->messages_count;
                        foreach ($messagesCountByStatus as $status => $count) {
                            $userStatusCount[$status] = ($userStatusCount[$status] ?? 0) + $count;
                        }
                        foreach ($messagesCountByPeer as $peer => $count) {
                            $userPeerCount[$peer] = ($userPeerCount[$peer] ?? 0) + $count;
                        }

                        $phoneData['message_groups'][] = $groupData;
                    }

                    $userData['phones'][] = $phoneData;
                }

                $userData['total_statistics'] = [
                    'messages_count' => $userMessagesCount,
                    'messages_count_by_status' => $userStatusCount,
                    'messages_count_by_peer' => $userPeerCount,
                ];

                // accumulate department stats
                $depMessagesCount += $userMessagesCount;
                foreach ($userStatusCount as $status => $count) {
                    $depStatusCount[$status] = ($depStatusCount[$status] ?? 0) + $count;
                }
                foreach ($userPeerCount as $peer => $count) {
                    $depPeerCount[$peer] = ($depPeerCount[$peer] ?? 0) + $count;
                }

                $depData['users'][] = $userData;
            }

            $depData['total_statistics'] = [
                'messages_count' => $depMessagesCount,
                'messages_count_by_status' => $depStatusCount,
                'messages_count_by_peer' => $depPeerCount,
            ];

            // accumulate global totals
            $totalMessages += $depMessagesCount;
            foreach ($depStatusCount as $status => $count) {
                $totalByStatus[$status] = ($totalByStatus[$status] ?? 0) + $count;
            }
            

            $departmentsData[] = $depData;
        }

        $response = [
            'total_statistics' => [
                'messages_count' => $totalMessages,
                'messages_count_by_status' => $totalByStatus,
            ],
            'departments' => $departmentsData,
        ];

        return $this->success($response, 'Message statistics retrieved successfully');
    }
}

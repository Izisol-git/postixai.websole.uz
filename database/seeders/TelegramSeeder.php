<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserPhone;
use App\Models\MessageGroup;
use App\Models\TelegramMessage;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class TelegramSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Roles
        $userRole  = Role::firstOrCreate(['name' => 'user']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $superRole = Role::firstOrCreate(['name' => 'superadmin']);

        // 2 departments
        for ($d = 1; $d <= 2; $d++) {
            $department = Department::create([
                'name' => $faker->company,
            ]);

            // 10-20 users per department
            $usersCount = rand(10, 20);
            for ($i = 0; $i < $usersCount; $i++) {
                $role = $faker->boolean(90) ? $userRole : $adminRole;
                $user = User::create([
                    'name'          => $faker->name,
                    'email'         => $faker->unique()->safeEmail,
                    'password'      => Hash::make('password'),
                    'department_id' => $department->id,
                    'role_id'       => $role->id,
                ]);

                // 1-3 phones per user
                $phonesCount = rand(1, 1);
                for ($p = 0; $p < $phonesCount; $p++) {
                    $phone = UserPhone::create([
                        'user_id'   => $user->id,
                        'phone'     => '+' . $faker->numberBetween(998900000000, 998999999999),
                        'is_active' => true,
                    ]);

                    // 1-3 message groups per phone
                    $groupsCount = rand(2, 5);
                    for ($g = 0; $g < $groupsCount; $g++) {
                        $group = MessageGroup::create([
                            'user_phone_id' => $phone->id,
                            // 'status'        => $faker->randomElement(['scheduled','sent','canceled','failed','processing']),
                            'status'        => $faker->randomElement(['failed','processing']),

                        ]);

                        // Generate a single message text for this group
                        $messageText = $faker->sentence(6);

                        // 2-10 messages per group (same text, different peer/status)
                        $messagesCount = rand(50, 100);
                        for ($m = 0; $m < $messagesCount; $m++) {
                            TelegramMessage::create([
                                'message_group_id'    => $group->id,
                                'telegram_message_id' => $faker->unique()->randomNumber(6, true),
                                'peer'                => 'Peer ' . $faker->randomLetter(),
                                'message_text'        => $messageText, // SAME text for all messages in this group
                                'send_at'             => $faker->dateTimeBetween('-30 days', 'now'),
                                'sent_at'             => $faker->dateTimeBetween('-30 days', 'now'),
                                'status'              => $faker->randomElement(['scheduled','sent','canceled']),
                                'attempts'            => rand(1, 5),
                            ]);
                        }
                    }
                }
            }

           
        }
    }
}

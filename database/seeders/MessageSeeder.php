<?php

namespace Database\Seeders;

use App\Models\Message;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $messages = [
            ['doctor_id' => 2, 'user_id' => 1000, 'message' => 'Hello Sir', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 0, 'send_user' => 1],
            ['doctor_id' => 2, 'user_id' => 1000, 'message' => 'I want to get treatment from you soon.', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 0, 'send_user' => 1],
            ['doctor_id' => 2, 'user_id' => 1000, 'message' => 'Can you please provide me your information so that I can contact on your chambers.', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 0, 'send_user' => 1],
            ['doctor_id' => 1, 'user_id' => 1000, 'message' => 'Hello', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 0, 'send_user' => 1],
            ['doctor_id' => 2, 'user_id' => 1000, 'message' => 'Yes. You can contact me. My official phone is: 222-2323-1222', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 2, 'send_user' => 0],
            ['doctor_id' => 2, 'user_id' => 1000, 'message' => 'Thank you very much, sir', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 0, 'send_user' => 1],
            ['doctor_id' => 2, 'user_id' => 1000, 'message' => 'You are welcome', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 2, 'send_user' => 0],
            ['doctor_id' => 1, 'user_id' => 1000, 'message' => 'Are you there?', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 0, 'send_user' => 1],
            ['doctor_id' => 1, 'user_id' => 1000, 'message' => 'yes there', 'doctor_view' => 0, 'user_view' => 1, 'send_doctor' => 0, 'send_user' => 1],
            ['doctor_id' => 2, 'user_id' => 1000, 'message' => 'lorem ipsum', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 2, 'send_user' => 0],
            ['doctor_id' => 2, 'user_id' => 1000, 'message' => 'Yes. You can contact me. My official phone is: 222-2323-1222', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 2, 'send_user' => 0],
            ['doctor_id' => 1, 'user_id' => 1000, 'message' => 'hi', 'doctor_view' => 0, 'user_view' => 1, 'send_doctor' => 0, 'send_user' => 1],
            ['doctor_id' => 2, 'user_id' => 1000, 'message' => 'hi', 'doctor_view' => 1, 'user_view' => 1, 'send_doctor' => 2, 'send_user' => 0],
        ];

        foreach ($messages as $message) {
            Message::create($message);
        }
    }
}

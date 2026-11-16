<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/
Broadcast::channel('patient-chat.{id}', function ($user,$id) {
    return $user->id == $id;
},['guards'=>['web','api']]);
Broadcast::channel('doctor-chat.{id}', function ($user,$id) {
    return $user->id == $id;
},['guards'=>['doctor','doctor_api']]);

Broadcast::channel('online', function($user) {
    return $user->only('id');
}, ['guards'=>['web','api','doctor','doctor_api']]);

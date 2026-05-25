<?php

use Illuminate\Support\Facades\Broadcast;


Broadcast::routes(['middleware' => ['auth']]);

// routes/channels.php
Broadcast::channel('supir.{supirId}', function ($user, $supirId) {
    return optional($user->supir)->id == (int) $supirId;
});

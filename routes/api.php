<?php

Route::post('/{resource}/conditional/{field}/{conditional}',
    \Firework\NovaConditionalFields\Http\Controllers\ConditionalFieldsController::class . '@index');

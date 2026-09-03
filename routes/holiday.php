<?php

// Holiday CRUD routes live in routes/web.php (Route::resource('holiday', ...)).
// Do not re-register them here with string controllers — Laravel 11 cannot resolve
// 'HolidayController@edit', which caused a 500 "Server Error" on the Edit popup.

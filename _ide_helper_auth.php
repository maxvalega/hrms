<?php
/**
 * Intelephense / IDE overrides — does not affect runtime.
 * Helps the editor understand Laravel facade aliases and Auth::user() return type.
 */

namespace Illuminate\Support\Facades {
    /**
     * @method static \App\Models\User|null user(string|null $guard = null)
     * @method static \App\Models\User|null guard(string|null $name = null)
     */
    class Auth
    {
    }
}

namespace {
    /**
     * @method static \App\Models\User|null user(string|null $guard = null)
     */
    class Auth extends \Illuminate\Support\Facades\Auth
    {
    }

    class Log extends \Illuminate\Support\Facades\Log
    {
    }

    class Validator extends \Illuminate\Support\Facades\Validator
    {
    }

    class DB extends \Illuminate\Support\Facades\DB
    {
    }

    class Storage extends \Illuminate\Support\Facades\Storage
    {
    }

    class Cache extends \Illuminate\Support\Facades\Cache
    {
    }

    class Crypt extends \Illuminate\Support\Facades\Crypt
    {
    }

    class Hash extends \Illuminate\Support\Facades\Hash
    {
    }

    class Session extends \Illuminate\Support\Facades\Session
    {
    }

    class File extends \Illuminate\Support\Facades\File
    {
    }

    class Str extends \Illuminate\Support\Str
    {
    }

    class Arr extends \Illuminate\Support\Arr
    {
    }
}

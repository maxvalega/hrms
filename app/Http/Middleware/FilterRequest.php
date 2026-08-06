<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FilterRequest
{
    protected array $skipSanitizeKeys = [
        '_token',
        '_method',
        '_previous',
        'password',
        'password_confirmation',
        'current_password',
        'mail_password',
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'g-recaptcha-response',
        'selfie_data',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        $this->sanitizeInput($input);
        $request->merge($input);

        return $next($request);
    }

    protected function sanitizeInput(array &$input): void
    {
        foreach ($input as $key => &$value) {
            if (in_array($key, $this->skipSanitizeKeys, true)) {
                continue;
            }

            if (is_array($value)) {
                $this->sanitizeInput($value);
                continue;
            }

            if (is_string($value)) {
                $value = htmlspecialchars_decode($value);
                $value = preg_replace('/<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/is', '', $value);
                $value = str_replace(['&lt;', '&gt;', 'javascript', 'script', 'alert'], '', $value);
            }
        }
    }
}

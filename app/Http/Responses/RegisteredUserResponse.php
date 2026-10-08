<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse;

/** После регистрации: если почта не подтверждена — на страницу «Проверьте почту». */
class RegisteredUserResponse implements RegisterResponse
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json('', 201);
        }

        $user = $request->user();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('status', $request->session()->pull('verification_mail_failed', false) ? 'verification-link-failed' : null);
        }

        return redirect()->intended(config('fortify.home'));
    }
}

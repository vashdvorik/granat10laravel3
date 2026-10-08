<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\VerifyEmailResponse;

/** После перехода по ссылке из письма: в кабинет с сообщением об успехе. */
class VerifiedEmailResponse implements VerifyEmailResponse
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json('', 204);
        }

        return redirect()
            ->route('account.index')
            ->with('success', 'Почта подтверждена. Добро пожаловать в кабинет!');
    }
}

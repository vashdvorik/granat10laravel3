<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse;
use Laravel\Fortify\Fortify;

/** Ответ на «Отправить письмо ещё раз»: честно сообщает, если письмо отправить не удалось. */
class VerificationLinkSentResponse implements EmailVerificationNotificationSentResponse
{
    public function toResponse($request)
    {
        $failed = (bool) $request->session()->pull('verification_mail_failed', false);

        if ($request->wantsJson()) {
            return new JsonResponse('', $failed ? 503 : 202);
        }

        return back()->with('status', $failed ? 'verification-link-failed' : Fortify::VERIFICATION_LINK_SENT);
    }
}

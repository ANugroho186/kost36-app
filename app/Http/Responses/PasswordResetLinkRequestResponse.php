<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Jawaban yang sama untuk email terdaftar, tidak terdaftar, maupun yang sedang di-throttle,
 * agar form lupa password tidak bisa dipakai untuk menebak email siapa saja yang terdaftar
 * (user enumeration). Status dari Fortify sengaja diabaikan.
 */
class PasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public const MESSAGE = 'If that email address is registered, a password reset link has been sent.';

    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $message = __(self::MESSAGE);

        return $request->wantsJson()
            ? new JsonResponse(['message' => $message], 200)
            : back()->with('status', $message);
    }
}

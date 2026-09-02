<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('consumer.dashboard');
        }

        $this->seo()->title('Confirma o teu email')->noindex(follow: false);

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('consumer.dashboard');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()
            ->route($request->user()->isBusiness() ? 'business.dashboard' : 'consumer.dashboard')
            ->with('success', 'Email confirmado. Já podes usar todas as funcionalidades.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back();
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Enviámos um novo email de confirmação.');
    }
}

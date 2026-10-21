<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\SelfUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/** Admin → App update: shows which build runs and pulls the newest one from GitHub. */
class SystemController extends Controller
{
    public function show(SelfUpdater $updater): Response
    {
        return Inertia::render('setup/Update', [
            'status' => $updater->status(),
            'auto' => (bool) config('khamar.updates.auto'),
            'repository' => config('khamar.updates.repository'),
        ]);
    }

    public function update(Request $request, SelfUpdater $updater): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']], [
            'current_password.current_password' => __('আপনার নিজের লগইন পাসওয়ার্ড মেলেনি।'),
        ]);

        // Backup, pull, migrate and caches can take a couple of minutes on shared hosting.
        set_time_limit(900);
        ignore_user_abort(true);

        try {
            $result = $updater->update();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['update' => $exception->getMessage()]);
        }

        AuditLog::record('app_updated', null, ['revision' => $result['from']], ['revision' => $result['to'], 'ok' => $result['ok']]);

        Inertia::flash('toast', $result['ok']
            ? ['type' => 'success', 'message' => __('সাইট সর্বশেষ সংস্করণে আপডেট হয়েছে।')]
            : ['type' => 'error', 'message' => __('আপডেট শেষ হয়নি — নিচের লগ দেখুন। শুরুর আগে ডাটাবেসের ব্যাকআপ নেওয়া আছে।')]);

        return back();
    }
}

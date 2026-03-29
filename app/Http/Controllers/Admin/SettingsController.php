<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function __construct()
    {
        // Superadmin only — checked in middleware or here
    }

    public function index(Request $request)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $settings = Setting::getMany([
            'mail_mailer', 'mail_from_address', 'mail_from_name',
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption',
            'graph_tenant_id', 'graph_client_id', 'graph_client_secret',
            'magic_link_expiry_minutes',
            'turnstile_site_key', 'turnstile_secret_key',
        ]);

        // Mask the password and secret for display
        $settings['smtp_password_set'] = ! empty($settings['smtp_password']);
        $settings['graph_client_secret_set'] = ! empty($settings['graph_client_secret']);
        $settings['turnstile_secret_key_set'] = ! empty($settings['turnstile_secret_key']);
        unset($settings['smtp_password'], $settings['graph_client_secret'], $settings['turnstile_secret_key']);

        return Inertia::render('Admin/Settings', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'mail_mailer' => 'required|in:smtp,microsoft-graph,log',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string|max:255',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|string|max:10',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:tls,ssl,null',
            'graph_tenant_id' => 'nullable|string|max:255',
            'graph_client_id' => 'nullable|string|max:255',
            'graph_client_secret' => 'nullable|string|max:255',
            'magic_link_expiry_minutes' => 'required|integer|min:5|max:1440',
            'turnstile_site_key' => 'nullable|string|max:255',
            'turnstile_secret_key' => 'nullable|string|max:255',
        ]);

        // Don't overwrite secrets with empty values if they weren't changed
        if (empty($validated['smtp_password'])) {
            unset($validated['smtp_password']);
        }
        if (empty($validated['graph_client_secret'])) {
            unset($validated['graph_client_secret']);
        }
        if (empty($validated['turnstile_secret_key'])) {
            unset($validated['turnstile_secret_key']);
        }

        Setting::setMany($validated);

        return back()->with('success', 'Settings saved successfully.');
    }

    public function testEmail(Request $request)
    {
        if (! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $request->validate(['test_email' => 'required|email']);

        try {
            Mail::raw('This is a test email from SkedBetter.', function ($message) use ($request) {
                $message->to($request->test_email)
                    ->subject('SkedBetter Test Email');
            });

            return back()->with('success', "Test email sent to {$request->test_email}.");
        } catch (\Exception $e) {
            return back()->with('error', "Failed to send test email: {$e->getMessage()}");
        }
    }
}

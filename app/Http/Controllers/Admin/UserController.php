<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', '!=', 'admin');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', '%' . $q . '%')
                  ->orWhere('email', 'like', '%' . $q . '%')
                  ->orWhere('nim', 'like', '%' . $q . '%');
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function warn(Request $request, User $user)
    {
        $this->guardTarget($user);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:200']], ['reason.required' => 'Alasan teguran wajib diisi.']);

        $user->applyWarning($validated['reason']);

        return back()->with('success', 'Teguran dikirim ke ' . $user->name . '.');
    }

    public function suspend(Request $request, User $user)
    {
        $this->guardTarget($user);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:200']], ['reason.required' => 'Alasan suspend wajib diisi.']);

        $user->suspendAccount($validated['reason']);

        return back()->with('success', $user->name . ' disuspend.');
    }

    public function unsuspend(User $user)
    {
        $this->guardTarget($user);
        $user->unsuspendAccount();

        return back()->with('success', 'Suspend ' . $user->name . ' dicabut.');
    }

    private function guardTarget(User $user): void
    {
        abort_if($user->isAdmin(), 403, 'Akun admin tidak dapat ditindak.');
    }
}

<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\SafeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Форма редактирования профиля
     */
    public function edit()
    {
        $user = Auth::user();
        
        return view('backend.cabinet.user.edit', compact('user'));
    }

    /**
     * Обновление профиля
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', new SafeEmail, 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($data);

        return redirect()->route('account.user.index')
            ->with('success', 'Профиль обновлён');
    }

}

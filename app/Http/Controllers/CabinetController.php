<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CabinetController extends Controller
{
    // Метод для отображения личного кабинета
    public function cabinet()
    {
        return view('backend.cabinet.cabinet'); 
    }
    
    // Метод для обновления имени пользователя
    public function updateName(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        $user->name = $request->input('name');
        $user->save();

        return redirect()->route('cabinet')->with('success', 'Имя успешно обновлено!');
    }

    // Метод для обновления пароля
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->route('cabinet')->with('error', 'Текущий пароль неверный.');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('cabinet')->with('success', 'Пароль успешно изменён!');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Seller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CabinetController extends Controller
{
    // Метод для отображения личного кабинета
    public function cabinet()
    {
        $user = Auth::user(); 
        $seller = $user->seller; // из модели user получили селлер
        return view('backend.cabinet.cabinet', compact('seller'));
    }
    
    //страница создания магазина
    public function createShop()
    {
        $user = Auth::user();
        if ($user->seller) {
            return redirect()->route('cabinet')->with('error', 'Магазин уже создан');
        }
        return view('backend.cabinet.create_shop');
    }

    public function storeShop(Request $request)
    {
        $user = Auth::user();
        if ($user->seller) {
            return redirect()->route('cabinet')->with('error', 'Магазин уже создан');
        }

        $data = $request->validate([
            'name' => ['required','string','max:255', Rule::unique('sellers','name')],
            'phone' => ['nullable','string','max:50'],
            'town' => ['nullable','string','max:255'],
            'sellerplace' => ['nullable','string','max:255'],
            'address' => ['nullable','string'],
            'description' => ['nullable','string'],
        ]);

        $data['user_id'] = $user->id;

        $baseAlias = Str::slug($data['name']);
        $baseAlias = $baseAlias ? Str::limit($baseAlias, 240, '') : Str::lower(Str::random(6));

        $alias = $baseAlias;
        $suffixLength = 4;
        while (Seller::where('alias', $alias)->exists()) {
            $alias = $baseAlias . '-' . Str::lower(Str::random($suffixLength));
            $suffixLength++;
        }

        $data['alias'] = $alias;
        Seller::create($data);

        return redirect()->route('cabinet')->with('success', 'Магазин создан');
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

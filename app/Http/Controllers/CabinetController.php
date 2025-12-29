<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Seller;
use App\Models\SellerPackage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CabinetController extends Controller
{
    /* 
    Отображает главную страницу личного кабинета 
    пользователя с информацией о магазине 
    */

    public function cabinet()
    {
        $user = Auth::user(); 
        $seller = $user->seller; // из модели user получили селлер
        return view('backend.cabinet.cabinet', compact('seller'));
    }
    
    /*
    Показывает форму создания магазина
    (проверяет, что магазин еще не создан)
    */
    public function createShop()
    {
        $user = Auth::user();
        if ($user->seller) {
            return redirect()->route('account.index')->with('error', 'Магазин уже создан');
        }
        return view('backend.cabinet.create_shop');
    }
    /* 
    Сохраняет новый магазин в базе данных
    генерирует уникальный alias из имени магазина, валидирует данные. 
    */
    public function storeShop(Request $request)
    {
        $user = Auth::user();
        if ($user->seller) {
            return redirect()->route('account.index')->with('error', 'Магазин уже создан');
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
        // Создаем магазин
        
        $seller = Seller::create($data);

        // Автоматически присваиваем базовый пакет (ID = 1)
        \App\Models\SellerPackage::create([
            'seller_id' => $seller->id,
            'package_id' => 1,
            'start_date' => now(),
            'end_date' => null, // Базовый пакет бессрочный
            'is_active' => true,
        ]);
        return redirect()->route('account.index')->with('success', 'Магазин создан');
    }

    

    // Изменяет пароль пользователя после проверки текущего пароля
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->route('account.index')->with('error', 'Текущий пароль неверный.');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('account.index')->with('success', 'Пароль успешно изменён!');
    }

    // Аккаунт. ПОльзователь. Общая информация
    public function userIndex()
    {
        $user = Auth::user();
        return view('backend.cabinet.user.index', compact('user'));
    }

    // Показывает форму изменения пароля пользователя
    public function showChangePasswordForm()
    {
        return view('backend.cabinet.user.change_password');
    }
    /**
     * Показывает форму подтверждения удаления магазина
     */
    public function deleteShop()
    {
        $user = Auth::user();
        
        if (!$user->seller) {
            return redirect()->route('account.index')->with('error', 'У вас нет магазина');
        }
        
        return view('backend.cabinet.delete_shop', compact('user'));
    }

    /**
     * Удаляет магазин текущего пользователя
     */
    public function destroyShop(Request $request)
    {
        $user = Auth::user();
        $seller = $user->seller;
        
        if (!$seller) {
            return redirect()->route('account.index')->with('error', 'Магазин не найден');
        }
        
        // Опциональная проверка пароля для безопасности
        $request->validate([
            'password' => 'required',
        ]);
        
        if (!Hash::check($request->password, $user->password)) {
            return redirect()->back()->with('error', 'Неверный пароль');
        }
        
        // Удаляем магазин
        $seller->delete();
        
        return redirect()->route('account.index')->with('success', 'Магазин успешно удалён');
    }
}

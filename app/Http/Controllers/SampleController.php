<?php

namespace App\Http\Controllers;

use Hash;

use Sessions;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SampleController extends Controller
{
    function index()
    {
        return view('login');
    }

    function registration()
    {
        return view('registration');
    }

    function validate_registration(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6'

        ]);

        $data = $request->all();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'])
        ]);

        return redirect('login')->with('success', 'Регистрация прошла успешно! Пожалуйста, авторизуйтесь');
    }

    function validate_login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $credentials = $request->only('email', 'password');

        if(Auth::attempt($credentials))
        {
            $token =md5(uniqid());

            User::where('id', Auth::id())->update(['token'=>$token]);

            return redirect('dashboard');
        }

        return redirect('login')->with('success', 'Данные пользователя введены неверно!');

    }

    function dashboard()
    {
        if(Auth::check())
        {
            return view('dashboard');
        }

        return redirect('login')->with('success', 'У вас нет доступа к этой странце');
    }

    function logout()
    {
        Session::flush();

        Auth::logout();

        return Redirect('login');
    }

    public function profile()
    {
        if(Auth::check())
        {
            $data = User::where('id', Auth::id())->get();

            return view('profile', compact('data'));
        }

        return redirect('login')->with('success', 'У вас нет прав доступа к этой странице');
    }

    public function profile_validation(Request $request)
    {
        $request->validate([
            'name'      =>  'required',
            'email'     =>  'required|email',
            'user_image'   =>   'image|mimes:jpg,png,jpeg|max:2048|dimensions:min_width=100,min_height=100,max_width=1000,max_height=1000'
        ]);

        $user_image = $request->hidden_user_image;

        if($request->user_image != '')
        {
            $user_image = time() . '.' . $request->user_image->getClientOriginalExtension();

            $request->user_image->move(public_path('images'), $user_image);
        }

        $user = User::find(Auth::id());

        $user->name = $request->name;

        $user->email = $request->email;

        if($request->password != '')
        {
            $user->password = Hash::make($request->password);
        }

        $user->user_image = $user_image;

        $user->save();

        return redirect('profile')->with('success', 'Данные профиля успешно изменены');
    }
}

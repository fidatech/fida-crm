<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
class AuthController extends Controller {
 public function login(){return view('auth.login');}
 public function register(){return view('auth.register');}
 public function authenticate(Request $r){$d=$r->validate(['email'=>'required|email','password'=>'required|string']);if(!Auth::attempt($d,$r->boolean('remember')))return back()->withErrors(['email'=>'Invalid credentials.'])->onlyInput('email');$r->session()->regenerate();return redirect()->intended(route('dashboard'));}
 public function store(Request $r){$d=$r->validate(['name'=>'required|string|max:255','organization'=>'required|string|max:255','email'=>'required|email|max:255|unique:users,email','password'=>['required','confirmed',Password::min(10)->mixedCase()->numbers()]]);
  $user=DB::transaction(function()use($d){$user=User::create(['name'=>$d['name'],'email'=>strtolower($d['email']),'password'=>$d['password']]);app(\App\Services\WorkspaceProvisioner::class)->create($d['organization'],$user->id);return $user;});
  Auth::login($user);$r->session()->regenerate();return redirect()->route('dashboard')->with('success','Workspace created.');
 }
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('login');}
}

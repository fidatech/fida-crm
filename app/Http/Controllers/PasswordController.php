<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use App\Models\User;
class PasswordController extends Controller {
 public function forgot(){return view('auth.forgot');}
 public function send(Request $r){$r->validate(['email'=>'required|email']);Password::sendResetLink($r->only('email'));return back()->with('success','If the address exists, reset instructions have been sent.');}
 public function reset(Request $r,string $token){return view('auth.reset',['token'=>$token,'email'=>$r->query('email')]);}
 public function update(Request $r){$r->validate(['token'=>'required','email'=>'required|email','password'=>['required','confirmed',PasswordRule::min(10)->mixedCase()->numbers()]]);$status=Password::reset($r->only('email','password','password_confirmation','token'),function(User $user,string $password){$user->forceFill(['password'=>$password,'remember_token'=>Str::random(60)])->save();});return $status===Password::PASSWORD_RESET?redirect()->route('login')->with('success','Password changed.'):back()->withErrors(['email'=>'The link was invalid or expired.']);}
}

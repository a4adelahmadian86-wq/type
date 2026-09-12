<?php
namespace App\Http\Middleware;
use App\Models\EditorSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class EnsureSingleEditor {
 public function handle(Request $request, Closure $next) {
   if (!$request->user()) return redirect()->route('login');
   if ($request->user()->is_blocked) abort(403);
   $session = EditorSession::where('user_id',$request->user()->id)->where('expires_at','>',now())->first();
   if (!$session) {
      $session = EditorSession::create(['user_id'=>$request->user()->id,'token'=>Str::random(64),'last_seen_at'=>now(),'expires_at'=>now()->addMinutes(20)]);
   } else {
      $current = $request->session()->get('editor_session_token');
      if ($current && !hash_equals($session->token,$current)) return response()->view('errors.single-session',[],409);
   }
   $request->session()->put('editor_session_token',$session->token);
   return $next($request);
 }
}

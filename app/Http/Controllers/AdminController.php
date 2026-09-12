<?php
namespace App\Http\Controllers;
use App\Models\PricingRule;use App\Models\User;use Illuminate\Http\Request;
class AdminController extends Controller {public function index(){return view('admin.index',['rules'=>PricingRule::orderBy('id')->get(),'users'=>User::latest()->limit(50)->get()]);}public function updatePricing(Request $r){$d=$r->validate(['key'=>'required|string','value'=>'required|integer|min:0','label'=>'required|string|max:160']);PricingRule::updateOrCreate(['key'=>$d['key']],['value'=>$d['value'],'label'=>$d['label'],'active'=>true]);return back();}public function toggleUser(User $user){abort_if($user->isAdmin(),403);$user->update(['is_blocked'=>!$user->is_blocked]);return back();}}

<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\CapabilityService;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(CapabilityService $capabilities){abort_unless($capabilities->allowed(auth()->user(),'can_support'),403,'پشتیبانی برای این حساب فعال نیست.');return view('support',['tickets'=>auth()->user()->tickets()->with('messages')->latest()->get()]);}
    public function create(Request $r,CapabilityService $capabilities){abort_unless($capabilities->allowed(auth()->user(),'can_support'),403);$d=$r->validate(['subject'=>'required|string|max:160','body'=>'required|string|max:5000']);$t=Ticket::create(['user_id'=>auth()->id(),'subject'=>$d['subject'],'status'=>'open']);$t->messages()->create(['user_id'=>auth()->id(),'body'=>$d['body']]);return back();}
    public function message(Request $r,Ticket $ticket,CapabilityService $capabilities){abort_unless($capabilities->allowed(auth()->user(),'can_support'),403);abort_unless($ticket->user_id===auth()->id(),403);$d=$r->validate(['body'=>'required|string|max:5000']);$ticket->messages()->create(['user_id'=>auth()->id(),'body'=>$d['body']]);$ticket->touch();return back();}
}

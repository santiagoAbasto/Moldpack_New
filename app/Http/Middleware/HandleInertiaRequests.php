<?php
namespace App\Http\Middleware;
use Inertia\Middleware;
class HandleInertiaRequests extends Middleware { protected $rootView='admin'; public function share($request): array { return array_merge(parent::share($request),['auth'=>fn()=>['user'=>$request->user()?->only('id','name','email')],'flash'=>fn()=>['success'=>$request->session()->get('success'),'error'=>$request->session()->get('error')]]); } }

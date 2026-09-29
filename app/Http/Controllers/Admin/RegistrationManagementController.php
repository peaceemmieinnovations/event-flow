<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\Event; use App\Models\Registration; use Illuminate\Http\Request; use Illuminate\Validation\Rule;
class RegistrationManagementController extends Controller {
 public function index(Request $request) { $registrations=Registration::query()->with('event')->when($request->search,fn($q)=>$q->where(fn($q)=>$q->where('reference','like','%'.$request->search.'%')->orWhere('email','like','%'.$request->search.'%')))->when($request->event,fn($q)=>$q->where('event_id',$request->event))->when($request->status,fn($q)=>$q->where('status',$request->status))->latest('registered_at')->paginate(20)->withQueryString(); return view('admin.registrations.index',['registrations'=>$registrations,'events'=>Event::orderBy('title')->get(['id','title'])]); }
 public function show(Registration $registration) { return view('admin.registrations.show',['registration'=>$registration->load('event')]); }
 public function updateStatus(Request $request, Registration $registration) { $data=$request->validate(['status'=>['required',Rule::in(['pending','confirmed','cancelled','attended'])]]); $registration->update($data); return back()->with('status','Registration status updated.'); }
}

<?php
namespace App\Http\Controllers;
use App\Models\Event; use App\Models\Registration; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB; use Illuminate\Support\Str;
class RegistrationController extends Controller {
 public function create(Event $event) { abort_unless($event->registrationIsOpen(), 404); return view('registrations.create', compact('event')); }
 public function store(Request $request, Event $event) {
  $data=$request->validate(['first_name'=>['required','string','max:100'],'last_name'=>['required','string','max:100'],'email'=>['required','email','max:255'],'phone'=>['required','string','max:40'],'organization'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:2000']]);
  $registration=DB::transaction(function () use($event,$data) { $event=$event->newQuery()->lockForUpdate()->findOrFail($event->id); abort_unless($event->registrationIsOpen(), 422); if($event->registrations()->where('email',$data['email'])->exists()) abort(422,'You are already registered for this event.'); return $event->registrations()->create($data+['reference'=>'EF-'.strtoupper(Str::random(8)),'status'=>'confirmed','registered_at'=>now()]); });
  $request->session()->put('registration_reference', $registration->reference);
  return to_route('registrations.confirmation',$registration);
 }
 public function confirmation(Registration $registration) { abort_unless(session('registration_reference') === $registration->reference, 404); return view('registrations.confirmation', compact('registration')); }
}

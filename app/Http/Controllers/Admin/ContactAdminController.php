<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactAdminController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));

        $query = Contact::query()->latest();

        if ($q !== '') {
            $query->where(function ($w) use ($q): void {
                $w->where('name', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('address', 'like', '%'.$q.'%')
                    ->orWhere('product', 'like', '%'.$q.'%')
                    ->orWhere('message', 'like', '%'.$q.'%');
            });
        }
        if (in_array($status, Contact::STATUSES, true)) {
            $query->where('status', $status);
        }

        return view('admin.contacts.index', [
            'title' => 'Contacts',
            'breadcrumbs' => [['label' => 'Contacts']],
            'contacts' => $query->take(200)->get(),
            'q' => $q,
            'status' => $status,
            'statuses' => Contact::STATUSES,
            'newCount' => Contact::query()->where('status', Contact::STATUS_NEW)->count(),
        ]);
    }

    public function show(Contact $contact): View
    {
        if ($contact->status === Contact::STATUS_NEW) {
            $contact->update(['status' => Contact::STATUS_READ]);
        }

        return view('admin.contacts.show', [
            'title' => 'Contact #'.$contact->id,
            'breadcrumbs' => [
                ['label' => 'Contacts', 'url' => route('admin.contacts.index')],
                ['label' => '#'.$contact->id],
            ],
            'contact' => $contact,
            'statuses' => Contact::STATUSES,
        ]);
    }

    public function status(Request $request, Contact $contact): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:'.implode(',', Contact::STATUSES),
        ]);

        $contact->update(['status' => $request->string('status')->toString()]);

        return redirect()->route('admin.contacts.show', $contact)->with('success', 'Status updated.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('admin.contacts.index')->with('success', 'Contact deleted.');
    }
}

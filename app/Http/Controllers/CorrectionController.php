<?php

namespace App\Http\Controllers;

use App\Services\CorrectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CorrectionController extends Controller
{
    public function index()
    {
        $requests = DB::table('correction_requests')->where(fn ($q) => $q->where('requested_by', auth()->id())->orWhere('counterparty_id', auth()->id()))->latest('id')->get();

        return view('vendor.corrections', compact('requests'));
    }

    public function store(Request $request, int $id, CorrectionService $service)
    {
        $data = $request->validate(['request_key' => ['required', 'uuid'], 'reason' => ['required', 'string', 'min:5', 'max:1000']]);
        try {
            $service->request($id, auth()->id(), $data['request_key'], $data['reason']);

            return redirect()->route('corrections.index')->with('success', 'Correction requested. Custody remains unchanged until the affected counterparty accepts.');
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function decide(Request $request, int $id, CorrectionService $service)
    {
        $data = $request->validate(['decision' => ['required', 'in:accepted,disputed,cancelled'], 'reason' => ['required', 'string', 'min:5', 'max:1000']]);
        try {
            $service->decide($id, auth()->id(), $data['decision'], $data['reason']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Correction decision recorded.');
    }
}

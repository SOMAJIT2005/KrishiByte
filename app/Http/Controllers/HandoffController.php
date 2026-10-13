<?php

namespace App\Http\Controllers;

use App\Models\HandoffProposal;
use App\Models\Transaction;
use App\Services\BatchHandoffService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class HandoffController extends Controller
{
    public function index()
    {
        $proposals = HandoffProposal::with(['batch.crop', 'seller', 'buyer', 'proposer'])->where(fn ($q) => $q->where('seller_id', auth()->id())->orWhere('buyer_id', auth()->id()))->latest()->paginate(15);

        return view('vendor.proposals', compact('proposals'));
    }

    public function decide(Request $request, int $id, BatchHandoffService $service)
    {
        $request->validate(['decision' => ['required', 'in:accepted,disputed,cancelled'], 'reason' => ['nullable', 'string', 'min:5', 'max:1000']]);
        try {
            $proposal = $service->decide($id, auth()->id(), $request->input('decision'), $request->input('reason'));

            return back()->with('success', "Proposal #{$id}: {$proposal->status}.");
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function receipt(int $id)
    {
        $transaction = Transaction::with(['batch.crop', 'seller', 'buyer', 'district'])->findOrFail($id);
        abort_unless(in_array(auth()->id(), [$transaction->seller_id, $transaction->buyer_id, $transaction->recorded_by_user_id], true) || auth()->user()->isAdmin(), 403);

        return response()->view('vendor.receipt', compact('transaction'))->header('Cache-Control', 'private, no-store');
    }
}

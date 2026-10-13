<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class MovementStatusTest extends TestCase
{
    public function test_movements_distinguish_first_collection_confirmation_and_voiding(): void
    {
        $farmer = new User(['role' => 'farmer']);
        $farmer->id = 1;
        $collector = new User(['role' => 'collector']);
        $collector->id = 2;
        $tx = new Transaction(['seller_id' => 1, 'buyer_id' => 2, 'stage' => 'farmer_to_collector', 'confirmation_status' => 'collector_attested']);
        $this->assertSame('Received from farmer (collector attested)', $tx->movementStatusFor($collector));
        $tx->confirmation_status = 'counterparty_accepted';
        $this->assertSame('Received from farmer', $tx->movementStatusFor($collector));
        $this->assertSame('Dispatched', $tx->movementStatusFor($farmer));
        $tx->confirmation_status = 'legacy_unconfirmed';
        $this->assertSame('Received from farmer (unconfirmed record)', $tx->movementStatusFor($collector));
        $tx->confirmation_status = 'pending';
        $this->assertSame('Awaiting confirmation', $tx->movementStatusFor($collector));
        $tx->is_voided = true;
        $this->assertSame('Voided', $tx->movementStatusFor($collector));
    }

    public function test_retail_has_no_dispatch_status_and_unrelated_accounts_have_no_movement(): void
    {
        $retailer = new User(['role' => 'retail']);
        $retailer->id = 3;
        $unrelated = new User(['role' => 'wholesale']);
        $unrelated->id = 99;
        $tx = new Transaction(['seller_id' => 2, 'buyer_id' => 3, 'stage' => 'collector_to_retail', 'confirmation_status' => 'counterparty_accepted']);
        $this->assertSame('Received', $tx->movementStatusFor($retailer));
        $this->assertNull($tx->movementStatusFor($unrelated));
        $tx->seller_id = 3;
        $tx->buyer_id = null;
        $tx->stage = 'retail_sale';
        $this->assertNull($tx->movementStatusFor($retailer));
    }
}

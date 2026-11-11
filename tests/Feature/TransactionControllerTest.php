<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Manager']);
        Role::create(['name' => 'Notaris']);
    }

    public function test_manager_can_store_transaction_when_at_least_one_jenis_permohonan_is_selected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Manager');

        $this->actingAs($user)
            ->withSession([
                'step1' => [
                    'nama_lengkap' => 'Jane Doe',
                    'tanggal_permohonan' => '2026-11-11',
                ],
            ])
            ->post(route('trx.store2'), [
                'kategori' => 'Bank',
                'jenis_permohonan' => ['SKMHT', 'AJB'],
                'nama_bank' => 'BRI',
                'plafon' => '500000000',
                'biaya_SKMHT' => '1500000',
                'biaya_AJB' => '2500000',
            ])
            ->assertRedirect(route('trx.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'nama_lengkap' => 'Jane Doe',
            'kategori' => 'Bank',
        ]);

        $this->assertDatabaseCount('transaction_details', 2);

        $transaction = Transaction::first();
        $this->assertMatchesRegularExpression('/^TRX-\d{8}-[A-Z0-9]+$/', $transaction->id_transaksi);
    }

    public function test_transaction_requires_at_least_one_selected_jenis_permohonan(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Manager');

        $this->actingAs($user)
            ->withSession([
                'step1' => [
                    'nama_lengkap' => 'Jane Doe',
                    'tanggal_permohonan' => '2026-11-11',
                ],
            ])
            ->post(route('trx.store2'), [
                'kategori' => 'Bank',
                'jenis_permohonan' => [],
                'nama_bank' => 'BRI',
                'plafon' => '500000000',
            ])
            ->assertSessionHasErrors('jenis_permohonan');
    }
}

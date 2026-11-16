<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\TransactionDetail;
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
                'plafon' => '500.000.000',
                'biaya_SKMHT' => '1.500.000',
                'biaya_AJB' => '2.500.000',
            ])
            ->assertRedirect(route('trx.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'nama_lengkap' => 'Jane Doe',
            'kategori' => 'Bank',
        ]);

        $this->assertDatabaseCount('transaction_details', 2);
        $this->assertDatabaseHas('transaction_details', [
            'jenis_permohonan' => 'SKMHT',
            'biaya' => 1500000,
        ]);

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

    public function test_store_rejects_a_jenis_permohonan_outside_the_selected_category(): void
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
                'jenis_permohonan' => ['PT - Pendirian'],
            ])
            ->assertSessionHasErrors('jenis_permohonan.0');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_store_rejects_duplicate_jenis_permohonan_values(): void
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
                'jenis_permohonan' => ['SKMHT', 'SKMHT'],
            ])
            ->assertSessionHasErrors('jenis_permohonan.1');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_store_redirects_to_step1_when_session_data_is_missing(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Manager');

        $this->actingAs($user)
            ->post(route('trx.store2'), [
                'kategori' => 'Bank',
                'jenis_permohonan' => ['SKMHT'],
            ])
            ->assertRedirect(route('trx.step1'));

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_manager_cannot_update_another_managers_transaction_status(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $otherManager = User::factory()->create();
        $otherManager->assignRole('Manager');

        $transaction = Transaction::create([
            'user_id' => $otherManager->id,
            'nama_lengkap' => 'Jane Doe',
            'tanggal_permohonan' => '2026-11-11',
            'kategori' => 'Bank',
        ]);
        $detail = TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'jenis_permohonan' => 'SKMHT',
            'biaya' => 1500000,
            'status_dikerjakan' => false,
        ]);

        $this->actingAs($manager)
            ->patch(route('trx.updateStatus', $detail->id), [
                'status_dikerjakan' => '1',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('transaction_details', [
            'id' => $detail->id,
            'status_dikerjakan' => false,
        ]);
    }

    public function test_manager_can_update_status_for_own_transaction(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $transaction = Transaction::create([
            'user_id' => $manager->id,
            'nama_lengkap' => 'Jane Doe',
            'tanggal_permohonan' => '2026-11-11',
            'kategori' => 'Bank',
        ]);
        $detail = TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'jenis_permohonan' => 'SKMHT',
            'biaya' => 1500000,
            'status_dikerjakan' => false,
        ]);

        $this->actingAs($manager)
            ->patch(route('trx.updateStatus', $detail->id), [
                'status_dikerjakan' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transaction_details', [
            'id' => $detail->id,
            'status_dikerjakan' => true,
        ]);
    }

    public function test_status_update_rejects_values_other_than_boolean(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');

        $transaction = Transaction::create([
            'user_id' => $manager->id,
            'nama_lengkap' => 'Jane Doe',
            'tanggal_permohonan' => '2026-11-11',
            'kategori' => 'Bank',
        ]);
        $detail = TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'jenis_permohonan' => 'SKMHT',
            'biaya' => 1500000,
            'status_dikerjakan' => false,
        ]);

        $this->actingAs($manager)
            ->patch(route('trx.updateStatus', $detail->id), [
                'status_dikerjakan' => 'selesai',
            ])
            ->assertSessionHasErrors('status_dikerjakan');

        $this->assertDatabaseHas('transaction_details', [
            'id' => $detail->id,
            'status_dikerjakan' => false,
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                if (!Schema::hasColumn('assets', 'asset_code')) {
                    $table->string('asset_code', 50)->nullable()->after('name');
                }
                if (!Schema::hasColumn('assets', 'serial_number')) {
                    $table->string('serial_number', 120)->nullable()->after('asset_code');
                }
                if (!Schema::hasColumn('assets', 'category')) {
                    $table->string('category', 80)->nullable()->after('serial_number');
                }
                if (!Schema::hasColumn('assets', 'brand')) {
                    $table->string('brand', 80)->nullable()->after('category');
                }
                if (!Schema::hasColumn('assets', 'model')) {
                    $table->string('model', 80)->nullable()->after('brand');
                }
                if (!Schema::hasColumn('assets', 'condition')) {
                    $table->string('condition', 30)->default('good')->after('model');
                }
                if (!Schema::hasColumn('assets', 'status')) {
                    $table->string('status', 30)->default('in_inventory')->after('condition');
                }
                if (!Schema::hasColumn('assets', 'current_employee_id')) {
                    $table->unsignedBigInteger('current_employee_id')->nullable()->after('employee_id');
                }
                if (!Schema::hasColumn('assets', 'location')) {
                    $table->string('location', 120)->nullable()->after('description');
                }
            });

            $this->backfillExistingAssets();
        }

        if (!Schema::hasTable('asset_movements')) {
            Schema::create('asset_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('asset_id');
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->string('action', 40);
                $table->unsignedBigInteger('replacement_asset_id')->nullable();
                $table->string('condition', 30)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('performed_by')->nullable();
                $table->unsignedBigInteger('created_by')->default(0);
                $table->timestamps();

                $table->index(['asset_id', 'id']);
                $table->index(['created_by', 'action']);
                $table->index('employee_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_movements');

        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                foreach ([
                    'asset_code',
                    'serial_number',
                    'category',
                    'brand',
                    'model',
                    'condition',
                    'status',
                    'current_employee_id',
                    'location',
                ] as $column) {
                    if (Schema::hasColumn('assets', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    protected function backfillExistingAssets(): void
    {
        if (!Schema::hasColumn('assets', 'status')) {
            return;
        }

        $assets = DB::table('assets')->select('id', 'employee_id', 'asset_code', 'created_by')->get();
        $counters = [];

        foreach ($assets as $row) {
            $parts = array_values(array_filter(array_map('trim', explode(',', (string) $row->employee_id)), function ($v) {
                return $v !== '' && $v !== '0';
            }));
            $empId = $parts ? (int) $parts[0] : null;
            $creator = (int) $row->created_by;
            if (!isset($counters[$creator])) {
                $counters[$creator] = 0;
            }
            $counters[$creator]++;

            $update = [
                'current_employee_id' => $empId ?: null,
                'status' => $empId ? 'assigned' : 'in_inventory',
                'condition' => 'good',
            ];

            if (empty($row->asset_code)) {
                $update['asset_code'] = 'AST-' . str_pad((string) $counters[$creator], 4, '0', STR_PAD_LEFT);
            }

            DB::table('assets')->where('id', $row->id)->update($update);
        }
    }
};

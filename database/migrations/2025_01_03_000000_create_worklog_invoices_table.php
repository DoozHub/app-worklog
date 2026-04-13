<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create invoices table
        Schema::create('worklog_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('worklog_clients');
            $table->string('invoice_number', 50)->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_minutes');
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('draft'); // draft, sent, paid, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Add invoice_id to time logs
        Schema::table('worklog_time_logs', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('note')->constrained('worklog_invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('worklog_time_logs', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        Schema::dropIfExists('worklog_invoices');
    }
};

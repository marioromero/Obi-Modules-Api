<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'cases_db';

    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10);
            $table->unsignedBigInteger('priority'); // FK to priorities
            $table->string('claim_number', 20)->nullable();
            $table->dateTime('created_at');
            $table->date('contestation_date')->nullable();
            $table->date('date_of_loss')->nullable();
            $table->date('document_signing_date')->nullable();
            $table->date('inspection_date')->nullable();
            $table->date('settlement_report_date')->nullable();
            $table->date('reporting_date')->nullable();
            $table->string('property_type', 20);
            $table->string('claim_tracking_number', 50)->nullable();
            $table->unsignedBigInteger('previous_case_number')->nullable(); // FK to cases.id (autorelación)
            $table->date('quote_submission_date')->nullable();
            $table->string('agreement', 50)->nullable();
            $table->boolean('is_duplicated')->default(false);
            $table->integer('approved_amount')->nullable();
            $table->float('uf_approved')->nullable();
            $table->integer('consultancy_amount')->nullable();
            $table->integer('amount_owed')->nullable();
            $table->integer('ammount_paid')->nullable();
            $table->date('estimated_payment_day')->nullable();
            $table->date('collection_date')->nullable();
            $table->date('online_collection_date')->nullable();
            $table->string('payment_status', 20)->nullable();
            $table->date('entry_date')->nullable();
            $table->string('description', 255)->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->string('resolution', 255)->nullable();

            // Foreign keys to other DBs (declared as unsignedBigInteger)
            $table->unsignedBigInteger('agent_id');         // users_db
            $table->unsignedBigInteger('consulant_id')->nullable(); // users_db
            $table->unsignedBigInteger('commune_id');       // geography_db
            $table->unsignedBigInteger('customer_id');      // customers_db
            $table->unsignedBigInteger('case_status_id');   // cases_db.case_statuses
            $table->unsignedBigInteger('accident_type_id'); // cases_db.accident_types
            $table->unsignedBigInteger('assigned_user');    // users_db
            $table->unsignedBigInteger('created_by');       // users_db
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};

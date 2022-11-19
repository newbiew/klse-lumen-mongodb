<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuarterlyReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quarterly_reports', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('short_name');
            $table->date('announcement_date');
            $table->string('category');
            $table->string('reference_number');
            $table->date('financial_year_end');
            $table->integer('qr_number');
            $table->date('current_period_end');
            $table->string('the_figures');
            $table->double('revenue', 14, 2);
            $table->double('pl_before_tax', 14, 2);
            $table->double('pl_after_tax', 14, 2);
            $table->double('current_pl', 14, 2);
            $table->double('current_preceding_year_percentage', 14, 2);
            $table->double('earning_per_share', 14, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quarterly_reports');
    }
}

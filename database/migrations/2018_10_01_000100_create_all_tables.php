<?php namespace Igniter\EventRules\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllTables extends Migration
{
    public function up()
    {
        Schema::create('igniter_eventrules_rules', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');
            $table->string('code');
            $table->string('description');
            $table->text('event_class')->nullable();
            $table->text('config_data')->nullable();
            $table->boolean('is_custom')->default(0);
            $table->boolean('status')->default(0);
            $table->timestamps();
        });

        Schema::create('igniter_eventrules_actions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('event_rule_id');
            $table->string('class_name');
            $table->text('options');
            $table->timestamps();
        });

        Schema::create('igniter_eventrules_conditions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('event_rule_id');
            $table->string('class_name');
            $table->text('options');
            $table->timestamps();
        });

        Schema::create('igniter_eventrules_jobs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('event_class');
            $table->morphs('eventible');
            $table->mediumText('payload');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('igniter_eventrules_rules');
        Schema::dropIfExists('igniter_eventrules_actions');
        Schema::dropIfExists('igniter_eventrules_conditions');
        Schema::dropIfExists('igniter_eventrules_jobs');
    }
}
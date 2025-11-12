<?php

use Illuminate\Database\Capsule\Manager as Capsule;

// Obtener la instancia del schema
$schema = Capsule::schema();

// Verificar si la tabla ya existe
if (!$schema->hasTable('usuarios')) {
    $schema->create('usuarios', function ($table) use ($schema) {
        $table->increments('id_usuario');
        $table->string('identificacion_tributaria_usuario', 15);
        $table->string('nombre_usuario', 150);
        $table->string('apellido_usuario', 150)->nullable();
        $table->string('password_usuario', 200);
        $table->string('email_usuario', 80);
        $table->string('direccion_usuario', 200)->nullable();
        $table->integer('avatar_usuario')->nullable();
        $table->timestamp('fecha_registro_usuario')->default(Capsule::raw('CURRENT_TIMESTAMP'));
        $table->string('cargo_usuario', 80)->nullable();
        $table->string('tel_usuario', 30);
        $table->integer('celular_usuario')->nullable();
        $table->string('token_usuario', 300)->nullable();
        $table->boolean('autorizo_usuario')->default(false);
        $table->string('ip_usuario', 30)->nullable();
        $table->string('tipo_usuario_fk', 60);
        $table->boolean('estado_usuario')->default(true);
        
        // Agregar la clave foránea solo si la tabla archivos existe
        if ($schema->hasTable('archivos')) {
            $table->foreign('avatar_usuario')
                  ->references('id_archivo')
                  ->on('archivos')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        }
    });
} 
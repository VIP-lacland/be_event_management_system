<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thêm google_id và avatar sau cột email
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar')->nullable()->after('google_id');

            // Sửa cột password thành nullable (cho phép trống) vì đăng nhập bằng Google không cần password
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar']);
            // Chú ý: Việc revert cột password từ nullable về required có thể gây lỗi nếu có data đang null,
            // nên ta cần xử lý cẩn thận nếu thực sự cần down().
            $table->string('password')->nullable(false)->change();
        });
    }
};

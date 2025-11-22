<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For SQL Server, we need to drop constraint first, then update, then recreate
        // Step 1: Find and drop the old constraint dynamically
        $constraints = DB::select("
            SELECT name 
            FROM sys.check_constraints 
            WHERE parent_object_id = OBJECT_ID('registrations') 
            AND parent_column_id = COLUMNPROPERTY(OBJECT_ID('registrations'), 'attendance_status', 'ColumnId')
        ");
        
        foreach ($constraints as $constraint) {
            try {
                DB::statement("ALTER TABLE registrations DROP CONSTRAINT " . $constraint->name);
            } catch (\Exception $e) {
                // Continue if constraint doesn't exist
            }
        }
        
        // Step 2: Drop default constraint if exists
        $defaults = DB::select("
            SELECT name 
            FROM sys.default_constraints 
            WHERE parent_object_id = OBJECT_ID('registrations') 
            AND parent_column_id = COLUMNPROPERTY(OBJECT_ID('registrations'), 'attendance_status', 'ColumnId')
        ");
        
        foreach ($defaults as $default) {
            try {
                DB::statement("ALTER TABLE registrations DROP CONSTRAINT " . $default->name);
            } catch (\Exception $e) {
                // Continue if constraint doesn't exist
            }
        }
        
        // Step 3: Update existing 'absent' to 'pending' (now that constraint is dropped)
        DB::statement("UPDATE registrations SET attendance_status = 'pending' WHERE attendance_status = 'absent'");
        
        // Step 4: Update default value
        DB::statement("ALTER TABLE registrations ALTER COLUMN attendance_status NVARCHAR(20) NOT NULL");
        DB::statement("ALTER TABLE registrations ADD CONSTRAINT DF_registrations_attendance_status DEFAULT 'pending' FOR attendance_status");
        
        // Step 5: Add new check constraint for the new values
        DB::statement("ALTER TABLE registrations ADD CONSTRAINT registrations_attendance_status_check CHECK (attendance_status IN ('pending', 'present', 'absent'))");
    }

    public function down(): void
    {
        // Revert to old enum
        try {
            DB::statement("ALTER TABLE registrations DROP CONSTRAINT registrations_attendance_status_check");
            DB::statement("ALTER TABLE registrations DROP CONSTRAINT DF_registrations_attendance_status");
        } catch (\Exception $e) {
            // Ignore if constraints don't exist
        }
        
        DB::statement("UPDATE registrations SET attendance_status = 'absent' WHERE attendance_status = 'pending'");
        DB::statement("ALTER TABLE registrations ALTER COLUMN attendance_status NVARCHAR(20) NOT NULL");
        DB::statement("ALTER TABLE registrations ADD CONSTRAINT DF_registrations_attendance_status DEFAULT 'absent' FOR attendance_status");
        DB::statement("ALTER TABLE registrations ADD CONSTRAINT registrations_attendance_status_check CHECK (attendance_status IN ('absent', 'present'))");
    }
};

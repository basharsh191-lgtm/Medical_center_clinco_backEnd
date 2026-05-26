<?php

namespace App\Services;

use App\Mail\superAdminEmail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StaffService
{
    public function createStaff(array $data, string $role)
    {
        return DB::transaction(function () use ($data, $role) {

            // إنشاء المستخدم
            $user = User::create([
                'name'        => $data['name'],
                'last_name'   => $data['last_name'],
                'email'       => $data['email'],
                'phone'       => $data['phone'],
                'password'    => Hash::make($data['password']),
                'is_verified' => true,
            ]);

            // Assign Role
            $user->assignRole($role);
            Mail::to($data['email'])->send(new superAdminEmail($data['email'],$data['password']));

            // إنشاء البيانات الخاصة حسب الدور
            match ($role) {

                'doctor' => $this->createDoctorProfile($user, $data),

                'reception' => $this->createReceptionProfile($user, $data),

                default => throw new \Exception("Invalid role")
            };

            return $user->load($this->relationName($role));
        });
    }
    private function createDoctorProfile($user, array $data)
    {
        $imagePath = $data['image']->store('doctor_picture', 'public');

        $user->doctorProfile()->create([
            'specialization_id' => $data['specialization_id'],
            'clinic_id'         => $data['clinic_id'],
            'experience_years'  => $data['experience_years'] ?? 0,
            'number_operations'  => $data['number_operations'] ?? 0,
            'bio'               => $data['bio'] ?? null,
            'image'             => $imagePath,
        ]);
    }
    private function createReceptionProfile($user, array $data)
    {
        $user->reception()->create([
            'clinic_id'   => $data['clinic_id'],
            'salary'      => $data['salary'] ?? null,
            'hiring_date' => $data['hiring_date'] ?? now(),
            'status'      => $data['status'] ?? 'active',
            'shift_type'  => $data['shift_type'],
            'biography'   => $data['biography'] ?? null,
        ]);
    }
    private function relationName(string $role): string
    {
        return match ($role) {
            'doctor' => 'doctorProfile',
            'reception' => 'reception',
            default => ''
        };
    }
}

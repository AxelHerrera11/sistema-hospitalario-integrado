<?php

namespace Database\Seeders;

use App\Modules\Reception\Infrastructure\OrderProjection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Solo datos ficticios, con bases dedicadas; nunca ejecutar contra la base clínica real. */
final class ReceptionStagingSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('staging')) throw new \RuntimeException('Solo APP_ENV=staging');
        foreach (['central','hospital'] as $connection) {
            if (!str_ends_with(config('database.connections.'.$connection.'.database'), '_staging')
                || DB::connection($connection)->getDriverName() !== 'pgsql') throw new \RuntimeException('Solo PostgreSQL *_staging');
        }
        $password = (string) env('RECEPTION_DEMO_PASSWORD');
        if (strlen($password) < 16) throw new \RuntimeException('Falta contraseña ficticia de al menos 16 caracteres');
        $hospital = (string) config('reception.hospital_id');
        if (!Str::isUuid($hospital)) throw new \RuntimeException('Configurar RECEPTION_HOSPITAL_ID');
        $central = DB::connection('central'); $clinical = DB::connection('hospital');
        $network = (string) Str::uuid(); $other = (string) Str::uuid();
        $central->table('networks')->insert(['network_id'=>$network,'name'=>'Red ficticia '.$network,'timezone'=>'America/Guatemala']);
        foreach ([$hospital,$other] as $id) $central->table('hospitals')->insertOrIgnore(['hospital_id'=>$id,
            'network_id'=>$network,'code'=>'DEMO-'.$id,'name'=>'Hospital ficticio','timezone'=>'America/Guatemala']);
        $fixture = ['hospital'=>$hospital,'other_hospital'=>$other,'password'=>$password,'users'=>[]];
        foreach (['tecnico_laboratorio','bioquimico','recepcion'] as $role) {
            $user = (string) Str::uuid(); $membership = (string) Str::uuid();
            $email = $role.'-'.substr($user,0,8).'@example.test';
            $central->table('central_users')->insert(['user_id'=>$user,'subject'=>$user,'email'=>$email,'display_name'=>'Usuario ficticio']);
            $central->table('auth_credentials')->insert(['user_id'=>$user,'email_normalized'=>$email,'password_hash'=>Hash::make($password)]);
            $central->table('hospital_memberships')->insert(['membership_id'=>$membership,'user_id'=>$user,
                'hospital_id'=>$hospital,'role_code'=>$role,'valid_from'=>now()->subMinute()]);
            $clinical->table('local_user_refs')->insert(['local_user_ref_id'=>Str::uuid(),'central_user_id'=>$user,
                'membership_id'=>$membership,'display_name'=>'Usuario ficticio','role_code'=>$role,'synced_at'=>now()]);
            $fixture['users'][$role] = $email;
        }
        $patient = (string) Str::uuid(); $episode = (string) Str::uuid();
        $clinical->table('local_patients')->insert(['local_patient_id'=>$patient,'global_id'=>Str::uuid(),'code'=>'DEMO-'.substr($patient,0,8),
            'given_name'=>'Ana','family_name'=>'Ficticia','birth_date'=>'2000-01-01','sex'=>'female']);
        $clinical->table('care_episodes')->insert(['care_episode_id'=>$episode,'local_patient_id'=>$patient,
            'type'=>'outpatient','started_at'=>now(),'reason'=>'Validación ficticia de despliegue']);
        $fixture['patient_code'] = 'DEMO-'.substr($patient,0,8); $fixture['patient_name'] = 'Ana Ficticia';
        foreach (['accepted_item','rejected_item'] as $key) {
            $fixture[$key] = (string) Str::uuid();
            (new OrderProjection)->import(['tenant_id'=>$hospital,'item_id'=>$fixture[$key],
                'local_patient_id'=>$patient,'care_episode_id'=>$episode,'patient_code'=>$fixture['patient_code'],
                'patient_name'=>$fixture['patient_name'],'type'=>'blood','container'=>'EDTA','min_volume'=>3,
                'max_minutes'=>60,'min_temperature'=>15,'max_temperature'=>25,'status'=>'pending']);
        }
        Storage::disk('local')->put('reception-stage-fixture.json', json_encode($fixture, JSON_THROW_ON_ERROR));
        $this->command?->info('Fixture ficticio guardado en storage privado; contiene credenciales y no se debe publicar.');
    }
}

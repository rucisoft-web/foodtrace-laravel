<?php

namespace App\Console\Commands;

use App\Support\Installer;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'foodtrace:install {--demo : Muat juga data demo Sarana Kulina}
        {--password= : Kata sandi admin} {--email= : Email admin (bisa dipakai untuk login)} {--company= : Nama perusahaan}';

    protected $description = 'Pasang FoodTrace lewat terminal: migrasi, data master, akun admin, (opsional) data demo.';

    public function handle(): int
    {
        $password = $this->option('password') ?: $this->secret('Kata sandi untuk pengguna "admin" (min. 8 karakter)');
        if (strlen((string) $password) < 8) {
            $this->error('Kata sandi minimal 8 karakter.');

            return self::FAILURE;
        }
        $this->info('Memasang FoodTrace'.($this->option('demo') ? ' + data demo (±1-2 menit)' : '').'...');
        $this->output->write(Installer::run($password, $this->option('email'), (bool) $this->option('demo'), $this->option('company')));
        $this->info('FoodTrace terpasang. Masuk dengan pengguna "admin"'.($this->option('email') ? ' atau '.$this->option('email') : '').'.');

        return self::SUCCESS;
    }
}

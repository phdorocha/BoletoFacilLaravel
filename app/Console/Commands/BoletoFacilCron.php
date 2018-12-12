<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMailable;

class BoletoFacilCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'boletoFacil:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automação de boletos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Schedule $schedule)
    {
        // Carrega Controller Faturas
        $Fatura = new \App\Http\Controllers\FaturaController();
        // Executa método para faturas de hoje
        $totalFaturas = $Fatura->gerarRecorrentes();
        
        $totalBoletos = $Fatura->gerarBoletosRecorrentes();

        if (count($totalFaturas) > 0)
            Mail::to('seuemail@provedor.com')->send(new SendMailable($totalFaturas));

        if (count($totalBoletos) > 0)
            Mail::to('seuemail@provedor.com')->send(new SendMailable($totalBoletos));
    }
}

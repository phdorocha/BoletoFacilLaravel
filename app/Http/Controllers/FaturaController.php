<?php

namespace App\Http\Controllers;

use App\Fatura;
use Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

use DateTime;
use DateInterval;
use Session;
use Exception;
use Redirect;
use URL;
use DB;

class FaturaController extends Controller
{
    
    /**
     *  Variáveis do BoletoFácil
     */
    private $Fatura;
    private $TotalPage = 10;
    private $token;
    private $dueDate;
    private $installments;
    private $maxOverdueDays;
    private $fine;
    private $interest;
    private $paymentToken;
    private $description;
    private $amount;
    private $payerName;
    private $payerCpfCnpj;
    private $payerEmail;
    private $notifyPayer;
    private $notificationUrl;
    private $beginPaymentDate;
    private $endPaymentDate;
    private $paymentTypes;
    private $responseType;

    public function __constructor(Fatura $Fatura)
    {
        $this->Fatura = $Fatura;
        $this->token            = config('BoletoFacil.token');
        $this->dueDate          = date('mm/dd/yyyy');
        $this->installments     = 1;
        $this->maxOverdueDays   = 29;
        $this->fine             = 2;
        $this->interest         = 1;
        $this->paymentToken     = '';
        $this->description      = '';
        $this->amount           = '';
        $this->payerName        = '';
        $this->payerCpfCnpj     = '';
        $this->payerEmail       = '';
        $this->notifyPayer      = true;
        $this->notificationUrl  = '';
        $this->beginPaymentDate = '';
        $this->endPaymentDate   = '';
        $this->paymentTypes     = 'BOLETO';
        $this->responseType     = 'JSON';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('fatura.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $documento_id = $request->documento_id;
        
        if ($request->qtdeparcelas == null)
        {
            $Fatura = Fatura::find($request->id);
            $Fatura->nDup         = $request->nDup;
            $Fatura->vDup         = str_replace(",",".", str_replace(".","", $request->vDup));
            $Fatura->tipo         = $request->tipo;
            $Fatura->vencimento   = DateTime::createFromFormat('Y-m-d', $request->vencimento);
            
            $Fatura->save();
        }

        for ($i = 0; $i <= $request->qtdeparcelas-1; $i++) {
            $vencimento = DateTime::createFromFormat('Y-m-d', $request->vencimento);
            $vencimento->add(new DateInterval('P'.($i).'M')); // 1 Mês

            $Fatura = new Fatura;
            $Fatura->nDup         = $i+1;
            $Fatura->vDup         = str_replace(",",".", str_replace(".","", $request->vDup));
            $Fatura->tipo         = $request->tipo;
            $Fatura->vencimento   = $vencimento;
            $Fatura->emissao      = DateTime::createFromFormat('d/m/Y', date('d/m/Y'));
            $Fatura->qtdeparcelas = $request->qtdeparcelas;
            $Fatura->documento_id = $request->documento_id;
            $Fatura->status       = 1;
            
            $Fatura->save();
            $Fatura = Fatura::find($Fatura->id);

            if($request->gerar == 'on'){
                $this->notifyPayer = True;
                $boleto = $this->GerarBoleto($Fatura);
                $Fatura->nDup            = $boleto->code;
                $Fatura->link            = $boleto->link;
                $Fatura->checkoutUrl     = $boleto->checkoutUrl;
                $Fatura->installmentLink = $boleto->installmentLink;
                $Fatura->payNumber       = $boleto->payNumber;
                $Fatura->save();
            }
        }

        Session::flash('message', 'Faturado com sucesso.');
        return Redirect(URL::previous());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fatura  $fatura
     * @return \Illuminate\Http\Response
     */
    public function show(Fatura $fatura)
    {        

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fatura  $fatura
     * @return \Illuminate\Http\Response
     */
    public function edit(Fatura $fatura)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fatura  $fatura
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Fatura $fatura)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fatura  $fatura
     * @return \Illuminate\Http\Response
     */
    public function destroy(Fatura $fatura)
    {

    }

    public function GerarBoleto($Fatura)
    {
        $paramURL = http_build_query(['fatura' => $Fatura->id]);
        $this->notificationUrl = 'https://webservices.sidsolucoes.com.br/retornoboletos.gravar?'.$paramURL;
        $Parametros = array(
            'token'                      => Config('boletofacil.token'),
            'description'                => 'Mensalidade Easy {mes_ano_anterior}',
            'amount'                     => $Fatura->vDup,
            'payerName'                  => $Fatura->Documento->Cliente->Nome,
            'payerCpfCnpj'               => $Fatura->Documento->Cliente->cliente_cnpj,
            'payerEmail'                 => $Fatura->Documento->Cliente->cliente_email,
            'notifyPayer'                => $this->notifyPayer,
            'notificationUrl'            => $this->notificationUrl,
            'reference'                  => $Fatura->id,
            'dueDate'                    => date('d/m/Y', strtotime($Fatura->vencimento)),
            'installments'               => 1,  //$Fatura->qtdeparcelas,
            'maxOverdueDays'             => 29, // Qtdes dias atrasos
            'fine'                       => 2,  // Multa
            'interest'                   => 1,  // Taxa de juros
            'billingAddressStreet'       => $Fatura->Documento->Cliente->cliente_logadouro,
            'billingAddressNumber'       => $Fatura->Documento->Cliente->cliente_numero,
            'billingAddressComplement'   => $Fatura->Documento->Cliente->cliente_complemento,
            'billingAddressNeighborhood' => $Fatura->Documento->Cliente->cliente_bairro,
            'billingAddressCity'         => $Fatura->Documento->Cliente->Cidade->nome,
            'billingAddressState'        => $Fatura->Documento->Cliente->Cidade->Estado->uf,
            'billingAddressPostcode'     => $Fatura->Documento->Cliente->cliente_cep,
        );

        $urlBase = $this->BoletoURL(http_build_query($Parametros));

        $client = new Client();
        try {
            $res = $client->request('GET', $urlBase);
            $retorno = json_decode($res->getBody());
            if ($retorno->success) {
                return $retorno->{'data'}->{'charges'}[0];
            }else{
                return $retorno;
            }            
        } catch (Exception $e) {
            return back()->withError($e->getMessage())->withInput();
        }
    }

    public function BoletoURL($Parametros = null)
    {
        $URL = Config('boletofacil.url');
        $URL = $URL.'?'.$Parametros;
        return $URL;
    }

    public function baixar(Request $request, $id)
    {
        $documento_id = $request->documento_id;
        $Fatura = Fatura::find($request->id);
        $Fatura->status = 2;
        $Fatura->pagoem = DateTime::createFromFormat('d/m/Y', date('d/m/Y'));

        $Fatura->save();

        Session::flash('message', 'Faturado baixada.');
        return Redirect(URL::previous());
    }

    /**
     * Gerar Faturas recorrentes
     */
    public function gerarRecorrentes()
    {
        $dataProxima = new DateTime(date('Y-m-d'));

        // Data da Próxima fatura
        $dataProxima->modify('+1 month');

        $Faturas = Fatura::where('vencimento',date('Y-m-d'))->with('documento')->get();
    
        $faturasGerar = array();

        foreach ($Faturas as $fatura){
            $res = Fatura::where('documento_id',$fatura->documento_id)
                ->where('vencimento',$dataProxima)
                ->get();

            if (count($res) <=0){
                $fatura->vencimento = $dataProxima->format('Y-m-d');;
                array_push($faturasGerar, $fatura);
            }
        }

        if (count($faturasGerar) >0){
            foreach ($faturasGerar as $item)
            {
                $Fatura = new Fatura;
                $Fatura->vencimento   = $item->vencimento;
                $Fatura->documento_id = $item->documento_id;
                $Fatura->nDup         = $item->nDup;
                $Fatura->vDup         = $item->vDup;
                $Fatura->tipo         = $item->tipo;
                $Fatura->emissao      = DateTime::createFromFormat('d/m/Y', date('d/m/Y'));
                $Fatura->qtdeparcelas = $item->qtdeparcelas;
                $Fatura->status       = 1;

                $Fatura->save();
            }

            return count($faturasGerar);
        }else{
            return 0;
        }
    }

    /**
     * Gerar Boletos recorrentes
     */
    public function gerarBoletosRecorrentes()
    {
        // Data atual
        $dataAntesVencimento = new DateTime(date('Y-m-d'));

        // Incrementa quantidade de dias antes do vencimento para gerar boleto
        $dataAntesVencimento->modify('+'.Config::get('boletofacil.daysBeforeSend').' day');

        // Habilita Notificação por e-mail
        $this->notifyPayer = True;

        // Filtra as Faturas com 
        $Faturas = Fatura::where('vencimento',$dataAntesVencimento)
                         ->whereNull('link')
                         ->get();

        if (count($Faturas) >0){
            foreach ($Faturas as $item)
            {
                $boleto = $this->GerarBoleto($item);
                $item->nDup            = $boleto->code;
                $item->link            = $boleto->link;
                $item->checkoutUrl     = $boleto->checkoutUrl;
                $item->installmentLink = $boleto->installmentLink;
                $item->payNumber       = $boleto->payNumber;
                $item->save();
            }

            return count($Faturas);

        }else{
            return 0;
        }
    }
}
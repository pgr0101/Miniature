<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Code;
use App\Label;
use App\Execute as EXE;
use function MongoDB\BSON\toJSON;
use function PHPSTORM_META\type;

class Execute implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $code; // from database code

    protected $pc = 0; // pc for counting the program

    protected $exe = []; // exe for the answer that going to e serialized

    protected $ram = []; // if the app needs ram

    protected $registerusage = 0.0; // calculating in use
    protected $ramusage = 0.0; // checking ram usage from 16000

    protected $add  = "/add\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
    protected $sub  = "/sub\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
    protected $slt  = "/slt\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
    protected $nand = "/nand\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
    protected $or   = "/or\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
    protected $addi = "/addi\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
    protected $ori  = "/ori\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
    protected $slti = "/slti\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
    protected $sw = "/sw\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\w+)/";
    protected $lw = "/lw\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\w+)/";
    protected $beq = "/beq\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\w+)/";
    protected $lui = "/lui\\s+(?P<rt>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
    protected $halt = "/halt\\s*/";
    protected $jalr = "/jalr\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*/";
    protected $j = "/j\\s+(?P<offset>\\w+)/";

    protected $fill = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>\\d+))/";
    protected $fillneg = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>[-]{1}\\d+))/";
    protected $fill1 = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>\\w+))/";
    protected $space = "/((?P<label>\\w{1,16})\\s+.space\\s+(?P<value>\\d+))/";
    protected $comment = "/\\s*#(\\S*|\\s*)*/";

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Execute the job.
     * TODO : completing
     * @return void
     */
    public function handle()
    {
        $exe = new EXE;
        $exe->code_id = $this->code->id;
        $this->init();
        $this->execute();
        $exe->exe = serialize($this->exe);
        $exe->memoryusage = $this->ramusage;
        $exe->registerusage = $this->registerusage;
        $exe->save();
        $this->code->execute_id = $exe->id;
        $this->code->save();

        /**
         * serialize and unserialize an array for saving in database
         * we use exe for this part
        */
    }

    /**
     * play the role of loader
    */
    public function init()
    {
        $this->ram = explode("\n" , $this->code->code);
    }


    public function execute()
    {
        $regused = array_fill(0,16 , -1);
        $registers = array_fill(0,16 , 0);

        while(true){
            if($this->pc >= count($this->ram)){
                break;
            }
            $line = $this->ram[$this->pc];
            $groups = array();
            if(preg_match($this->comment , $line)){
                continue;
            }
            if($line == "" || $line == "\n" || (
                preg_match('/\\s+/' , $line && !preg_match('/\\w+/' , $line)))){
                continue;
            }



            if(preg_match($this->space , $line , $groups)){
                // address f first and making space with 0 value for label value
                $val = $groups['value'];
                $this->pc += $val;
                continue;
            }

            if(preg_match($this->fill , $line , $groups)){
                $this->pc += 1;
                continue;
            }

            if(preg_match($this->fill1 , $line , $groups)){
                $this->pc += 1;
                continue;
            }

            if(preg_match($this->fillneg , $line , $groups)){
                $this->pc+=1;
                continue;
            }

            if(preg_match($this->add , $line , $groups)){
                $rdindex = (int)$groups['rd'];
                $rsindex = (int)$groups['rs'];
                $rtindex = (int)$groups['rt'];

                $regused[$rdindex] = 1;
                $regused[$rtindex] = 1;
                $regused[$rsindex] = 1;

                $registers[$rdindex] = (int)$registers[$rsindex] + (int)$registers[$rtindex];
                array_push($this->exe , [$rdindex => $registers[$rdindex]]);
                $this->pc += 1;
                continue;
            }

            if(preg_match($this->sub , $line , $groups)){
                $rdindex = (int)$groups['rd'];
                $rsindex = (int)$groups['rs'];
                $rtindex = (int)$groups['rt'];

                $regused[$rdindex] = 1;
                $regused[$rtindex] = 1;
                $regused[$rsindex] = 1;

                $registers[$rdindex] = (int)$registers[$rsindex] - (int)$registers[$rtindex];
                array_push($this->exe , [$rdindex => $registers[$rdindex]]);
                $this->pc += 1;
                continue;
            }

            if(preg_match($this->slt , $line , $groups)){
                $rdindex = (int)$groups['rd'];
                $rsindex = (int)$groups['rs'];
                $rtindex = (int)$groups['rt'];

                $regused[$rdindex] = 1;
                $regused[$rtindex] = 1;
                $regused[$rsindex] = 1;

                if($registers[$rsindex] < $registers[$rtindex]){
                    $registers[$rdindex] = 1;
                }else{
                    $registers[$rdindex] = 0;
                }
                array_push($this->exe , [$rdindex => $registers[$rdindex]]);
                $this->pc += 1;
                continue;
            }

            if(preg_match($this->or , $line , $groups)){
                $rdindex = (int)$groups['rd'];
                $rsindex = (int)$groups['rs'];
                $rtindex = (int)$groups['rt'];

                $regused[$rdindex] = 1;
                $regused[$rtindex] = 1;
                $regused[$rsindex] = 1;

                $registers[$rdindex] = bindec((decbin($registers[$rsindex]) | decbin($registers[$rtindex])));
                array_push($this->exe , [$rdindex => $registers[$rdindex]]);
                $this->pc += 1;
                continue;
            }

            if(preg_match($this->nand , $line , $groups)){
                $rdindex = (int)$groups['rd'];
                $rsindex = (int)$groups['rs'];
                $rtindex = (int)$groups['rt'];

                $regused[$rdindex] = 1;
                $regused[$rtindex] = 1;
                $regused[$rsindex] = 1;

                $registers[$rdindex] = bindec(!(decbin($registers[$rsindex]) & decbin($registers[$rtindex])));
                array_push($this->exe , [$rdindex => $registers[$rdindex]]);
                $this->pc += 1;
                continue;
            }

            if(preg_match($this->addi , $line , $groups)){
                $imm = (int)$groups['imm'];
                $rsindex = (int)$groups['rs'];
                $rtindex = (int)$groups['rt'];

                $regused[$rtindex] = 1;
                $regused[$rsindex] = 1;

                $registers[$rtindex] = (int)$registers[$rsindex] + (int)$imm;
                array_push($this->exe , [$rtindex => $registers[$rtindex]]);
                $this->pc += 1;
                continue;
            }

            if(preg_match($this->slti , $line , $groups)){
                $imm = (int)$groups['imm'];
                $rsindex = (int)$groups['rs'];
                $rtindex = (int)$groups['rt'];

                $regused[$rtindex] = 1;
                $regused[$rsindex] = 1;

                if($registers[$rsindex] < $imm){
                    $registers[$rtindex] = 1;
                }else{
                    $registers[$rtindex] = 0;
                }
                array_push($this->exe , [$rtindex => $registers[$rtindex]]);
                $this->pc += 1;
                continue;
            }

            if(preg_match($this->ori , $line , $groups)){
                $imm = (int)$groups['imm'];
                $rsindex = (int)$groups['rs'];
                $rtindex = (int)$groups['rt'];

                $regused[$rtindex] = 1;
                $regused[$rsindex] = 1;

                $registers[$rtindex] = bindec((decbin($registers[$rsindex]) | decbin($imm)));
                array_push($this->exe , [$rtindex => $registers[$rtindex]]);
                $this->pc += 1;
                continue;
            }

            if(preg_match($this->lui , $line , $groups) ){
                $imm = (int)$groups['imm'];
                $rtindex = (int)$groups['rt'];

                $regused[$rtindex] = 1;

                $registers[$rtindex] = bindec(decbin($imm) << 16);
                array_push($this->exe , [$rtindex => $registers[$rtindex]]);
                $this->pc += 1;

                continue;
            }

            if(preg_match($this->lw , $line , $groups)){
                if(is_numeric($groups['offset'])){
                    $imm = (int)$groups['offset'];
                }else{
                    $lbl = Label::where('code_id' , $this->code->id)
                        ->where('label' , $groups['offset'])
                        ->first();
                    $imm = (int)$lbl->value;
                    // TODO : handling line support
                }


                $rtindex = (int)$groups['rt'];
                $registers[$rtindex] = (int)$imm;
                array_push($this->exe , [$rtindex => $registers[$rtindex]]);

                $regused[$rtindex] = 1;

                $this->pc += 1;
                continue;
            }

            if(preg_match($this->sw , $line , $groups)){
                if(is_numeric($groups['offset'])){
                    $imm = (int)$groups['offset'];
                }else{
                    $lbl = Label::where('code_id' , $this->code->id)
                        ->where('label' , $groups['offset'])
                        ->first();
                    $imm = (int)$lbl->line;
                    $lbl->value = $registers[$groups['rt']];
                    $lbl->save();
                }

                $regused[$groups['rt']] = 1;
                $regused[$groups['rs']] = 1;

                $this->ram[$registers[$groups['rs']] + $imm] = (int)$registers[$groups['rt']];

                $this->pc += 1;
                continue;
            }

            if(preg_match($this->beq , $line , $groups)){
                if(is_numeric($groups['offset'])){
                    $imm = $groups['offset'];
                }else{
                    $lbl = Label::where('code_id' , $this->code->id)
                        ->where('label' , $groups['offset'])
                        ->first();

                    $imm = $lbl->line;
                }
                $rt = $registers($groups['rt']);
                $rs = $registers($groups['rs']);

                $regused[$groups['rt']] = 1;
                $regused[$groups['rs']] = 1;


                if($rs == $rt){
                    $this->pc = $imm;
                }else{
                    $this->pc += 1;
                }
                continue;
            }

            if(preg_match($this->j , $line , $groups)){
                if(is_numeric($groups['offset'])){
                    $imm = (int)$groups['offset'];
                }else{
                    $lbl = Label::where('code_id' , $this->code->id)
                        ->where('label' , $groups['offset'])
                        ->first();
                    $imm = $lbl->line;
                }
                $this->pc = $imm;
                continue;
            }

            if(preg_match($this->jalr , $line , $groups)){
                if(is_numeric($groups['rs'])){
                    $imm = $registers[$groups['rs']];
                }else{
                    $lbl = Label::where('code_id' , $this->code->id)
                        ->where('label' , $groups['rs'])
                        ->first();
                    $imm = (int)$lbl->line;
                }

                $regused[$groups['rs']] = 1;
                $regused[$groups['rt']] = 1;


                $registers[$groups['rt']] = $this->pc+1;
                $this->pc = $imm;
                continue;
            }

            if(preg_match($this->halt , $line , $groups) ){
                break;
            }

        }
        $counts = array_count_values($regused );
        $this->registerusage = $counts[1]/ 16;
        $this->ramusage = count($this->ram) / 16000;
    }
}


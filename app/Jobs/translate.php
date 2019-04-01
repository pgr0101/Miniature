<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Answer;
use App\Code;
use App\Label;

class translate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

      protected $code;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Code $code)
    {
        $this->code = $code;
        // loading the dictionary here
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Answer $answer)
    {
        // creating the answer by usig the dictionary here
        $answer = new Answer;
        $ans = $this->translate($this->code->code);
        $answer->answer = $ans;
        $code_id = $this->code->id;
        $answer->code_id = $code_id;
        $answer->save();
        // saving the answer of code to answer_id
        $code = Code::where('id' , $code_id)->first();
        $code->answer_id = $answer->id;
        $code->save();
    }

    // some helper methods
    protected function fourBitHelper($decimal){
        $answer = decbin($decimal);
        $len = strlen($answer);
        if($len == 1){
            return "000".$answer;
        }
        if($len == 2){
            return "00".$answer;
        }
        if($len == 3){
            return "0".$answer;
        }
        return $answer;
    }


    // for execution
    protected function zeroExtention($decimal){
        // adding all zero till 32bits
        $imm = "00000000000000000000000000000000";
        $answer = "".decbin($decimal);
        $i = 31;
        for($j = strlen($imm)-1 ; $j >= 0  ; $j--){
            $imm[$i] = $answer[$j];
            $i--;
        }
        return $imm;
    }


    protected function sixteenBitHelper($decimal){
        $imm = "0000000000000000";
        $answer = "".decbin($decimal);
        $i = 15;

        for($j = strlen($answer)-1 ; $j >= 0 ; $j--){
            $imm[$i] = $answer[$j];
            $i--;
        }

        if($decimal < 0){
            for ($j=31; $j >=0 ; $j--) {
                if($imm[$j] == 0){
                    $imm[$j] = 1;
                }else{
                    $imm[$j] = 0;
                }
            }
            $imm = $this->binaryPlusPlus($imm);
        }
        return $imm;

    }

    // for execution
    protected function signedExtention($decimal){
        // making the signed binary value
        $imm = "00000000000000000000000000000000";
        $answer = "".decbin($decimal);
        $i = 31;

        for($j = strlen($answer)-1 ; $j >= 0 ; $j--){
            $imm[$i] = $answer[$j];
            $i--;
        }

        if($decimal < 0){
            for ($j=31; $j >=0 ; $j--) {
                if($imm[$j] == 0){
                    $imm[$j] = 1;
                }else{
                    $imm[$j] = 0;
                }
            }
            $imm = $this->binaryPlusPlus($imm);
        }
        return $imm;

    }

    protected function binaryPlusPlus($imm){
        // TODO adding 1 to binary string
        $i = 31;
        while($i>=0){
            if($imm[$i] == 0){
                $imm[$i] = 1;
                break;
            }else{
                $imm[$i] = 0;
            }
            $i--;
        }
        return $answer;
    }

    public function translate($value){
    // for r type checking if groups 2 , 3 , 4 gte 0 and lt 15 :
        $add  = "/add\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $sub  = "/sub\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $slt  = "/slt\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $nand = "/nand\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $or   = "/or\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        // for i type checking if groups 2 , 3 gte 0 and lt 15 the last one digit :
        $addi = "/addi\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $ori  = "/ori\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $slti = "/slti\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        // exceptions  : // label is just the line number
        // **offset can be label and also be the line number offset in 16bits
        $sw = "/sw\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\d+)/";
        $lw = "/lw\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\d+)/";
        $beq = "/beq\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\d+)/";
        $lui = "/lui\\s+(?P<rt>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $halt = "/halt\\s*/";
        $jalr = "/jalr\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*/";
        $j = "/j\\s+(?P<offset>\\d+)/";
        // label and directives
        // changing label
        $label = "/((?P<label>[a-zA-Z]+[a-zA-Z0-9]{1,14})\\s+:)/";

        $fill = "/((?P<label>[a-zA-Z]+[a-zA-Z0-9]{1,14})\\s+.fill\\s+(?P<value>\\d+))/";
        $space = "/((?P<label>[a-zA-Z]+[a-zA-Z0-9]{1,14})\\s+.fill\\s+(?P<value>\\d+))/";

        $answer = "";
        $i = 0;
        $array =  explode( "\n", $value);

        foreach($array as $line){
            $groups = array();

            if($line == "" || $line == "\n" || (
                preg_match('/\\s+/' , $line && !preg_match('/\\w+/' , $line)))){
                continue;
            }

            if(preg_match($add , $line , $groups)){
                // each of them four bits
                $rd = $this->fourBitHelper($groups['rd']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex("000000000000".$rd.$rt.$rs."0000"."0000");
                $answer.=($temp."\n");
                 continue;   
            }

            if(preg_match($sub , $line , $groups)){
                // each of them four bits
                $rd = $this->fourBitHelper($groups['rd']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex("000000000000".$rd.$rt.$rs."0001"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($slt , $line , $groups)){
                // each of them four bits
                $rd = $this->fourBitHelper($groups['rd']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex("000000000000".$rd.$rt.$rs."0010"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($or , $line , $groups)){
                // each of them four bits
                $rd = $this->fourBitHelper($groups['rd']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex("000000000000".$rd.$rt.$rs."0011"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($nand , $line , $groups)){
                // each of them four bits
                $rd = $this->fourBitHelper($groups['rd']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex("000000000000".$rd.$rt.$rs."0100"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($addi , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex($imm.$rt.$rs."0101"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($slti , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex($imm.$rt.$rs."0110"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($ori , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex($imm.$rt.$rs."0111"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($lui , $line , $groups) ){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $temp = bin2hex($imm.$rt."0000"."1000"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($lw , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = "";
                if(is_numeric($groups['offset'])){
                    $imm = $this->sixteenBitHelper($groups['offset']);
                }else{
                    $lbl = Label::where('code_id' , $this->code->id)
                                ->where('label' , $groups['label'])
                                ->first();
                    $imm = $this->sixteenBitHelper($lbl->line);
                }
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex($imm.$rt.$rs."1001"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($sw , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = "";
                if(is_numeric($groups['offset'])){
                    $imm = $this->sixteenBitHelper($groups['offset']);
                }else{
                    $lbl = Label::where('code_id' , $this->code->id)
                                ->where('label' , $groups['label'])
                                ->first();
                    $imm = $this->sixteenBitHelper($lbl->line);
                }
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex($imm.$rt.$rs."1010"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($beq , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = "";
                if(is_numeric($groups['offset'])){
                    $imm = $this->sixteenBitHelper($groups['offset']);
                }else{
                    $lbl = Label::where('code_id' , $this->code->id)
                                ->where('label' , $groups['label'])
                                ->first();
                    $imm = $this->sixteenBitHelper($lbl->line);
                }
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex($imm.$rt.$rs."1011"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($j , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = "";
                if(is_numeric($groups['offset'])){
                    $imm = $this->sixteenBitHelper($groups['offset']);
                }else{
                    $lbl = Label::where('code_id' , $this->code->id)
                                ->where('label' , $groups['label'])
                                ->first();
                    $imm = $this->sixteenBitHelper($lbl->line);
                }
                $temp = bin2hex($imm."00000000"."1101"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($jalr , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bin2hex("0000000000000000".$rt.$rs."1100"."0000");
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($halt , $line , $groups) ){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $temp = bin2hex("000000000000000000000000"."1110"."0000");
                $answer.=($temp."\n");
                continue;
            }


            // work with database
            if(preg_match($space , $line , $froups)){
                // address f first and making space with 0 value for label value
                $value = $groups['value'];
                for ($i=0; $i < $value; $i++) {
                    $answer.=("0\n");
                }
                continue;
            }

            // think more
            if(preg_match($label , $line)){
                // just label name
                // adding the command
                continue;
            }

            if(preg_match($fill , $line , $groups)){
                // if it has value put the value in 32 bits
                $temp = $this->signedExtention($groups['value']);
                $answer.=($temp."\n");
                continue;
            }

            $i++;
        }

        return $answer;
    }


}

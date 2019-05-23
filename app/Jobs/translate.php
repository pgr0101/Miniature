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

    // Helpers methods :

    // bindec both negative and positive numbers
    function bindec32($bin){
        if (strlen($bin) == 32 && $bin[0] == '1') {
            for ($i = 0; $i < 32; $i++) {
                $bin[$i] = $bin[$i] == '1' ? '0' : '1';
            }

            return (bindec($bin) + 1) * -1;
        }
        return bindec($bin);
    }

    function bindec16($bin){
        if (strlen($bin) == 16 && $bin[0] == '1') {
            for ($i = 0; $i < 16; $i++) {
                $bin[$i] = $bin[$i] == '1' ? '0' : '1';
            }

            return (bindec($bin) + 1) * -1;
        }
        return bindec($bin);
    }

    protected function fourBitHelper($decimal){
        $answer = decbin($decimal);
        $len = strlen($answer);
        switch ($len) {
            case 1:{
                return "000".$answer;
            }break;
            case 2:{
                return "00".$answer;
            }break;
            case 3:{
                return "0".$answer;
            }break;
            case 4:{
                return $answer;
            }break;
        }
        return $answer;
    }

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

		if($decimal < 0){
            $imm = decbin($decimal);
            $imm = substr($imm,-16,16);
        }else{
            $imm = decbin($decimal);
            $temp = "0000000000000000";
            $j = 15;
            for($i = strlen($imm)-1 ; $i >= 0  ; $i--){
                $temp[$j] = $imm[$i];
                $j--;
            }
            $imm = $temp;
        }
        return $imm;
    }

    protected function signedExtention($decimal){
        // making the signed binary value
		if($decimal < 0){
            $imm = decbin($decimal);
        }else{
            $imm = decbin($decimal);
            $temp = "00000000000000000000000000000000";
            $j = 31;
            for($i = strlen($imm)-1 ; $i >= 0  ; $i--){
                $temp[$j] = $imm[$i];
                $j--;
            }
            $imm = $temp;
        }
        return $imm;
    }


    public function translate($value){

        $add  = "/add\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $sub  = "/sub\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $slt  = "/slt\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $nand = "/nand\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $or   = "/or\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $addi = "/addi\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $ori  = "/ori\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $slti = "/slti\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $sw = "/sw\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\w+)/";
        $lw = "/lw\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\w+)/";
        $beq = "/beq\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\w+)/";
        $lui = "/lui\\s+(?P<rt>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $halt = "/halt\\s*/";
        $jalr = "/jalr\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*/";
        $j = "/j\\s+(?P<offset>\\w+)/";

        $fill = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>\\d+))/";
        $fillneg = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>[-]{1}\\d+))/";
        $fill1 = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>\\w+))/";

        $space = "/((?P<label>\\w{1,16})\\s+.space\\s+(?P<value>\\d+))/";
        $comment = "/\\s*#(\\S*|\\s*)*/";


        $addineg = "/addi\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>-\\d+)/";
        $orineg  = "/ori\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>-\\d+)/";
        $sltineg = "/slti\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>-\\d+)/";
        $luineg = "/lui\\s+(?P<rt>\\d+)\\s*,\\s*(?P<imm>-\\d+)/";

        $answer = "";
        $i = -1;
        $array =  explode( "\n", $value);

        foreach($array as $line){
            $groups = array();

            if(preg_match($comment , $line)){
                continue;
            }

            $i++;

            if($line == "" || $line == "\n" || (
                preg_match('/\\s+/' , $line && !preg_match('/\\w+/' , $line)))){
                continue;
            }

            if(preg_match($add , $line , $groups)){
                // each of them four bits
                $rd = $this->fourBitHelper($groups['rd']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0000".$rs.$rt.$rd."000000000000");
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                 continue;
            }

            if(preg_match($sub , $line , $groups)){
                // each of them four bits
                $rd = $this->fourBitHelper($groups['rd']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0001".$rs.$rt.$rd."000000000000");
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($slt , $line , $groups)){
                // each of them four bits
                $rd = $this->fourBitHelper($groups['rd']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0010".$rs.$rt.$rd."000000000000");
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($or , $line , $groups)){
                // each of them four bits
                $rd = $this->fourBitHelper($groups['rd']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0011".$rs.$rt.$rd."000000000000");
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($nand , $line , $groups)){
                // each of them four bits
                $rd = $this->fourBitHelper($groups['rd']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0100".$rs.$rt.$rd."000000000000");
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($addi , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0101".$rs.$rt.$imm);
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($slti , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0110".$rs.$rt.$imm);
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($ori , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0111".$rs.$rt.$imm);
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($lui , $line , $groups) ){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $temp = bindec("0000"."1000"."0000".$rt.$imm);
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }



            if(preg_match($addineg , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0101".$rs.$rt.$imm);
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($sltineg , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0110".$rs.$rt.$imm);
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($orineg , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."0111".$rs.$rt.$imm);
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($luineg , $line , $groups) ){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $imm = $this->sixteenBitHelper($groups['imm']);
                $rt = $this->fourBitHelper($groups['rt']);
                $temp = bindec("0000"."1000"."0000".$rt.$imm);
                //$temp = dechex($temp);
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
                                ->where('label' , $groups['offset'])
                                ->first();
                    $val = $lbl->line;
                    $imm = $this->sixteenBitHelper($val);
                }
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."1001".$rs.$rt.$imm);
                //$temp = dechex($temp);
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
                                ->where('label' , $groups['offset'])
                                ->first();
                    $val = $lbl->line;
                    $imm = $this->sixteenBitHelper($val);
                }
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."1010".$rs.$rt.$imm);
                //$temp = dechex($temp);
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
                                ->where('label' , $groups['offset'])
                                ->first();

                $val = $lbl->line - $i - 1;
                $imm = $this->sixteenBitHelper($val);
                }
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."1011".$rs.$rt.$imm);
                //$temp = dechex($temp);
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
                                ->where('label' , $groups['offset'])
                                ->first();

                    $val = $lbl->line;
                    $imm = $this->sixteenBitHelper($val);
                }
                $temp = bindec("0000"."1101"."00000000".$imm);
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($jalr , $line , $groups)){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $rt = $this->fourBitHelper($groups['rt']);
                $rs = $this->fourBitHelper($groups['rs']);
                $temp = bindec("0000"."1100".$rs.$rt."0000000000000000");
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($halt , $line , $groups) ){
                // imm 16 bits , rt,rs 8 bits , opcode 4 bits , 4 bit zero
                $temp = bindec("0000"."1110"."000000000000000000000000");
                //$temp = dechex($temp);
                $answer.=($temp."\n");
                continue;
            }

            if(preg_match($space , $line , $groups)){
                // address f first and making space with 0 value for label value
                $val = $groups['value'];
                for ($i=0; $i < $val; $i++) {
                    $answer.=("0\n");
                }
                continue;
            }

            if(preg_match($fill , $line , $groups)){
                // if it has value put the value in 32 bits
                $temp = $this->signedExtention($groups['value']);
                $lblt = Label::where('code_id' , $this->code->id)
                             ->where('label' , $groups['label'])
                             ->first();
                $answer.=($lblt->value."\n");
                continue;
            }

            if(preg_match($fill1 , $line , $groups)){
                // if it has value put the value in 32 bits
                $lbl = Label::where('label' , $groups['label'])
                            ->where('code_id' , $this->code->id)
                            ->first();
                $answer.=($lbl->value."\n");
                continue;
            }

            if(preg_match($fillneg , $line , $groups)){
                // if it has value put the value in 32 bits
                $lbl = Label::where('label' , $groups['label'])
                            ->where('code_id' , $this->code->id)
                            ->first();
                $answer.=($lbl->value."\n");
                continue;
            }

        }

        return $answer;
    }


}

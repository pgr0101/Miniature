<?php

namespace App\Rules;

//require_once base_path('vendor/spatie/regex/src/Regex.php');
use Illuminate\Contracts\Validation\Rule;
use App\Error;
use App\Label;
//use Spatie\Regex\Regex;

class syntax implements Rule
{

    protected $code_id;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($code_id)
    {
        //
        $this->code_id = $code_id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {

        // for r type checking if groups 2 , 3 , 4 gte 0 and lt 15 :
        $add  = "/add\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $sub  = "/sub\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $slt  = "/slt\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $nand = "/nand\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $or   = "/or\\s+(?P<rd>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<rt>\\d+)/";
        $addi = "/addi\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $ori  = "/ori\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $slti = "/slti\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $sw = "/sw\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\d+)/";
        $lw = "/lw\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\d+)/";
        $beq = "/beq\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\d+)/";
        $lui = "/lui\\s+(?P<rt>\\d+)\\s*,\\s*(?P<imm>\\d+)/";
        $halt = "/halt\\s*/";
        $jalr = "/jalr\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*/";
        $j = "/j\\s+(?P<offset>\\d+)/";
        $j1 = "/j\\s+(?P<offset>\\w+)/";
        $sw1 = "/sw\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\w+)/";
        $lw1 = "/lw\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\w+)/";
        $beq1 = "/beq\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<offset>\\w+)/";
        $addt  = "add\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $subt  = "sub\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $sltt  = "slt\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $nandt = "nand\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $ort   = "or\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $addit = "addi\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $orit  = "ori\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $sltit = "slti\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $swt = "sw\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $lwt = "lw\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $beqt = "beq\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $luit = "lui\\s+(\\d+)\\s*,\\s*(\\d+)";
        $haltt = "halt\\s*";
        $jalrt = "jalr\\s+(\\d+)\\s*,\\s*(\\d+)\\s*";
        $jt = "j\\s+(\\d+)";
        $addinegt = "addi\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(-\\d+)";
        $orinegt  = "ori\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(-\\d+)";
        $sltinegt = "slti\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(-\\d+)";
        $luinegt = "lui\\s+(\\d+)\\s*,\\s*(-\\d+)";

        $label = "/(?P<label>\\w{1,16})\\s+($addinegt|$orinegt|$sltinegt|$luinegt|$addt|$subt|$sltt|$nandt|$ort|$addit|$orit|$sltit|$swt|$lwt|$beqt|$luit|$haltt|$jt|$jalrt)/";
        $fill = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>\\d+))/";
        $fillneg = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>[-]{1}\\d+))/";
        $fill1 = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>\\w+))/";
        $space = "/((?P<label>\\w{1,16})\\s+.space\\s+(?P<value>\\d+))/";

        $addineg = "/addi\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>-\\d+)/";
        $orineg  = "/ori\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>-\\d+)/";
        $sltineg = "/slti\\s+(?P<rt>\\d+)\\s*,\\s*(?P<rs>\\d+)\\s*,\\s*(?P<imm>-\\d+)/";
        $luineg = "/lui\\s+(?P<rt>\\d+)\\s*,\\s*(?P<imm>-\\d+)/";


        $comment = "/\\s*#(\\S*|\\s*)*/";


        $answer = true;
        $i = 1;
        $array =  explode( "\n", $value);

        foreach($array as $line){

            $groups = array();

            if($line == "" || $line == "\n" ||
            (preg_match('/\\s+/' , $line) && !preg_match('/\\w+/' , $line))){
                $i++;
                continue;
            }

            if(preg_match($comment , $line)){
                $i++;
                continue;
            }

            if(preg_match($j1 , $line , $groups) || preg_match($sw1 , $line , $groups) ||
                preg_match($beq1 , $line , $groups) || preg_match($lw1 , $line , $groups) ){
                $lbl = Label::where('label' , $groups['offset'])
                            ->where('code_id' , $this->code_id)
                            ->first();
                if($lbl != null){
                    $i++;
                    continue;
                }
            }

            if(preg_match($fill1 , $line , $groups)){
                $i++;
                continue;
            }

            // label and directives
            if(preg_match($label , $line) || preg_match($space , $line)
                || preg_match($fill , $line) || preg_match($fillneg , $line)){
                $i++;
                continue;
            }

            // R-Type
            if(preg_match($add , $line , $groups) || preg_match($sub , $line , $groups) ||
                preg_match($nand , $line , $groups) || preg_match($slt , $line , $groups) ||
                preg_match($or , $line , $groups)){
                // checking the groups then break
                if( $groups['rd'] != null && $groups['rs'] != null && $groups['rt'] != null &&
                    $groups['rd'] < 16 && $groups['rd'] > -1 &&
                    $groups['rs'] < 16 && $groups['rs'] > -1 &&
                    $groups['rt'] < 16 && $groups['rt'] > -1 &&
                    $groups['rd'] != 0){
                        $i++;
                        continue;
                }
            }
            // I-Type
            if(preg_match($addi , $line , $groups) || preg_match($slti , $line , $groups) ||
            preg_match($ori , $line , $groups)){
                if( $groups['imm'] != null && $groups['rs'] != null && $groups['rt'] != null &&
                    $groups['imm'] < 65536  && $groups['imm'] > -65536  &&
                    $groups['rs'] < 16 && $groups['rs'] > -1 &&
                    $groups['rt'] < 16 && $groups['rt'] > -1 &&
                    $groups['rt'] != 0){
                        $i++;
                        continue;
                }
            }

            // I-Type
            if(preg_match($addineg , $line , $groups) || preg_match($sltineg , $line , $groups) ||
                preg_match($orineg , $line , $groups)){
                if( $groups['imm'] != null && $groups['rs'] != null && $groups['rt'] != null &&
                    $groups['imm'] < 65536  && $groups['imm'] > -65536  &&
                    $groups['rs'] < 16 && $groups['rs'] > -1 &&
                    $groups['rt'] < 16 && $groups['rt'] > -1 &&
                    $groups['rt'] != 0){
                    $i++;
                    continue;
                }
            }

            // lui
            if(preg_match($lui , $line , $groups) ){
            // checking the groups then break
                if( $groups['imm'] != null && $groups['rt'] != null &&
                    $groups['rt'] < 16 && $groups['rt'] > -1 &&
                    $groups['imm'] < 65536 && $groups['imm'] > -65536
                    && $groups['rt'] != 0){
                        $i++;
                        continue;
                }
            }

            if(preg_match($luineg , $line , $groups) ){
                // checking the groups then break
                if( $groups['imm'] != null && $groups['rt'] != null &&
                    $groups['rt'] < 16 && $groups['rt'] > -1 &&
                    $groups['imm'] < 65536 && $groups['imm'] > -65536
                    && $groups['rt'] != 0){
                    $i++;
                    continue;
                }
            }

            if(preg_match($halt , $line , $groups) ){
                // checking the groups then break
                $i++;
                continue;
            }



            // ** with offset
            if(preg_match($sw , $line , $groups) || preg_match($lw , $line , $groups) ||
                preg_match($beq , $line , $groups)){
                // checking the groups then break
                if( $groups['offset'] != null&& $groups['rs'] != null&& $groups['rt'] != null &&
                    $groups['offset'] < 65536 && $groups['offset'] > -65536 &&
                    $groups['rs'] < 16 && $groups['rs'] > -1 &&
                    $groups['rt'] < 16 && $groups['rt'] > -1 ){
                        $i++;
                        continue;
                }
            }

            if(preg_match($j , $line , $groups)){
                // checking the groups then break
                if( $groups['offset'] != null &&
                    $groups['offset'] < 65536 && $groups['offset'] > -65536){
                        $i++;
                        continue;
                }
            }

            if(preg_match($jalr , $line , $groups)){
                // checking the groups then break
                if( $groups['rs'] < 16 && $groups['rs'] > -1 &&
                    $groups['rt'] < 16 && $groups['rt'] > -1 &&
                    $groups['rt'] != 0){
                        $i++;
                        continue;
                }
            }

            $error = new Error;
            $error->error = "there is a problem on line : " . $i;
            $error->code_id = $this->code_id;
            $error->save();
            $answer = false;
            $i++;
        }

        return $answer;
    }


    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'syntax error .';
    }
}

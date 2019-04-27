<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Error;
use App\Label as Labelt;

class label implements Rule
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

        $array =  explode("\n" , $value);

        $add  = "add\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $sub  = "sub\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $slt  = "slt\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $nand = "nand\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $or   = "or\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $addi = "addi\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $ori  = "ori\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $slti = "slti\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $sw = "sw\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $lw = "lw\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $beq = "beq\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $lui = "lui\\s+(\\d+)\\s*,\\s*(\\d+)";
        $halt = "halt\\s*";
        $jalr = "jalr\\s+(\\d+)\\s*,\\s*(\\d+)\\s*";
        $j = "j\\s+(\\d+)";
        $j1 = "j\\s+(?P<offset>\\w+)";
        $sw1 = "sw\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\w+)";
        $lw1 = "lw\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\w+)";
        $beq1 = "beq\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\w+)";


        $label = "/\\s*(?P<label>\\w+)\\s+($add|$sub|$slt|$nand|$or|$addi|$ori|$slti|$sw|$lw|$beq|$lui|$halt|$j|$jalr)/";
        $lbltest = "\\s+($add|$sub|$slt|$nand|$or|$addi|$ori|$slti|$sw|$lw|$beq|$lui|$halt|$j|$jalr)";
        $codet = "/($add|$sub|$slt|$nand|$or|$addi|$ori|$slti|$sw|$lw|$beq|$lui|$halt|$j|$jalr|$j1|$sw1|$lw1|$beq1)/";
        $fill = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>\\d+))/";
        $fillneg = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>[-]{1}\\d+))/";
        $fill1 = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>[a-zA-Z]{1}[a-zA-Z0-9]+))/";
        $space = "/((?P<label>\\w{1,16})\\s+.fill\\s+(?P<value>\\d+))/";

        $comment = "/\\s*#(\\S*|\\s*)*/";
        $newline = "/\n/";

        $answer = true;
        $i = 1;
        $lbls = array();
        foreach($array as $line){
            // scope problem have to change if rules

            $groups = array();

            if(preg_match($label , $line , $groups)){
                $lbl = "/".$groups['label'].$lbltest."/";
                if(preg_match_all($lbl, $value) < 2){
                    $lbl = new Labelt;
                    $lbl->label = $groups['label'];
                    $lbl->line = $i-1;
                    $lbl->code_id = $this->code_id;
                    $lbl->save();
                    $i++;
                    continue;
                }else{
                    $error = new Error;
                    $error->error = "problem with label definition on line : " . $i;
                    $error->code_id = $this->code_id;
                    $error->save();
                    $answer = false;
                    $i++;
                    continue;
                }
            }

            if(preg_match($fill1 , $line , $groups) && strpos($line,'.fill')){
                $i++;
                continue;
            }

            if((!preg_match($fillneg , $line , $groups) && !preg_match($fill , $line , $groups)) && strpos($line,'.fill')){
                $error = new Error;
                $error->error = "problem with label definition on line : " . $i;
                $error->code_id = $this->code_id;
                $error->save();
                $answer = false;
                $i++;
                continue;
            }else if(preg_match($fillneg , $line , $groups)){
                $lbl = new Labelt;
                $lbl->label = $groups['label'];
                $lbl->line = $i-1;
                $lbl->value = $groups['value'];
                $lbl->code_id = $this->code_id;
                $lbl->save();
                $i++;
                continue;
            }else if(preg_match($fill , $line , $groups)){
                $lbl = new Labelt;
                $lbl->label = $groups['label'];
                $lbl->line = $i-1;
                $lbl->value = $groups['value'];
                $lbl->code_id = $this->code_id;
                $lbl->save();
                $i++;
                continue;
            }

            if(!preg_match($space , $line , $groups) && strpos($line,'.space')){
                $error = new Error;
                $error->error = "problem with label definition on line : " . $i;
                $error->code_id = $this->code_id;
                $error->save();
                $answer = false;
                $i++;
                continue;
            }else if(preg_match($space , $line , $groups)){
                $lbl = new Labelt;
                $lbl->label = $groups['label'];
                $lbl->line = $i-1;
                $lbl->value = $groups['value'];
                $lbl->code_id = $this->code_id;
                $lbl->save();
                $i++;
                continue;
            }


            if(preg_match($codet , $line , $groups)){
                $i++;
                continue;
            }

            if(preg_match($comment , $line)){
                continue;
            }

            if($line == "" || $line == "\n" ||
            (preg_match('/\\s+/' , $line) && !preg_match('/\\w+/' , $line))){
                continue;
            }


            $error = new Error;
            $error->error = "problem with label definition on line : " . $i;
            $error->code_id = $this->code_id;
            $error->save();
            $answer = false;
            $i++;
        }

        $i = 0;
        foreach($array as $line){
            if(preg_match($fill1 , $line , $groups)){
                $lbl = new Labelt;
                $lbl->label = $groups['label'];
                $lbl->line = $i;
                $lblt = Labelt::where('label' , $groups['value'])
                              ->where('code_id' , $this->code_id)
                              ->where('line' , '!=' , null)
                              ->first();
                if($lblt){
                    $lbl->value = $lblt->line;
                    $lbl->code_id = $this->code_id;
                    $lbl->save();
                }else{
                    $error = new Error;
                    $error->error = "problem with label definition on line : " . ($i+1);
                    $error->code_id = $this->code_id;
                    $error->save();
                    $answer = false;
                }
                $i++;
                continue;
            }
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
        return 'label or directive error';
    }
}

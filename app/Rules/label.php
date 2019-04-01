<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Error;

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

        // for i type checking if groups 2 , 3 gte 0 and lt 15 the last one digit :
        $addi = "addi\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $ori  = "ori\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $slti = "slti\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";


        // exceptions  : // label is just the line number
        // **offset can be label and also be the line number offset in 16bits
        $sw = "sw\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $lw = "lw\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $beq = "beq\\s+(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)";
        $lui = "lui\\s+(\\d+)\\s*,\\s*(\\d+)";

        $halt = "halt\\s*";
        $jalr = "jalr\\s+(\\d+)\\s*,\\s*(\\d+)\\s*";
        $j = "j\\s+(\\d+)";



        $label = "/(?P<label>[a-zA-Z]{1}[a-zA-Z0-9]{1,14})\\s+($add|$sub|$slt|$nand|$or|$addi|$ori|$slti|$sw|$lw|$beq|$lui|$halt|$j|$jalr)/";
        $fill = "/(?P<label>[a-zA-Z]{1}[a-zA-Z0-9]{1,14})\\s+\.fill\\s+(?P<value>\\d+)/";
        $space = "/(?P<label>[a-zA-Z]{1}[a-zA-Z0-9]{1,14})\\s+\.fill\\s+(?P<value>\\d+)/";

        // directives are used for run and labels just have the line number
        // in that place we can store code or value by .fill .space or ....
        // for space we have to check space for execution and checking the memory in correct way

        $answer = true;
        $i = 1;
        foreach($array as $line){
            $groups = array();

            if(preg_match($label , $line , $groups)){
                $lbl = "/".$groups['label']."\\s*"."/";
                if(preg_match_all($lbl, $value) < 2){
                    $i++;
                    continue;
                }else{
                    $error = new Error;
                    $error->error = "there is a problem on line : " . $i;
                    $error->code_id = $this->code_id;
                    $error->save();
                    $answer = false;
                    $i++;
                }
            }

            if(!preg_match($fill , $line , $groups) && strpos($line,'.fill')){
                $error = new Error;
                $error->error = "there is a problem on line : " . $i;
                $error->code_id = $this->code_id;
                $error->save();
                $answer = false;
                $i++;
                continue;
            }

            if(!preg_match($space , $line , $groups) && strpos($line,'.space')){
                $error = new Error;
                $error->error = "there is a problem on line : " . $i;
                $error->code_id = $this->code_id;
                $error->save();
                $answer = false;
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

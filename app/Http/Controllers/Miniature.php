<?php

namespace App\Http\Controllers;

use App\Jobs\Execute;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;
use Mockery\Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Queue;
use App\Code;
use App\Answer;
use App\Error;
use App\Jobs\translate;
use App\Rules\label as labelval;
use App\Rules\syntax;
use App\Execute as ExeModel;


class Miniature extends Controller
{
    //

    /*
        need a json with just code parameter
        returns a json (status msg errors and the code)
     */
    public function handleCode(Request $req){

        // creating code and adding to user
        $user = User::where('username' , $req->input('username'))->first();
        $code = new Code;
        $code->title = $req->input('title');
        $code->code = $req->input('code');
        $code->user_id = $user->id;
        $str = "";
        $code->save();
        // making the validator for checking
        $validator = Validator::make($req->all(), [
            'code' => [new labelval($code->id) , new syntax($code->id)],
            'title' => 'required',
        ]);

        if(!$validator->fails()){
            // answer here
            translate::dispatch($code);
            Execute::dispatch($code);

            // making the code_id relation manual
            if($user->code_id == null || $user->code_id == ""){
                $str = $code->id;
            }else{
                $str = $user->code_id . " " . (string)$code->id;
            }

            $user->code_id = $str;
            $user->save();

            // response of the handling
            return response()->json([
                'status' => 200 ,
                'msg' => 'data recieved successfuly you can take the aswer in next request' ,
                'code' => $code ,
            ]);
        }else{
            $errors = Error::where('code_id' , $code->id)->get();
            return response()->json([
                'status' => 406 ,
                'msg' => $validator->messages() ,
                'errors' => $errors
            ]);
        }

    }

    /*
        returning the answer of the code as json
    */
    public function getTheAnswer(Request $req){
        $answer = Answer::where('code_id' , $req->route('id'))->first();
        $code = Code::where('id' , $req->route('id'))->first();
        if($answer == null){
            return response()->json([
                'status' => 406 ,
                'msg' => 'answer did not found'
            ]);
        }
        return response()->json([
            'status' => 200 ,
            'msg' => "found the answer" ,
            'answer' => $answer ,
            'code' => $code
        ]);
    }

    /*
        return all user's upload
    */
    public function getAllUploads(Request $req){
        // first check my Auth middleware
        // then take all the codes from the database
        // we can use user->code_id and split with space
        $user = User::where('username', $req->route('username'))->first();
        $codes = Code::where('user_id' , $user->id)
                      ->where('answer_id' , '!=' ,null)
                      ->where('execute_id' , '!=' , null)
                      ->get();
        return response()->json([
            'status' => 200 ,
            'msg' => "found some posts" ,
            'codes' => $codes
        ]);
    }

    /*
        creating new user
        needs a json with username and password
        returns json (status , msg)
    */
    public function signUp(Request $req){
        // TODO signing up the user
        $validator = Validator::make($req->all() , [
            'username' => 'required|unique:users|min:3' ,
            'password' => 'required|min:8'
        ]);

        if(!$validator->fails()){
            $user = new User;
            $user->username = $req->input('username');
            $hash = Hash::make($req->input('password'));
            $user->password = $hash;
            $user->save();
            return response()->json([
                'status' => 200 ,
                'msg' => "user saved"
            ]);
        }

        return response()->json([
            'status' => 406 ,
            'msg' => "bad input data" ,
            'errors' => $validator->messages()
        ]);

    }

    /*
        logging in and save some data in front end
        needs a json with username and password
        returns response json (status , msg) , also set some cookies
    */
    public function logIn(Request $req){
        // TODO logging in and setting the cookies
        $user = User::where('username' , $req->input('username'))->first();
        if($user){
            $flag = Hash::check($req->input('password'), $user->password);
            if($flag){
                return response()->json([
                    'status' => 200 ,
                    'msg' => 'logged in'
                ]);

            }else{
                return response()->json([
                    'status' => 406 ,
                    'msg' => 'data is not acceptale'
                ]);
            }
        }else{
            return response()->json([
                'status' => 400 ,
                'msg' => 'bad request not found'
            ]);
        }
    }

    /**
     * returning the answer of execution the most important part is
     * the part of register changes it should be displayed by delay
     * in the front-end part the user can set the delayed time
    */
    public function getExecution(Request $req)
    {
        $code = Code::where('id' , $req->route('id'))->first();
        Execute::dispatch($code);
        $exe = ExeModel::where('code_id' , $req->route('id'))
                        ->first();
        if($exe){
            $data = [
                'memoryusage' => $exe->memoryusage ,
                'registerusage' => $exe->registerusage ,
                'registerchanges' => unserialize($exe->exe)
            ];
            return response()->json([
                'status' => 200 ,
                'msg' => 'execution answer' ,
                'data' => $data
            ]);
        }else{
            response()->json([
               'msg' => "not found answer"
            ]);
        }
    }
}

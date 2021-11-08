<?php

namespace App\Containers\User\API\V1\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(){
        $user = User::all();
        return response()->json($user, 200);
    }
}

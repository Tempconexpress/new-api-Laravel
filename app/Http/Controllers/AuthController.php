<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\Models\UserMaster;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Facades\Tenancy;
 
class AuthController extends Controller
{
    // Login API
    // public function login(Request $request)
    // {
    //     // Validate the request input
    //     $validator = Validator::make($request->all(), [
    //         'login_id' => 'required|string',
    //         'password' => 'required|string|min:6',
    //     ]);
 
    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }
 
    //     // Attempt to find the user
    //     $user = UserMaster::where('login_id', $request->login_id)->first();
    //     // print_r($user->password);
    //     // print_r($request->password);die;
    //     //for hashed passwords
        
    //     //for unhashed passwords
    //     // if (!$user || $user->password !== $request->password) {
    //     //     return response()->json(['error' => 'Invalid login credentials'], 401);
    //     // }
    //     // Generate JWT Token
    //     $token = JWTAuth::fromUser($user);
    //     if (!$user || !Hash::check($request->password, $user->password)) {
    //         return response()->json(['error' => 'Invalid login credentials'], 401);
    //     }else{
    //         $user_id = Session::put('user_id', $user['user_id']);
    //         // In AuthController after setting session
    //     Session::put('user_id',$user['user_id']);  // Assuming $user is your authenticated user
    //     \Log::info('Session user_id:', [Session::get('user_id')]);

    //     return response()->json([
    //         'message' => 'Login successful',
    //         // 'user' => $user,
    //         'token' => $token,
    //         'status'=>200,
    //         'user_id'=> Session::get('user_id', $user_id)
           
    //     ]);
      
       
       
        
    //  }
    // }
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        // Switch to the tenant
        $tenant = Tenancy::find($validated['tenant_id']);
        if (!$tenant) {
            return response()->json(['error' => 'Invalid tenant ID'], 404);
        }

        tenancy()->initialize($tenant);

        // Attempt login in the tenant's database
        if (!Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
        ]);
    }
    public function sales_rep(){
        $sales_rep = UserMaster::where('user_level', 'super')->get();
        if($sales_rep){
            return response()->json([
                'status' => 200,
                'msg' => "Sales Representative fetched",
                'data' =>$sales_rep
            ]);
        }else{
            return response()->json([
                'status' => 201,
                'msg' => "Sales Representative  Not fetched",
                'data' =>[]
            ]);
        }
    }

    public function user_details(Request $request) // Use lowercase "r"
{
    $users = UserMaster::where('email_id', $request->input('email'))
                       ->where('active', '1')
                       ->get();

    if ($users->isNotEmpty()) { // Use isNotEmpty() instead of checking truthiness
        return response()->json([
            'status' => 200,
            'msg' => "Users fetched",
            'data' => $users
        ]);
    } else {
        return response()->json([
            'status' => 201,
            'msg' => "Data not fetched",
            'data' => []
        ]);
    }
}

    public function user_master(){
        $users = UserMaster::where('active','1')->get();
        if($users){
            return response()->json([
                'status' => 200,
                'msg' => "All Users fetched",
                'data' =>$users
            ]);
        }else{
            return response()->json([
                'status' => 201,
                'msg' => "Data  Not fetched",
                'data' =>[]
            ]);
        }
    }

    public function users(){
        $users = UserMaster::get();
        if($users){
            return response()->json([
                'status' => 200,
                'msg' => "users fetched",
                // 'data' =>$sales_rep
            ]);
        }else{
            return response()->json([
                'status' => 201,
                'msg' => "Users  Not fetched",
                'data' =>[]
            ]);
        }
    }

    // Logout API Pending..........
    public function logout(Request $request)
    {
        try {
            // Invalidate the token to log the user out
            JWTAuth::invalidate(JWTAuth::getToken());
 
            return response()->json([
                'message' => 'Logout successful'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to logout, please try again.'
            ], 500);
        }
    }
 
    // Get the authenticated user's details (optional, if you want a user profile route)
    public function profile()
    {
        return response()->json(JWTAuth::user());
    }
}
 
 
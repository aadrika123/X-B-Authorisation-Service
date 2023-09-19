<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePassRequest;
use App\Http\Requests\Auth\OtpChangePass;
use App\MicroServices\DocUpload;
use App\Models\Auth\ActiveCitizen;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CitizenController extends Controller
{

    /**
     * | Citizen Register
     */
    public function citizenRegister(Request $request)
    {
        $request->validate([
            'name'     => 'required',
            'mobile'   => 'required|numeric|digits:10',
            'password' => [
                'required',
                'min:6',
                'max:255',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/'  // must contain a special character
            ],
        ]);

        try {

            $mCitizen = new ActiveCitizen();
            $citizens = $mCitizen->getCitizenByMobile($request->mobile);
            if (isset($citizens))
                return responseMsgs(false, "This Mobile No is Already Existing", "");

            DB::beginTransaction();
            $id = $mCitizen->citizenRegister($mCitizen, $request);        //Citizen save in model
            $this->docUpload($request, $id);

            DB::commit();
            return responseMsgs(true, 'Succesfully Registered', "", '', "1.0", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", '', "1.0", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Doc upload
     */
    public function docUpload($request, $id)
    {
        $docUpload = new DocUpload;
        $imageRelativePath = 'Uploads/Citizen/' . $id;
        ActiveCitizen::where('id', $id)
            ->update([
                'relative_path' => $imageRelativePath . '/',
            ]);

        if ($request->photo) {
            $filename = 'photo';
            $document = $request->photo;
            $imageName = $docUpload->upload($filename, $document, $imageRelativePath);

            ActiveCitizen::where('id', $id)
                ->update([
                    'profile_photo' => $imageName,
                ]);
        }

        if (isset($request->aadharDoc)) {
            $filename = 'aadharDoc';
            $document = $request->aadharDoc;
            $imageName = $docUpload->upload($filename, $document, $imageRelativePath);

            ActiveCitizen::where('id', $id)
                ->update([
                    'aadhar_doc' => $imageName,
                ]);
        }

        if ($request->speciallyAbledDoc) {
            $filename = 'speciallyAbled';
            $document = $request->speciallyAbledDoc;
            $imageName = $docUpload->upload($filename, $document, $imageRelativePath);

            ActiveCitizen::where('id', $id)
                ->update([
                    'specially_abled_doc' => $imageName,
                ]);
        }

        if ($request->armedForceDoc) {
            $filename = 'armedForce';
            $document = $request->armedForceDoc;
            $imageName = $docUpload->upload($filename, $document, $imageRelativePath);

            ActiveCitizen::where('id', $id)
                ->update([
                    'armed_force_doc' => $imageName,
                ]);
        }
    }

    /**
     *  Citizen Login
     */
    public function citizenLogin(Request $req)
    {
        try {
            $req->validate([
                'mobile' => "required",
                'password' => [
                    'required',
                    'min:6',
                    'max:255',
                    'regex:/[a-z]/',      // must contain at least one lowercase letter
                    'regex:/[A-Z]/',      // must contain at least one uppercase letter
                    'regex:/[0-9]/',      // must contain at least one digit
                    'regex:/[@$!%*#?&]/'  // must contain a special character
                ],
            ]);
            $citizenInfo = ActiveCitizen::where('mobile', $req->mobile)
                ->first();
            if (!$citizenInfo) {
                $msg = "Oops! Given mobile no does not exist";
                return responseMsg(false, $msg, "");
            }

            $userDetails['id'] = $citizenInfo->id;
            $userDetails['userName'] = $citizenInfo->user_name;
            $userDetails['mobile'] = $citizenInfo->mobile;
            $userDetails['userType'] = $citizenInfo->user_type;
            $userDetails['user_type'] = $citizenInfo->user_type;
            $userDetails['ip_address'] = $citizenInfo->ip_address;

            if ($citizenInfo) {
                if (Hash::check($req->password, $citizenInfo->password)) {
                    $token = $citizenInfo->createToken('my-app-token')->plainTextToken;
                    $citizenInfo->remember_token = $token;
                    $citizenInfo->save();
                    $userDetails['token'] = $token;
                    $key = 'last_activity_citizen_' . $citizenInfo->id;               // Set last activity key 
                    return responseMsgs(true, 'You r logged in now', $userDetails, '', "1.0", responseTime(), $req->getMethod(), $req->deviceId);
                } else {
                    $msg = "Incorrect Password";
                    return responseMsgs(false, $msg, "", '', "1.0", responseTime(), $req->getMethod(), $req->deviceId);
                }
            }
        }
        // Authentication Using Sql Database
        catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '', "1.0", responseTime(), $req->getMethod(), $req->deviceId);
        }
    }

    /**
     * | Citizen Logout 
     */
    public function citizenLogout(Request $req)
    {
        // token();
        $id =  auth()->user()->id;

        $user = ActiveCitizen::where('id', $id)->first();
        $user->remember_token = null;
        $user->save();

        $user->tokens()->delete();

        return responseMsgs(true, 'Successfully logged out', "", '', "1.0", responseTime(), $req->getMethod(), $req->deviceId);
    }

    /**
     * | Get Citizen Details
     */
    public function getCitizenByID(Request $request, $id)
    {
        try {
            $citizen = ActiveCitizen::find($id);

            return responseMsgs(true, "Citizen Details", $citizen,  '', "1.0", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '', "1.0", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Get Citizen Details
     */
    public function getAllCitizens(Request $request)
    {
        try {
            $citizen = ActiveCitizen::get();

            return responseMsgs(true, "Citizen Details", $citizen,  '', "1.0", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", '', "1.0", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * |
     */
    public function citizenEditProfile(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'id'     => 'required'
        ]);

        if ($validator->fails())
            return validationError($validator);

        try {
            $citizen = ActiveCitizen::find($request->id);
            $citizen->user_name = $request->name;
            $citizen->email = $request->email;
            $citizen->mobile = $request->mobile;
            $citizen->gender = $request->gender;
            $citizen->dob    = $request->dob;
            $citizen->aadhar = $request->aadhar;
            $citizen->is_specially_abled = $request->isSpeciallyAbled;
            $citizen->is_armed_force = $request->isArmedForce;
            $citizen->save();

            $this->docUpload($request, $citizen->id);
            return responseMsgs(true, "Successful Updated", "",  '', "1.0", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "",  '', "1.0", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Citizen Change Password
     */
    public function changeCitizenPass(ChangePassRequest $request)
    {
        try {
            $id = auth()->user()->id;
            $citizen = ActiveCitizen::where('id', $id)->firstOrFail();
            $validPassword = Hash::check($request->password, $citizen->password);
            if ($validPassword) {

                $citizen->password = Hash::make($request->newPassword);
                $citizen->save();

                return responseMsgs(true, 'Successfully Changed the Password', "", "", "01", responseTime(), $request->getMethod(), $request->deviceId);
            }
            throw new Exception("Old Password doesn't Match!");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * |
     */
    public function changeCitizenPassByOtp(OtpChangePass $request)
    {
        try {
            $id = auth()->user()->id;
            $citizen = ActiveCitizen::where('id', $id)->firstOrFail();
            $citizen->password = Hash::make($request->password);
            $citizen->save();

            return responseMsgs(true, "Password changed!", "", "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /** 
     * 
     */
    public function profileDetails(Request $request)
    {
        try {
            $citizenId = auth()->user()->id;
            $details = ActiveCitizen::find($citizenId);

            $details->name = $details->user_name;
            $details->aadhar_doc = (config('app.url') . '/' . $details->relative_path . $details->aadhar_doc);
            $details->specially_abled_doc = (config('app.url') . '/' . $details->relative_path . $details->specially_abled_doc);
            $details->armed_force_doc = (config('app.url') . '/' . $details->relative_path . $details->armed_force_doc);
            $details->profile_photo = (config('app.url') . '/' . $details->relative_path . $details->profile_photo);

            return responseMsgs(true, "Citizen Details", $details, "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
}

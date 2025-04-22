<?php

namespace App\Helpers;

use App\Models\MasterCompany;
use App\Models\User;
use App\Models\Booking;
use App\Models\AddressBook;

class CustomHelpers
{
    public static function getIDFromURN($arr)
    {
      
        $id = false;

        if (isset($arr['urn']) && isset($arr['type'])) {
            switch ($arr['type']) {
                case 'company':
                    $company = MasterCompany::where('company_urn', $arr['urn'])->first();
                    $id = $company ? $company->company_id : false;
                    break;
                
                case 'user':
                    $user = User::where('user_urn', $arr['urn'])->first();
                    $id = $user ? $user->user_id : false;
                    break;
                
                case 'booking':
                    $booking = Booking::where('booking_urn', $arr['urn'])->first();
                    $id = $booking ? $booking->booking_id : false;
                    break;
                
                case 'addressbook':
                    $address = AddressBook::where('address_urn', $arr['urn'])->first();
                    $id = $address ? $address->address_id : false;
                    break;
                
                default:
                    $id = false; // No match found for the type
                    break;
            }
        }

        return $id;
    }
    
}
